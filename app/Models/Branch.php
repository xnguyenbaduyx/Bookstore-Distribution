<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'manager_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function orderRequests()
    {
        return $this->hasMany(OrderRequest::class);
    }

    public function distributions()
    {
        return $this->hasMany(Distribution::class);
    }

    public function inventories()
    {
        return $this->hasMany(BranchInventory::class);
    }
}