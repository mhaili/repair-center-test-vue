<?php

namespace App\Entity;

enum RepairOrderStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING_PARTS = 'WAITING_PARTS';
    case DONE = 'DONE';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    public function canTransitionTo(self $next): bool
    {
        if ($this === self::CANCELLED) {
            return false;
        }

        if ($this === self::DELIVERED) {
            return $next === self::CANCELLED;
        }

        return true;
    }
}
