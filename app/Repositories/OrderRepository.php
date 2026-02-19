<?php
// app/Repositories/OrderRepository.php

namespace App\Repositories;

use App\Models\Order;
use Carbon\Carbon;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getByUser(int $userId, int $perPage = 10)
    {
        return $this->findByUser($userId, ['customer', 'product'])
                    ->latest()
                    ->paginate($perPage);
    }

    public function getTodaySales(int $userId): float
    {
        return $this->model->where('user_id', $userId)
                          ->whereDate('created_at', Carbon::today())
                          ->where('status', 'completed')
                          ->sum('total_price');
    }

    public function getTotalOrders(int $userId): int
    {
        return $this->model->where('user_id', $userId)->count();
    }

    public function getProductsSold(int $userId): int
    {
        return $this->model->where('user_id', $userId)
                          ->where('status', 'completed')
                          ->sum('quantity');
    }

    public function getRecentOrders(int $userId, int $limit = 5)
    {
        return $this->findByUser($userId, ['customer', 'product'])
                    ->latest()
                    ->take($limit)
                    ->get();
    }
}