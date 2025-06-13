<?php

namespace App\Enums;

class DistributionStatus
{
    const PENDING = 'pending';
    const CONFIRMED = 'confirmed';
    const SHIPPED = 'shipped';
    const DELIVERED = 'delivered';
    const CANCELLED = 'cancelled';

    public static function all()
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED,
        ];
    }

    public static function labels()
    {
        return [
            self::PENDING => 'Chờ xác nhận',
            self::CONFIRMED => 'Đã xác nhận',
            self::SHIPPED => 'Đã xuất kho',
            self::DELIVERED => 'Đã giao',
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
            self::SHIPPED => 'badge-primary',
            self::DELIVERED => 'badge-success',
            self::CANCELLED => 'badge-danger',
        ];
        return $classes[$status] ?? 'badge-secondary';
    }
}