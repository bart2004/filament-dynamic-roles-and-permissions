<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Enums;

enum ActionAccess: string
{
    case VISIBLE = 'visible';
    case DISABLED = 'disabled';
    case HIDDEN = 'hidden';
}
