<?php
// app/Models/Customer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'notes',
        'total_orders',
        'total_spent',
        'last_order_at',
        'is_active',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'last_order_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'initials',
        'formatted_phone',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->code)) {
                $customer->code = $customer->generateCode();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ==================== SCOPES ====================

    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    public function scopeFrequent($query, $minOrders = 5)
    {
        return $query->where('total_orders', '>=', $minOrders);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('last_order_at', '>=', now()->subDays($days));
    }

    // ==================== ACCESSORS ====================

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) break;
        }
        
        return $initials;
    }

    public function getFormattedPhoneAttribute(): string
    {
        $phone = $this->phone;
        
        // Format: 081234567890 -> +62 812-3456-7890
        if (strlen($phone) > 10) {
            return '+62 ' . substr($phone, 1, 3) . '-' . 
                   substr($phone, 4, 4) . '-' . 
                   substr($phone, 8);
        }
        
        return $phone;
    }

    // ==================== METHODS ====================

    public function generateCode(): string
    {
        $prefix = 'CUS';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function updateStats(): void
    {
        $this->update([
            'total_orders' => $this->orders()->count(),
            'total_spent' => $this->orders()->sum('total_price'),
            'last_order_at' => $this->orders()->latest()->value('created_at'),
        ]);
    }

    public function getAverageOrderValue(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }
        return $this->total_spent / $this->total_orders;
    }
}