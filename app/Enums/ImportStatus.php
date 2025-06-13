<?php

namespace App\Enums;

class ImportStatus
{
    const PENDING = 'pending';
    const CONFIRMED = 'confirmed';
    const RECEIVED = 'received';
    const CANCELLED = 'cancelled';

    public static function all()
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::RECEIVED,
            self::CANCELLED,
        ];
    }

    public static function labels()
    {
        return [
            self::PENDING => 'Chờ xác nhận',
            self::CONFIRMED => 'Đã xác nhận',
            self::RECEIVED => 'Đã nhận hàng',
            self::CANCELLED => 'Đã hủy',
        ];
    }

    public static function getLabel($status)
    {
        $labels = self::labels();
        return $labels[$status] ?? $status;
    }

    public static function getBadgeClass($status)
    {
        $classes = [
            self::PENDING => 'badge-warning',
            self::CONFIRMED => 'badge-info',
            self::RECEIVED => 'badge-success',
            self::CANCELLED => 'badge-danger',
        ];
        return $classes[$status] ?? 'badge-secondary';
    }
}