<?php

namespace App\Enums;

class UserRole
{
    const ADMIN = 'admin';
    const MANAGER = 'manager';
    const WAREHOUSE = 'warehouse';
    const BRANCH = 'branch';

    public static function all()
    {
        return [
            self::ADMIN,
            self::MANAGER,
            self::WAREHOUSE,
            self::BRANCH,
        ];
    }

    public static function labels()
    {
        return [
            self::ADMIN => 'Quản trị viên',
            self::MANAGER => 'Quản lý trung tâm',
            self::WAREHOUSE => 'Nhân viên kho',
            self::BRANCH => 'Chi nhánh',
        ];
    }

    public static function getLabel($role)
    {
        $labels = self::labels();
        return $labels[$role] ?? $role;
    }
}