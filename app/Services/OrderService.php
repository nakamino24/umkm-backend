<?php
// app/Services/OrderService.php

namespace App\Services;

use App\Http\Resources\OrderResource;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

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
        $customer = $this->customerRepository->findOrFail($data['customer_id']);

        if ((int) $product->user_id !== $userId || (int) $customer->user_id !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        if (! $product->canFulfillOrder((int) $data['quantity'])) {
            throw new \Exception('Stok tidak mencukupi', 400);
        }

        $order = DB::transaction(function () use ($userId, $data, $product, $customer) {
            $quantity = (int) $data['quantity'];
            $price = (float) $product->price;
            $subtotal = $price * $quantity;

            $order = $this->orderRepository->create([
                'user_id' => $userId,
                'customer_id' => $customer->id,
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'total_price' => $subtotal,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'price' => $price,
                'cost_price' => $product->cost_price,
                'subtotal' => $subtotal,
            ]);

            $this->productRepository->decrementStock($product->id, $quantity);
            $this->customerRepository->updateStats($customer->id, $subtotal);

            return $order;
        });

        return new OrderResource($order->load(['customer', 'items.product']));
    }

    public function updateStatus(int $id, int $userId, string $status): OrderResource
    {
        $order = $this->orderRepository->findOrFail($id);

        if ((int) $order->user_id !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        $payload = ['status' => $status];
        if ($status === 'completed' && empty($order->completed_at)) {
            $payload['completed_at'] = now();
        }

        $order->update($payload);

        return new OrderResource($order->fresh()->load(['customer', 'items.product']));
    }
}
