<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Controls where the Roles resource appears in the Filament panel
    | navigation. Leave null to use Filament's defaults.
    |
    */

    'navigation' => [
        'group' => null,
        'sort' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic resource policies
    |--------------------------------------------------------------------------
    |
    | When enabled, the standard resource abilities (viewAny, view, create,
    | update, delete, restore, forceDelete) are authorized automatically for
    | every Filament resource from the current role's stored permissions, so
    | you do not have to write a policy class per model.
    |
    | Any model that already has its own policy (registered explicitly or found
    | by Laravel's naming convention) is left untouched, so you can still hand
    | write a policy where you need custom behaviour. Set this to false to opt
    | out entirely and manage every policy yourself.
    |
    */

    'auto_register_policies' => true,

];
