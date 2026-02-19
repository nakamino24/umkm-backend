<?php
// app/Http/Resources/ProductResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'formatted_price' => 'Rp ' . number_format($this->price, 0, ',', '.'),
            'stock' => (int) $this->stock,
            'unit' => $this->unit,
            'stock_status' => $this->stock < 5 ? 'low' : ($this->stock === 0 ? 'out' : 'available'),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'image' => $this->image,
            'created_at' => $this->created_at->format('d M Y'),
            'updated_at' => $this->updated_at->format('d M Y')
        ];
    }
}