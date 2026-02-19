<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'cost_price',
        'stock',
        'min_stock',
        'unit',
        'image',
        'barcode',
        'is_active',
        'weight',
        'dimensions',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
    ];

    protected $appends = [
        'profit_margin',
        'stock_status',
        'stock_status_color',
        'image_url',
        'total_value',
    ];

    // ==================== CONSTANTS ====================

    const UNITS = [
        'pcs' => 'Pieces',
        'kg' => 'Kilogram',
        'g' => 'Gram',
        'liter' => 'Liter',
        'ml' => 'Milliliter',
        'meter' => 'Meter',
        'cm' => 'Centimeter',
        'pack' => 'Pack',
        'box' => 'Box',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = $product->generateSku();
            }
            if (empty($product->min_stock)) {
                $product->min_stock = 5;
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
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

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock')
                     ->where('stock', '>', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->when($min, function ($q) use ($min) {
            $q->where('price', '>=', $min);
        })->when($max, function ($q) use ($max) {
            $q->where('price', '<=', $max);
        });
    }

    // ==================== ACCESSORS ====================

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price == 0) {
            return null;
        }
        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        }
        if ($this->stock <= $this->min_stock) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        return [
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
        ][$this->stock_status] ?? 'gray';
    }

    public function getStockStatusLabelAttribute(): string
    {
        return [
            'out_of_stock' => 'Habis',
            'low_stock' => 'Menipis',
            'in_stock' => 'Tersedia',
        ][$this->stock_status] ?? 'Unknown';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getTotalValueAttribute(): float
    {
        return $this->stock * $this->price;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    // ==================== MUTATORS ====================

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = str_replace(['.', ','], ['', '.'], $value);
    }

    // ==================== METHODS ====================

    public function generateSku(): string
    {
        $prefix = 'PRD';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function decreaseStock(int $quantity, string $reason = 'order', ?int $orderId = null): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }

        $oldStock = $this->stock;
        $this->decrement('stock', $quantity);

        // Catat history
        $this->stockHistories()->create([
            'type' => 'decrease',
            'quantity' => $quantity,
            'old_stock' => $oldStock,
            'new_stock' => $this->fresh()->stock,
            'reason' => $reason,
            'order_id' => $orderId,
            'user_id' => auth()->id(),
        ]);

        return true;
    }

    public function increaseStock(int $quantity, string $reason = 'restock'): void
    {
        $oldStock = $this->stock;
        $this->increment('stock', $quantity);

        // Catat history
        $this->stockHistories()->create([
            'type' => 'increase',
            'quantity' => $quantity,
            'old_stock' => $oldStock,
            'new_stock' => $this->fresh()->stock,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->min_stock;
    }

    public function canFulfillOrder(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}