<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function updateAvailableQuantity()
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        $this->save();
    }

    public function reserveStock($quantity)
    {
        if ($this->available_quantity >= $quantity) {
            $this->reserved_quantity += $quantity;
            $this->updateAvailableQuantity();
            return true;
        }
        return false;
    }

    public function releaseReservedStock($quantity)
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->updateAvailableQuantity();
    }

    public function confirmStock($quantity)
    {
        $this->quantity -= $quantity;
        $this->reserved_quantity -= $quantity;
        $this->updateAvailableQuantity();
    }

    public function addStock($quantity)
    {
        $this->quantity += $quantity;
        $this->updateAvailableQuantity();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->available_quantity = $model->quantity - $model->reserved_quantity;
        });
    }
}