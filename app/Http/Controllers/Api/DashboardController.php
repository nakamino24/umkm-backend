<?php
// app/Http/Controllers/Api/DashboardController.php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    protected DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function stats(Request $request)
    {
        $stats = $this->service->getStats($request->user()->id);
        return $this->successResponse($stats);
    }
}