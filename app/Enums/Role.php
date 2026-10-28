<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Staff => 'Staff Gudang',
        };
    }
}
