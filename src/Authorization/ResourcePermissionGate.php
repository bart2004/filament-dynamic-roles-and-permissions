<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Authorization;

use BartStrik\FilamentDynamicRolesAndPermissions\Services\PermissionCacheService;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorizes the standard resource abilities for every Filament resource
 * automatically, so consumers no longer have to write a policy class per model.
 *
 * A single {@see Gate::before()} hook resolves each ability against the current
 * user's stored resource permissions. Unlike a policy, the before-callback
 * receives the model (or model class) for every ability — including the
 * instance-less {@see viewAny}/{@see create} — which lets one shared resolver
 * map any model back to its Filament resource.
 *
 * The hook defers whenever the developer has registered their own policy for a
 * model, so a hand-written policy still wins where one exists.
 */
class ResourcePermissionGate
{
    /**
     * Abilities that map to a stored resource action. Bulk (*Any) variants share
     * the same action as their single-record counterpart. Any ability not listed
     * here is left to the application's own authorization.
     *
     * @var array<string, string>
     */
    protected const ABILITY_ACTIONS = [
        'viewAny' => 'viewAny',
        'view' => 'view',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
        'deleteAny' => 'delete',
        'restore' => 'restore',
        'restoreAny' => 'restore',
        'forceDelete' => 'forceDelete',
        'forceDeleteAny' => 'forceDelete',
    ];

    /**
     * Cached map of model class => Filament resource class, built from every
     * registered panel's resources on first use.
     *
     * @var array<class-string, class-string>|null
     */
    protected static ?array $modelResourceMap = null;

    public function __construct(
        protected Gate $gate,
        protected PermissionCacheService $cache,
    ) {}

    /**
     * Registers the global before-hook. Called once from the service provider.
     */
    public static function register(): void
    {
        app(Gate::class)->before(function (Authenticatable $user, string $ability, array $arguments) {
            return app(static::class)->authorize($user, $ability, $arguments[0] ?? null);
        });
    }

    /**
     * Resolves a single ability. Returns true/false to authorize a managed
     * Filament resource, or null to fall through to the normal Gate pipeline.
     *
     * @param  object|class-string|null  $target  the model instance or class being authorized
     */
    public function authorize(Authenticatable $user, string $ability, mixed $target): ?bool
    {
        $action = self::ABILITY_ACTIONS[$ability] ?? null;

        if ($action === null) {
            return null;
        }

        $modelClass = $this->resolveModelClass($target);

        if ($modelClass === null) {
            return null;
        }

        // Respect a developer's own policy: if one is registered (or discovered
        // by Laravel's naming convention) for this model, let it decide instead.
        if ($this->gate->getPolicyFor($modelClass) !== null) {
            return null;
        }

        $resourceClass = $this->resourceFor($modelClass);

        if ($resourceClass === null) {
            return null;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->role_id ?? null)) {
            return false;
        }

        return $this->cache->hasResourceAction($user->role_id, $resourceClass, $action);
    }

    /**
     * @param  object|class-string|null  $target
     * @return class-string|null
     */
    protected function resolveModelClass(mixed $target): ?string
    {
        if (is_object($target)) {
            return $target::class;
        }

        if (is_string($target) && class_exists($target)) {
            return $target;
        }

        return null;
    }

    /**
     * @param  class-string  $modelClass
     * @return class-string|null
     */
    protected function resourceFor(string $modelClass): ?string
    {
        return $this->modelResourceMap()[$modelClass] ?? null;
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function modelResourceMap(): array
    {
        if (self::$modelResourceMap !== null) {
            return self::$modelResourceMap;
        }

        $map = [];

        try {
            foreach (Filament::getPanels() as $panel) {
                foreach ($panel->getResources() as $resourceClass) {
                    try {
                        $model = $resourceClass::getModel();
                    } catch (\Throwable) {
                        continue;
                    }

                    // First registration wins so a resource cannot be shadowed by
                    // another panel exposing the same model later in the list.
                    $map[$model] ??= $resourceClass;
                }
            }
        } catch (\Throwable) {
            // Panels not booted yet (e.g. authorization outside an HTTP request);
            // resolve nothing rather than fail the check.
            return [];
        }

        // Only cache once resources are actually available, so an early check
        // before the panels are registered does not pin an empty map.
        if ($map === []) {
            return [];
        }

        return self::$modelResourceMap = $map;
    }

    /**
     * Clears the cached model map. Primarily useful in tests where panels and
     * resources are registered per case.
     */
    public static function flush(): void
    {
        self::$modelResourceMap = null;
    }
}
