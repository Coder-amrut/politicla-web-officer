<?php

namespace App\Enums;

enum RoleName: string
{
    case ADMIN   = 'ADMIN';
    case OFFICER = 'OFFICER';
    case WORKER  = 'WORKER';

    /**
     * Roles that are allowed for public self-registration.
     */
    public static function publicRegistrable(): array
    {
        return [self::WORKER->value, self::OFFICER->value];
    }
}
