<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case DRAFT     = 'DRAFT';
    case ACTIVE    = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED  = 'ARCHIVED';

    /**
     * Returns the valid next statuses from a given status.
     * Enforces a logical one-way workflow:
     *   DRAFT → ACTIVE → COMPLETED → ARCHIVED
     *
     * ARCHIVED is terminal — no transitions allowed from it.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT     => [self::ACTIVE, self::COMPLETED],
            self::ACTIVE    => [self::COMPLETED],
            self::COMPLETED => [self::ARCHIVED, self::ACTIVE],
            self::ARCHIVED  => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }
}
