<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_request_id',
        'book_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function orderRequest()
    {
        return $this->belongsTo(OrderRequest::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}