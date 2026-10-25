<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case SELF_REGISTRATION = 'SELF_REGISTRATION';
    case ADMIN_CREATED     = 'ADMIN_CREATED';
    case SYSTEM_BOOTSTRAP  = 'SYSTEM_BOOTSTRAP';
}
