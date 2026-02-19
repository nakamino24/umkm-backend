<?php
// app/Services/DashboardService.php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Http\Resources\DashboardResource;

class DashboardService
{
    protected OrderRepository $orderRepository;
    protected ProductRepository $productRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    public function getStats(int $userId): DashboardResource
    {
        $stats = [
            'today_sales' => $this->orderRepository->getTodaySales($userId),
            'total_orders' => $this->orderRepository->getTotalOrders($userId),
            'products_sold' => $this->orderRepository->getProductsSold($userId),
            'low_stock' => $this->productRepository->getLowStockCount($userId),
            'recent_orders' => $this->orderRepository->getRecentOrders($userId, 5)
        ];

        return new DashboardResource($stats);
    }
}