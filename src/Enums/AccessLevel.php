<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Enums;

enum AccessLevel: string
{
    case MUTABLE = 'mutable';
    case READ_ONLY = 'read_only';
    case FILL_ONLY = 'fill_only';
    case HIDDEN = 'hidden';
}
