<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_id',
        'order_code',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax',
        'discount',
        'shipping_cost',
        'total_price',
        'paid_amount',
        'change_amount',
        'notes',
        'ordered_at',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'payment_status_label',
        'profit',
        'is_paid',
        'is_overdue',
    ];

    // ==================== CONSTANTS ====================

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_REFUNDED = 'refunded';

    const STATUSES = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING => 'Belum Bayar',
        self::PAYMENT_PARTIAL => 'DP',
        self::PAYMENT_PAID => 'Lunas',
        self::PAYMENT_REFUNDED => 'Dikembalikan',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_code)) {
                $order->order_code = $order->generateOrderCode();
            }
            if (empty($order->ordered_at)) {
                $order->ordered_at = now();
            }
            $order->calculateTotals();
        });

        static::updating(function ($order) {
            if ($order->isDirty(['subtotal', 'tax', 'discount', 'shipping_cost'])) {
                $order->calculateTotals();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot('quantity', 'price', 'subtotal');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ==================== SCOPES ====================

    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', [self::PAYMENT_PENDING, self::PAYMENT_PARTIAL]);
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getStatusColorAttribute(): string
    {
        return [
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
        ][$this->status] ?? 'gray';
    }

    public function getProfitAttribute(): float
    {
        $cost = $this->items->sum(function ($item) {
            return $item->quantity * ($item->product->cost_price ?? 0);
        });
        
        return $this->total_price - $cost;
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_paid && $this->created_at->diffInDays(now()) > 7;
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_price - $this->paid_amount;
    }

    // ==================== METHODS ====================

    public function generateOrderCode(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}-{$date}-{$random}";
    }

    public function calculateTotals(): void
    {
        $this->total_price = ($this->subtotal ?? 0) 
                           + ($this->tax ?? 0) 
                           + ($this->shipping_cost ?? 0) 
                           - ($this->discount ?? 0);
    }

    public function addItem(Product $product, int $quantity, ?float $customPrice = null): OrderItem
    {
        $price = $customPrice ?? $product->price;
        $subtotal = $price * $quantity;

        $item = $this->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $quantity,
            'price' => $price,
            'cost_price' => $product->cost_price,
            'subtotal' => $subtotal,
        ]);

        // Update order subtotal
        $this->subtotal = $this->items->sum('subtotal');
        $this->save();

        // Decrease product stock
        $product->decreaseStock($quantity, 'order', $this->id);

        return $item;
    }

    public function process(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Update customer stats
        $this->customer->updateStats();
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        // Return stock
        foreach ($this->items as $item) {
            $item->product->increaseStock($item->quantity, 'order_cancelled');
        }
    }

    public function recordPayment(float $amount, string $method = 'cash', ?string $notes = null): Payment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'method' => $method,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        $this->paid_amount += $amount;
        $this->change_amount = max(0, $this->paid_amount - $this->total_price);
        
        // Update payment status
        if ($this->paid_amount >= $this->total_price) {
            $this->payment_status = self::PAYMENT_PAID;
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = self::PAYMENT_PARTIAL;
        }
        
        $this->save();

        return $payment;
    }
}