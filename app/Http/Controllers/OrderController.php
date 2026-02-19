<?php
// app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    protected OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orders = $this->service->getAll($request->user()->id);
        return $this->paginatedResponse($orders);
    }

    public function store(Request $request)
    {
        try {
            $order = $this->service->create(
                $request->user()->id,
                $request->all()
            );
            
            return $this->successResponse($order, 'Pesanan berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function updateStatus($id, Request $request)
    {
        try {
            $order = $this->service->updateStatus(
                $id,
                $request->user()->id,
                $request->status
            );
            
            return $this->successResponse($order, 'Status berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}