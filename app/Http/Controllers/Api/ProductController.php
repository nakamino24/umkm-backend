<?php
// app/Http/Controllers/Api/ProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'stock_status']);
        $products = $this->service->getAll($request->user()->id, $filters);
        
        return $this->paginatedResponse($products);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->service->create(
                $request->user()->id, 
                $request->validated()
            );
            
            return $this->successResponse($product, 'Produk berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $product = $this->service->find($id);
            return $this->successResponse($product);
        } catch (\Exception $e) {
            return $this->errorResponse('Produk tidak ditemukan', 404);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $product = $this->service->update(
                $id,
                $request->user()->id,
                $request->validated()
            );
            
            return $this->successResponse($product, 'Produk berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $this->service->delete($id, $request->user()->id);
            return $this->successResponse(null, 'Produk berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}