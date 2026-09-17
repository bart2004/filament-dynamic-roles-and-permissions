<?php

namespace BartStrik\FilamentRolePermissions\Enums;

enum ComponentType: string
{
    case FORM = 'form';
    case CREATE = 'create';
    case EDIT = 'edit';
    case TABLE = 'table';
    case INFOLIST = 'infolist';
}
