<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ForecastService;

class ForecastController extends Controller
{
    public function index(ForecastService $forecastService)
    {
        $historicalData = $forecastService->getHistoricalData();

        return view('admin.forecasting', $historicalData);
    }
}