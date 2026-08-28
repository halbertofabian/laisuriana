<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json([
                'data' => [
                    'status' => 'ok',
                    'service' => 'suriana-mobile-api',
                    'version' => 'v1',
                ],
            ])
            ->header('Cache-Control', 'no-store');
    }
}
