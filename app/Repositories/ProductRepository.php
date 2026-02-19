<?php
// app/Repositories/ProductRepository.php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getByUserWithFilters(int $userId, array $filters = [], int $perPage = 10)
    {
        $query = $this->findByUser($userId, ['category']);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $query->where('stock', '<', 5)->where('stock', '>', 0);
            } elseif ($filters['stock_status'] === 'out') {
                $query->where('stock', 0);
            } elseif ($filters['stock_status'] === 'available') {
                $query->where('stock', '>=', 5);
            }
        }

        return $query->latest()->paginate($perPage);
    }

    public function getLowStockCount(int $userId): int
    {
        return $this->model->where('user_id', $userId)
                          ->where('stock', '<', 5)
                          ->count();
    }

    public function decrementStock(int $productId, int $quantity): bool
    {
        $product = $this->findOrFail($productId);
        
        if ($product->stock < $quantity) {
            return false;
        }

        $product->decrement('stock', $quantity);
        return true;
    }
}