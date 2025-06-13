<?php

namespace App\Enums;

class OrderStatus
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const PROCESSING = 'processing';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    public static function all()
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::REJECTED,
            self::PROCESSING,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    public static function labels()
    {
        return [
            self::PENDING => 'Chờ duyệt',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
            self::PROCESSING => 'Đang xử lý',
            self::COMPLETED => 'Hoàn thành',
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
            self::APPROVED => 'badge-success',
            self::REJECTED => 'badge-danger',
            self::PROCESSING => 'badge-info',
            self::COMPLETED => 'badge-primary',
            self::CANCELLED => 'badge-secondary',
        ];
        return $classes[$status] ?? 'badge-secondary';
    }
}