<?php
// app/Http/Resources/OrderResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'quantity' => $this->quantity,
            'total_price' => (float) $this->total_price,
            'formatted_total' => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at->format('d M Y H:i'),
            'created_at_human' => $this->created_at->diffForHumans()
        ];
    }

    private function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'Menunggu',
            'processing' => 'Diproses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];
        return $labels[$this->status] ?? $this->status;
    }

    private function getStatusColor(): string
    {
        $colors = [
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red'
        ];
        return $colors[$this->status] ?? 'gray';
    }
}