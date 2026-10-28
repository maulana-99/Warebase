<?php

namespace App\Enums;

enum TransactionType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Barang Masuk',
            self::Out => 'Barang Keluar',
            self::Adjustment => 'Penyesuaian Stok',
        };
    }

    /**
     * Prefix used when generating transaction codes, e.g. TRX-IN-20261028-0001.
     */
    public function codePrefix(): string
    {
        return match ($this) {
            self::In => 'IN',
            self::Out => 'OUT',
            self::Adjustment => 'ADJ',
        };
    }
}
