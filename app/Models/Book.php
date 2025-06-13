<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'category_id',
        'price',
        'description',
        'publisher',
        'published_date',
        'image',
        'is_active',
    ];

    protected $casts = [
        'published_date' => 'date',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function branchInventories()
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function orderRequestDetails()
    {
        return $this->hasMany(OrderRequestDetail::class);
    }

    public function distributionDetails()
    {
        return $this->hasMany(DistributionDetail::class);
    }

    public function importDetails()
    {
        return $this->hasMany(ImportDetail::class);
    }
}