<?php
// app/Http/Resources/CustomerResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'total_orders' => $this->total_orders,
            'total_spent' => (float) $this->total_spent,
            'formatted_spent' => 'Rp ' . number_format($this->total_spent, 0, ',', '.'),
            'created_at' => $this->created_at->format('d M Y')
        ];
    }
}