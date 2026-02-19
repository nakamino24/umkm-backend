<?php
// app/Http/Resources/DashboardResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'today_sales' => (float) $this['today_sales'],
            'formatted_today_sales' => 'Rp ' . number_format($this['today_sales'], 0, ',', '.'),
            'total_orders' => (int) $this['total_orders'],
            'products_sold' => (int) $this['products_sold'],
            'low_stock' => (int) $this['low_stock'],
            'recent_orders' => OrderResource::collection($this['recent_orders'])
        ];
    }
}