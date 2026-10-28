<?php

return [

    'fields' => [
        'name' => 'Name',
        'permission_level' => 'Permission level',
    ],

    'sections' => [
        'resource_actions' => 'Resource actions',
        'header_actions' => 'Header actions',
        'row_actions' => 'Row actions',
        'bulk_actions' => 'Bulk actions',
    ],

    // Shown under the permission level slider, in order from level 0 to 7.
    'levels' => [
        'none' => 'None',
        'view_any' => 'View any',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'force_delete' => 'Force delete',
    ],

    // Tab names for the pages of a resource. Pages that are not listed here
    // fall back to a headline of the page name.
    'pages' => [
        'index' => 'List',
        'create' => 'Create',
        'edit' => 'Edit',
        'view' => 'View',
    ],

    'contexts' => [
        'form' => 'Form',
        'table' => 'Table',
        'infolist' => 'Infolist',
    ],

    'access' => [
        'mutable' => 'Mutable',
        'read_only' => 'Read only',
        'hidden' => 'Hidden',
    ],

    'action_access' => [
        'visible' => 'Visible',
        'disabled' => 'Disabled',
        'hidden' => 'Hidden',
    ],

    'empty_state' => [
        'tab' => 'No pages available',
        'heading' => 'No page permissions available',
        'description' => 'Enable at least one resource action above to configure page-level access.',
    ],

    'hints' => [
        'code_condition' => 'Be aware: this setting may not have full control due to a code-applied condition.',
        'required_field' => 'This field is required. Setting it to Read only or Hidden stops the user from saving a new record.',
    ],

];
