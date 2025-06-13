<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'order_request_id',
        'branch_id',
        'created_by',
        'status',
        'notes',
        'confirmed_at',
        'confirmed_by',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function orderRequest()
    {
        return $this->belongsTo(OrderRequest::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function details()
    {
        return $this->hasMany(DistributionDetail::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->details->sum('total_price');
    }

    public function canBeConfirmed()
    {
        return $this->status === 'pending';
    }

    public function canBeShipped()
    {
        return $this->status === 'confirmed';
    }

    public function canBeDelivered()
    {
        return $this->status === 'shipped';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}