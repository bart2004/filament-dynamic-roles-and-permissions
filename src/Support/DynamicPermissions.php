<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Support;

use BartStrik\FilamentDynamicRolesAndPermissions\Enums\AccessLevel;
use BartStrik\FilamentDynamicRolesAndPermissions\Services\PermissionCacheService;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Columns\Column;

/**
 * Attaches render-time permission enforcement to every Filament field, column
 * and infolist entry, resolving the stored access level against the current
 * user's role once the component is bound to its Livewire page.
 */
class DynamicPermissions
{
    /**
     * Nesting depth of active suppression scopes. While greater than zero, no
     * permission closures are attached to components — used when the role form
     * introspects resources so its own enforcement is not mistaken for a
     * developer-applied condition.
     */
    protected static int $suppressionDepth = 0;

    /**
     * Registers the global component hooks. Called once from the package service
     * provider's boot method.
     */
    public static function attach(): void
    {
        Field::configureUsing(function (Field $field): void {
            static::applyPermission($field, 'form');
        });

        Column::configureUsing(function (Column $column): void {
            static::applyPermission($column, 'table');
        });

        Entry::configureUsing(function (Entry $entry): void {
            static::applyPermission($entry, 'infolist');
        });
    }

    /**
     * Runs the callback without attaching any permission enforcement to components
     * created inside it.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutEnforcement(callable $callback): mixed
    {
        self::$suppressionDepth++;

        try {
            return $callback();
        } finally {
            self::$suppressionDepth--;
        }
    }

    /**
     * Attaches render-time closures so the access level is resolved once the
     * component is bound to its Livewire page, where the resource context is known.
     */
    protected static function applyPermission(mixed $component, string $type): void
    {
        if (self::$suppressionDepth > 0) {
            return;
        }

        $resolve = fn (): ?AccessLevel => static::resolveAccessLevel($component, $type);

        $component->hidden(fn (): bool => $resolve() === AccessLevel::HIDDEN);

        if ($type === 'infolist') {
            return;
        }

        $component->disabled(function () use ($resolve, $component): bool {
            return match ($resolve()) {
                AccessLevel::READ_ONLY => true,
                AccessLevel::FILL_ONLY => static::isEditContext($component),
                default => false,
            };
        });
    }

    protected static function resolveAccessLevel(mixed $component, string $type): ?AccessLevel
    {
        $user = auth()->user();

        if (! $user || ! $user->role_id) {
            return null;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return null;
        }

        $livewire = static::resolveLivewire($component);
        $resourceClass = static::resolveResourceContext($livewire);
        $componentType = static::resolveComponentType($type, $livewire);

        if (! $resourceClass || ! $componentType) {
            return null;
        }

        $accessLevel = app(PermissionCacheService::class)->getFieldAccessLevel(
            $user->role_id,
            $resourceClass,
            $componentType,
            $component->getName(),
        );

        return AccessLevel::tryFrom($accessLevel);
    }

    /**
     * Maps a component kind to the stored permission context. Form fields resolve to
     * the create or edit context depending on the page they render on, so the two
     * forms can be permissioned independently.
     */
    protected static function resolveComponentType(string $type, mixed $livewire): ?string
    {
        if ($type !== 'form') {
            return $type;
        }

        return match (true) {
            $livewire instanceof EditRecord => 'edit',
            $livewire instanceof CreateRecord => 'create',
            default => null,
        };
    }

    /**
     * Resolves the Filament resource class that owns the component by inspecting
     * the Livewire page it is rendered on.
     */
    protected static function resolveResourceContext(mixed $livewire): ?string
    {
        if ($livewire && method_exists($livewire, 'getResource')) {
            try {
                $resource = $livewire::getResource();
            } catch (\Throwable) {
                return null;
            }

            return is_string($resource) ? $resource : null;
        }

        return null;
    }

    protected static function resolveLivewire(mixed $component): mixed
    {
        if (method_exists($component, 'getLivewire')) {
            try {
                if ($livewire = $component->getLivewire()) {
                    return $livewire;
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        if (method_exists($component, 'getTable')) {
            try {
                $table = $component->getTable();

                if ($table && method_exists($table, 'getLivewire')) {
                    return $table->getLivewire();
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        return null;
    }

    protected static function isEditContext(mixed $component): bool
    {
        return static::resolveLivewire($component) instanceof EditRecord;
    }
}
