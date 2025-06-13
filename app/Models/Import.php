<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'supplier_id',
        'created_by',
        'status',
        'notes',
        'confirmed_at',
        'confirmed_by',
        'received_at',
        'total_amount',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'received_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(ImportDetail::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->details->sum('total_price');
    }

    public function canBeConfirmed()
    {
        return $this->status === 'pending';
    }

    public function canBeReceived()
    {
        return $this->status === 'confirmed';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($import) {
            $import->total_amount = $import->details->sum('total_price');
            $import->saveQuietly();
        });
    }
}