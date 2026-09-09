<?php

namespace App\Enums;

enum CardType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Account = 'account';

    public function label(): string
    {
        return match ($this) {
            self::Credit => __('ui.cards.type_credit'),
            self::Debit => __('ui.cards.type_debit'),
            self::Account => __('ui.cards.type_account'),
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
