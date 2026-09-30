<?php

namespace App\Enums;

enum OrderChannel: string
{
    case Web = 'web';
    case Admin = 'admin';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Website',
            self::Admin => 'Admin panel',
            self::Api => 'API',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
