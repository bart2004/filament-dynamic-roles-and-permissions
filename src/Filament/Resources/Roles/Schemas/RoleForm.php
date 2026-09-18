<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Schemas;

use BartStrik\FilamentDynamicRolesAndPermissions\Support\DynamicPermissions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Contracts\Editable as EditableColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component as LivewireComponent;

class RoleForm
{
    /** @var array<string, bool> */
    private static array $extracting = [];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),

                Tabs::make('Permissions')
                    ->tabs(self::getDynamicResourceTabs())
                    ->vertical()
                    ->columnSpanFull(),
            ]);
    }

    protected static function getDynamicResourceTabs(): array
    {
        $tabs = [];
        $resources = Filament::getResources();

        foreach ($resources as $resource) {
            $resourceName = class_basename($resource);

            $schema = [
                Section::make('Resource Actions')->schema([
                    Slider::make("resource_permissions.{$resourceName}.actions")
                        ->label('Permission Level')
                        ->minValue(0)
                        ->maxValue(7)
                        ->step(1)
                        ->default(7)
                        ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? 7))
                        ->fillTrack()
                        ->pips(PipsMode::Steps)
                        ->pipsFormatter(RawJs::make('$value === 0 ? \'None\' : $value === 1 ? \'View Any\' : $value === 2 ? \'View\' : $value === 3 ? \'Create\' : $value === 4 ? \'Update\' : $value === 5 ? \'Delete\' : $value === 6 ? \'Restore\' : $value === 7 ? \'Force Delete\' : String($value)'))
                        ->live(),
                ]),
            ];

            $pagesTabs = self::buildPagesTabs($resource);
            if ($pagesTabs !== null) {
                $schema[] = $pagesTabs;
            }

            $tabs[] = Tabs\Tab::make($resourceName)->schema($schema);
        }

        return $tabs;
    }

    /**
     * Builds one tab per resource page (Index, Create, Edit, View), nesting the
     * field-permission editors (form/table/infolist) for the schema components that
     * page actually renders, together with that page's action permissions.
     */
    protected static function buildPagesTabs(string $resourceClass): ?Tabs
    {
        $resourceName = class_basename($resourceClass);

        try {
            $pages = $resourceClass::getPages();
        } catch (\Throwable) {
            return null;
        }

        $contextAssignment = self::assignContextsToPages($resourceClass, $pages);

        $tabs = [];

        foreach ($pages as $pageName => $pageRoute) {
            $pageClass = $pageRoute->getPage();
            $schema = [];

            $contexts = $contextAssignment[$pageName] ?? [];
            if (! empty($contexts)) {
                $contextTabs = [];
                foreach ($contexts as $context) {
                    $contextTabs[] = self::buildContextTab($resourceClass, $context);
                }

                $schema[] = Tabs::make("field_permissions_{$resourceName}_{$pageName}")
                    ->tabs($contextTabs);
            }

            foreach (self::buildPageActionSections($resourceClass, $pageName, $pageClass) as $section) {
                $schema[] = $section;
            }

            if (empty($schema)) {
                continue;
            }

            $tab = Tabs\Tab::make(ucfirst($pageName))->schema($schema);

            $hiddenCallback = self::buildPageTabHiddenCallback($pageName, $resourceName);
            if ($hiddenCallback !== null) {
                $tab->hidden($hiddenCallback);
            }

            $tabs[] = $tab;
        }

        if (empty($tabs)) {
            return null;
        }

        $allActionKeys = array_filter(array_unique(array_map(
            fn ($pageName) => self::pageNameToAction($pageName),
            array_keys($pages),
        )));

        return Tabs::make('Pages')
            ->tabs([
                ...$tabs,
                Tabs\Tab::make('_placeholder')
                    ->label('No pages available')
                    ->schema([
                        EmptyState::make('No page permissions available')
                            ->description('Enable at least one resource action above to configure page-level access.'),
                    ])
                    ->hidden(fn (Get $get): bool => self::hasAnyAction($get, $resourceName, array_values($allActionKeys))),
            ]);
    }

    /**
     * Determines which field-permission contexts each page owns. Every page that
     * renders a schema shows its editor — the form appears under both Create and
     * Edit — each gated by that page's own action, so create-only or update-only
     * roles can still configure the form fields they can reach.
     *
     * @param  iterable<string, mixed>  $pages
     * @return array<string, array<int, string>>
     */
    protected static function assignContextsToPages(string $resourceClass, iterable $pages): array
    {
        $assignment = [];

        foreach ($pages as $pageName => $pageRoute) {
            $contexts = self::getContextsForPage($pageRoute->getPage(), $resourceClass);

            if (! empty($contexts)) {
                $assignment[$pageName] = $contexts;
            }
        }

        return $assignment;
    }

    /**
     * Detects which schema components a resource page renders, based on the
     * Filament page class it extends.
     *
     * @return array<int, string>
     */
    protected static function getContextsForPage(string $pageClass, string $resourceClass): array
    {
        $contexts = [];

        if (is_subclass_of($pageClass, ListRecords::class)) {
            $contexts[] = 'table';
        }

        // The Create and Edit forms are configured independently, each stored under
        // its own component type, so a field can be, for example, mutable on create
        // but read only on edit.
        if (is_subclass_of($pageClass, CreateRecord::class)) {
            $contexts[] = 'create';
        }

        if (is_subclass_of($pageClass, EditRecord::class)) {
            $contexts[] = 'edit';
        }

        if (is_subclass_of($pageClass, ViewRecord::class) && method_exists($resourceClass, 'infolist')) {
            $contexts[] = 'infolist';
        }

        return $contexts;
    }

    /**
     * @return array<int, Section>
     */
    protected static function buildPageActionSections(string $resourceClass, string $pageName, string $pageClass): array
    {
        $resourceName = class_basename($resourceClass);
        $sections = [];

        $headerActions = self::extractPageActions($pageClass);

        if (! empty($headerActions)) {
            $fields = [];
            foreach ($headerActions as $actionName => $actionLabel) {
                $fields[] = self::buildActionSelect("action_permissions.{$resourceName}.{$pageName}.{$actionName}", $actionLabel);
            }
            $sections[] = Section::make('Header Actions')->schema($fields)->columns(3);
        }

        if ($pageName === 'index') {
            $tableActionGroups = self::extractTableActionGroups($resourceClass);

            if (! empty($tableActionGroups['record'])) {
                $fields = [];
                foreach ($tableActionGroups['record'] as $actionName => $actionLabel) {
                    $fields[] = self::buildActionSelect("action_permissions.{$resourceName}.{$pageName}.record_{$actionName}", $actionLabel);
                }
                $sections[] = Section::make('Row Actions')->schema($fields)->columns(3);
            }

            if (! empty($tableActionGroups['bulk'])) {
                $fields = [];
                foreach ($tableActionGroups['bulk'] as $actionName => $actionLabel) {
                    $fields[] = self::buildActionSelect("action_permissions.{$resourceName}.{$pageName}.bulk_{$actionName}", $actionLabel);
                }
                $sections[] = Section::make('Bulk Actions')->schema($fields)->columns(3);
            }
        }

        return $sections;
    }

    protected static function buildPageTabHiddenCallback(string $pageName, string $resourceName): ?\Closure
    {
        $requiredAction = self::pageNameToAction($pageName);

        if ($requiredAction === null) {
            return null;
        }

        return function (Get $get) use ($resourceName, $requiredAction): bool {
            return ! self::hasAnyAction($get, $resourceName, [$requiredAction]);
        };
    }

    protected static function pageNameToAction(string $pageName): ?string
    {
        return match ($pageName) {
            'index' => 'viewAny',
            'view' => 'view',
            'create' => 'create',
            'edit' => 'update',
            default => null,
        };
    }

    /** @var array<string, int> Maps action name to minimum slider level required */
    private static array $actionLevels = [
        'viewAny' => 1,
        'view' => 2,
        'create' => 3,
        'update' => 4,
        'delete' => 5,
        'restore' => 6,
        'forceDelete' => 7,
    ];

    /** @param array<string> $actions */
    protected static function hasAnyAction(Get $get, string $resourceName, array $actions): bool
    {
        $level = (int) ($get("resource_permissions.{$resourceName}.actions") ?? 0);

        foreach ($actions as $action) {
            $required = self::$actionLevels[$action] ?? PHP_INT_MAX;
            if ($level >= $required) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string> */
    public static function sliderLevelToActions(int $level): array
    {
        return array_keys(array_filter(
            self::$actionLevels,
            fn (int $required) => $level >= $required,
        ));
    }

    protected static function buildActionSelect(string $statePath, string $label): Select
    {
        return Select::make($statePath)
            ->label($label)
            ->options([
                'visible' => 'Visible',
                'disabled' => 'Disabled',
                'hidden' => 'Hidden',
            ])
            ->default('visible');
    }

    /**
     * @return array{record: array<string, string>, bulk: array<string, string>}
     */
    protected static function extractTableActionGroups(string $resourceClass): array
    {
        $groups = ['record' => [], 'bulk' => []];

        try {
            $tableComponent = new class implements HasTable
            {
                use InteractsWithTable;

                public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
                {
                    return null;
                }
            };

            $table = $resourceClass::table(Table::make($tableComponent));

            foreach ($table->getFlatRecordActions() as $action) {
                $name = $action->getName();
                $groups['record'][$name] = $action->getLabel() ?? ucfirst($name);
            }

            foreach ($table->getFlatBulkActions() as $action) {
                $name = $action->getName();
                $groups['bulk'][$name] = $action->getLabel() ?? ucfirst($name);
            }
        } catch (\Throwable) {
            return $groups;
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    protected static function extractPageActions(string $pageClass): array
    {
        $actions = [];

        try {
            $ref = new \ReflectionClass($pageClass);

            if (! $ref->hasMethod('getHeaderActions')) {
                return [];
            }

            $method = $ref->getMethod('getHeaderActions');

            if ($method->getDeclaringClass()->getName() !== $pageClass) {
                return [];
            }

            $page = $ref->newInstanceWithoutConstructor();
            $method->setAccessible(true);
            $headerActions = $method->invoke($page);

            foreach ($headerActions as $action) {
                if (! method_exists($action, 'getName') || ! method_exists($action, 'getLabel')) {
                    continue;
                }

                $name = $action->getName();
                $label = $action->getLabel() ?? ucfirst($name);
                $actions[$name] = $label;
            }
        } catch (\Throwable) {
            return [];
        }

        return $actions;
    }

    protected static function buildContextTab(string $resourceClass, string $context): Tabs\Tab
    {
        $fields = self::extractFields($resourceClass, $context);
        $resourceName = class_basename($resourceClass);
        $isFormContext = self::isFormContext($context);
        $schema = [];

        foreach ($fields as $fieldName => $fieldData) {
            $label = $fieldData['label'];
            $forcedState = $fieldData['forcedState'];
            $hasCodeCondition = $fieldData['hasCodeCondition'];
            $isEditable = $fieldData['isEditable'];
            $isRequired = $fieldData['isRequired'] ?? false;

            $options = match (true) {
                $isFormContext, $isEditable => ['mutable' => 'Mutable', 'read_only' => 'Read Only', 'hidden' => 'Hidden'],
                default => ['read_only' => 'Read Only', 'hidden' => 'Hidden'],
            };

            $defaultValue = $forcedState ?? (($isFormContext || $isEditable) ? 'mutable' : 'read_only');

            $select = Select::make("field_permissions.{$resourceName}.{$context}.{$fieldName}")
                ->label($label)
                ->options($options)
                ->default($defaultValue);

            if ($forcedState !== null) {
                $select->disabled()->dehydrated();
            }

            if ($hasCodeCondition) {
                $select
                    ->hint('Be aware: this setting may not have full control due to a code-applied condition.')
                    ->hintIcon(Heroicon::OutlinedExclamationTriangle)
                    ->hintColor('warning');
            }

            // On the create form, removing write access from a required field means
            // the user can never provide a value, so the record cannot be saved.
            if ($context === 'create' && $isRequired) {
                $select
                    ->live()
                    ->helperText(fn ($state): ?string => in_array($state, ['read_only', 'hidden'], true)
                        ? 'This field is required. Setting it to Read Only or Hidden stops the user from saving a new record.'
                        : null);
            }

            $schema[] = $select;
        }

        return Tabs\Tab::make(self::contextLabel($context))
            ->schema($schema)
            ->columns(3);
    }

    protected static function isFormContext(string $context): bool
    {
        return in_array($context, ['form', 'create', 'edit'], true);
    }

    protected static function fieldIsRequired(mixed $field): bool
    {
        if (! method_exists($field, 'isRequired')) {
            return false;
        }

        try {
            return (bool) $field->isRequired();
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function contextLabel(string $context): string
    {
        return match ($context) {
            'form', 'create', 'edit' => 'Form',
            default => ucfirst($context),
        };
    }

    /**
     * @return array<string, array{label: string, forcedState: string|null, hasCodeCondition: bool, isEditable: bool, isRequired: bool}>
     */
    protected static function extractFields(string $resourceClass, string $context): array
    {
        $key = "{$resourceClass}:{$context}";

        if (isset(self::$extracting[$key])) {
            return [];
        }

        self::$extracting[$key] = true;

        /** @var array<string, array{label: string, forcedState: string|null, hasCodeCondition: bool, isEditable: bool}> $fields */
        $fields = [];

        $schemaComponent = new class extends LivewireComponent implements HasSchemas
        {
            use InteractsWithSchemas;

            public function render(): string
            {
                return '';
            }
        };

        $tableComponent = new class implements HasTable
        {
            use InteractsWithTable;

            public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
            {
                return null;
            }
        };

        try {
            // Suppress the dynamic permission enforcement while introspecting the
            // resource, so its own hidden/disabled closures are not mistaken for a
            // developer-applied condition on these fields.
            DynamicPermissions::withoutEnforcement(function () use (&$fields, $resourceClass, $context, $schemaComponent, $tableComponent): void {
                if (self::isFormContext($context) && method_exists($resourceClass, 'form')) {
                    $schema = $resourceClass::form(Schema::make($schemaComponent));

                    foreach ($schema->getFlatFields() as $name => $field) {
                        $codeState = self::detectFieldCodeState($field);
                        $fields[$name] = [
                            'label' => $field->getLabel() ?? $name,
                            'isEditable' => true,
                            'isRequired' => self::fieldIsRequired($field),
                            ...$codeState,
                        ];
                    }
                } elseif ($context === 'table' && method_exists($resourceClass, 'table')) {
                    $table = $resourceClass::table(Table::make($tableComponent));

                    foreach ($table->getColumns() as $column) {
                        $isEditable = $column instanceof EditableColumn;
                        $codeState = self::detectFieldCodeState($column, checkDisabled: $isEditable);
                        $fields[$column->getName()] = [
                            'label' => $column->getLabel() ?? $column->getName(),
                            'isEditable' => $isEditable,
                            'isRequired' => false,
                            ...$codeState,
                        ];
                    }
                } elseif ($context === 'infolist' && method_exists($resourceClass, 'infolist')) {
                    $infolist = $resourceClass::infolist(Schema::make($schemaComponent));

                    $flatComponents = method_exists($infolist, 'getFlatComponents')
                        ? $infolist->getFlatComponents()
                        : $infolist->getComponents();

                    foreach ($flatComponents as $component) {
                        $name = method_exists($component, 'getName') ? $component->getName() : null;
                        $label = method_exists($component, 'getLabel') ? $component->getLabel() : null;

                        if ($name) {
                            $codeState = self::detectFieldCodeState($component, checkDisabled: false);
                            $fields[$name] = [
                                'label' => $label ?? $name,
                                'isEditable' => false,
                                'isRequired' => false,
                                ...$codeState,
                            ];
                        }
                    }
                }
            });
        } catch (\Throwable) {
            return [];
        } finally {
            unset(self::$extracting[$key]);
        }

        return array_filter(
            $fields,
            fn (string $name): bool => ! str_contains($name, '.'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return array{forcedState: string|null, hasCodeCondition: bool}
     */
    protected static function detectFieldCodeState(mixed $component, bool $checkDisabled = true): array
    {
        $forcedState = null;
        $hasCodeCondition = false;

        /** @var array<class-string, array<string, \ReflectionProperty>> $cache */
        static $cache = [];
        $className = get_class($component);

        if (! isset($cache[$className])) {
            $cache[$className] = [];
            $reflection = new \ReflectionClass($className);

            foreach (['isDisabled', 'isHidden', 'isVisible'] as $propName) {
                $class = $reflection;
                while ($class instanceof \ReflectionClass) {
                    if ($class->hasProperty($propName)) {
                        $prop = $class->getProperty($propName);
                        $prop->setAccessible(true);
                        $cache[$className][$propName] = $prop;
                        break;
                    }
                    $class = $class->getParentClass();
                }
            }
        }

        foreach (['isDisabled', 'isHidden', 'isVisible'] as $propName) {
            if ($propName === 'isDisabled' && ! $checkDisabled) {
                continue;
            }

            if (! isset($cache[$className][$propName])) {
                continue;
            }

            try {
                $prop = $cache[$className][$propName];
                $value = $prop->isInitialized($component) ? $prop->getValue($component) : null;
            } catch (\Throwable) {
                continue;
            }

            if ($value instanceof \Closure) {
                $hasCodeCondition = true;

                continue;
            }

            if ($propName === 'isDisabled' && $value === true && $forcedState === null) {
                $forcedState = 'read_only';
            } elseif (($propName === 'isHidden' && $value === true) || ($propName === 'isVisible' && $value === false)) {
                $forcedState = 'hidden';
            }
        }

        return compact('forcedState', 'hasCodeCondition');
    }
}
