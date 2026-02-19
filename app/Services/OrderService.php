<?php
// app/Services/OrderService.php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\CustomerRepository;
use App\Http\Resources\OrderResource;

class OrderService
{
    protected OrderRepository $orderRepository;
    protected ProductRepository $productRepository;
    protected CustomerRepository $customerRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        CustomerRepository $customerRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
    }

    public function getAll(int $userId)
    {
        $orders = $this->orderRepository->getByUser($userId);
        return OrderResource::collection($orders);
    }

    public function create(int $userId, array $data): OrderResource
    {
        $product = $this->productRepository->findOrFail($data['product_id']);

        // Check stock
        if ($product->stock < $data['quantity']) {
            throw new \Exception('Stok tidak mencukupi', 400);
        }

        $totalPrice = $product->price * $data['quantity'];

        $orderData = [
            'user_id' => $userId,
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'total_price' => $totalPrice,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null
        ];

        $order = $this->orderRepository->create($orderData);

        // Update stock
        $this->productRepository->decrementStock($data['product_id'], $data['quantity']);

        // Update customer stats
        $this->customerRepository->updateStats($data['customer_id'], $totalPrice);

        return new OrderResource($order->load(['customer', 'product']));
    }

    public function updateStatus(int $id, int $userId, string $status): OrderResource
    {
        $order = $this->orderRepository->findOrFail($id);

        if ($order->user_id !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        $order->update(['status' => $status]);
        return new OrderResource($order->fresh()->load(['customer', 'product']));
    }
}