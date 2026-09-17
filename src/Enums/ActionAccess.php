<?php

namespace BartStrik\FilamentRolePermissions\Enums;

enum ActionAccess: string
{
    case VISIBLE = 'visible';
    case DISABLED = 'disabled';
    case HIDDEN = 'hidden';
}
