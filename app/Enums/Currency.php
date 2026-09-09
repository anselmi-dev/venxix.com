<?php

namespace App\Enums;

enum Currency: string
{
    case Uyu = 'UYU';
    case Usd = 'USD';

    public function symbol(): string
    {
        return match ($this) {
            self::Uyu => '$',
            self::Usd => 'U$',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
