<?php

namespace App\Enums;

enum InventoryMovementReason: string
{
    case Restock = 'restock';
    case Adjustment = 'adjustment';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
    case Sale = 'sale';
    case Return = 'return';
    case Damage = 'damage';

    public function label(): string
    {
        return match ($this) {
            self::Restock => 'Restock',
            self::Adjustment => 'Manual adjustment',
            self::Reservation => 'Reserved at checkout',
            self::ReservationRelease => 'Reservation released',
            self::Sale => 'Sold',
            self::Return => 'Returned',
            self::Damage => 'Damaged / written off',
        };
    }

    /** +1 increases on-hand, -1 decreases it. */
    public function sign(): int
    {
        return match ($this) {
            self::Restock, self::Return, self::ReservationRelease => 1,
            self::Adjustment => 0,
            self::Reservation, self::Sale, self::Damage => -1,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
