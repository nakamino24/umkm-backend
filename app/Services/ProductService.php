<?php
// app/Services/ProductService.php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Http\Resources\ProductResource;

class ProductService
{
    protected ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(int $userId, array $filters = [])
    {
        $products = $this->repository->getByUserWithFilters($userId, $filters);
        return ProductResource::collection($products);
    }

    public function create(int $userId, array $data): ProductResource
    {
        $data['user_id'] = $userId;
        $product = $this->repository->create($data);
        return new ProductResource($product->load('category'));
    }

    public function update(int $id, int $userId, array $data): ProductResource
    {
        $product = $this->repository->findOrFail($id);

        if ($product->user_id !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        $product->update($data);
        return new ProductResource($product->fresh()->load('category'));
    }

    public function delete(int $id, int $userId): bool
    {
        $product = $this->repository->findOrFail($id);

        if ($product->user_id !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        return $this->repository->delete($id);
    }
}