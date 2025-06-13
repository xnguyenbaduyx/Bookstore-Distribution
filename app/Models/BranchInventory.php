<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'book_id',
        'quantity',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function addStock($quantity)
    {
        $this->quantity += $quantity;
        $this->save();
    }

    public function removeStock($quantity)
    {
        $this->quantity = max(0, $this->quantity - $quantity);
        $this->save();
    }

    public function hasStock($quantity = 1)
    {
        return $this->quantity >= $quantity;
    }
}
