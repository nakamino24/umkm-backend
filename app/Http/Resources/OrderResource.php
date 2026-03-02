<?php
// app/Http/Resources/OrderResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalQuantity = (int) ($this->items->sum('quantity') ?? 0);

        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'quantity' => $totalQuantity,
            'total_price' => (float) $this->total_price,
            'formatted_total' => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                });
            }),
            'created_at' => $this->created_at?->format('d M Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
