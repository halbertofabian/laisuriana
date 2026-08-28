<?php

use App\Http\Controllers\Api\V1\Mobile\AuthController;
use App\Http\Controllers\Api\V1\Mobile\FloorOrderController;
use App\Http\Controllers\Api\V1\Mobile\HealthController;
use App\Http\Controllers\Api\V1\Mobile\OrderCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('v1/mobile/health', HealthController::class)
    ->middleware('throttle:60,1');

Route::prefix('v1/mobile/auth')->group(function (): void {
    Route::get('/usuarios', [AuthController::class, 'usuarios'])
        ->middleware('throttle:60,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/sesion', [AuthController::class, 'sesion']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('v1/mobile')
    ->middleware(['auth:sanctum', 'abilities:mobile:orders'])
    ->group(function (): void {
        Route::get('/order-context', [OrderCatalogController::class, 'context']);
        Route::get('/products', [OrderCatalogController::class, 'products']);
        Route::get('/products/{sku}/warehouses', [OrderCatalogController::class, 'warehouses']);
        Route::get('/clients', [OrderCatalogController::class, 'clients']);
        Route::get('/floor-orders', [FloorOrderController::class, 'index']);
        Route::post('/floor-orders', [FloorOrderController::class, 'store']);
        Route::get('/floor-orders/{order}', [FloorOrderController::class, 'show']);
        Route::put('/floor-orders/{order}', [FloorOrderController::class, 'update']);
        Route::delete('/floor-orders/{order}', [FloorOrderController::class, 'destroy']);
    });
