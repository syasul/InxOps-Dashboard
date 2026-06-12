<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServerStatsService;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    protected $statsService;

    public function __construct(ServerStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function index()
    {
        return response()->json($this->statsService->getStats());
    }
}
