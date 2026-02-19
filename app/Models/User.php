<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'business_name',
        'email',
        'phone',
        'category',
        'password',
        'avatar',
        'address',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'avatar_url',
        'category_label',
    ];

    // ==================== CONSTANTS ====================

    const CATEGORIES = [
        'makanan' => 'Makanan & Minuman',
        'fashion' => 'Fashion & Tekstil',
        'kerajinan' => 'Kerajinan Tangan',
        'pertanian' => 'Pertanian & Perkebunan',
        'jasa' => 'Jasa',
        'lainnya' => 'Lainnya',
    ];

    // ==================== RELATIONSHIPS ====================

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('business_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // ==================== ACCESSORS ====================

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar 
            ? asset('storage/' . $this->avatar) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random';
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

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

    // ==================== MUTATORS ====================

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash($value) 
                ? Hash::make($value) 
                : $value;
        }
    }

    public function setPhoneAttribute($value)
    {
        // Hapus semua non-digit
        $this->attributes['phone'] = preg_replace('/[^0-9]/', '', $value);
    }

    // ==================== METHODS ====================

    public function hasLowStockProducts(): bool
    {
        return $this->products()
                    ->where('stock', '<', 5)
                    ->where('stock', '>', 0)
                    ->exists();
    }

    public function getLowStockProductsCount(): int
    {
        return $this->products()
                    ->where('stock', '<', 5)
                    ->count();
    }

    public function getTodaySales(): float
    {
        return $this->orders()
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('total_price');
    }
}