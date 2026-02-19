<?php
// app/Repositories/CustomerRepository.php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function getByUser(int $userId, int $perPage = 10)
    {
        return $this->findByUser($userId)->latest()->paginate($perPage);
    }

    public function updateStats(int $customerId, float $amount): void
    {
        $customer = $this->findOrFail($customerId);
        $customer->increment('total_orders');
        $customer->increment('total_spent', $amount);
    }
}