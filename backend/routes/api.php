<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Weather API Routes (No Login Required)
|--------------------------------------------------------------------------
*/
Route::prefix('weather')->group(function () {
    Route::get('/current', [WeatherController::class, 'current']);
    Route::get('/hourly', [WeatherController::class, 'hourly']);
    Route::get('/daily', [WeatherController::class, 'daily']);
    Route::get('/search', [WeatherController::class, 'search']);
    Route::get('/location', [WeatherController::class, 'location']);
    Route::get('/indonesia-regions', [WeatherController::class, 'indonesiaRegions']);
});

Route::prefix('cities')->group(function () {
    Route::get('/featured', [CityController::class, 'featured']);
});

/*
|--------------------------------------------------------------------------
| Disaster Monitoring API Routes (BMKG & Flood Telemetry)
|--------------------------------------------------------------------------
*/
Route::prefix('disaster')->group(function () {
    Route::get('/earthquakes', [\App\Http\Controllers\Api\DisasterController::class, 'earthquakes']);
    Route::get('/earthquakes/latest', [\App\Http\Controllers\Api\DisasterController::class, 'latestEarthquake']);
    Route::get('/earthquakes/nearby', [\App\Http\Controllers\Api\DisasterController::class, 'nearbyEarthquakes']);
    Route::get('/floods', [\App\Http\Controllers\Api\DisasterController::class, 'floods']);
    Route::get('/floods/nearby', [\App\Http\Controllers\Api\DisasterController::class, 'nearbyFloods']);
    Route::get('/status', [\App\Http\Controllers\Api\DisasterController::class, 'status']);
});

/*
|--------------------------------------------------------------------------
| Traffic Monitoring API Routes (Indonesia Roads, Congestion & Regions)
|--------------------------------------------------------------------------
*/
Route::prefix('traffic')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\TrafficController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\Api\TrafficController::class, 'search']);
    Route::get('/hierarchy', [\App\Http\Controllers\Api\TrafficController::class, 'hierarchy']);
    Route::get('/area/{id}', [\App\Http\Controllers\Api\TrafficController::class, 'area']);
});

/*
|--------------------------------------------------------------------------
| CCTV Traffic Monitoring API Routes (ATCS / Dishub Aggregator)
|--------------------------------------------------------------------------
*/
Route::prefix('cctv')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CctvController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\Api\CctvController::class, 'search']);
    Route::get('/sources', [\App\Http\Controllers\Api\CctvController::class, 'sources']);
    Route::get('/{id}', [\App\Http\Controllers\Api\CctvController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Unified Indonesia Monitoring Hub Telemetry
|--------------------------------------------------------------------------
*/
Route::prefix('monitoring')->group(function () {
    Route::get('/overview', [\App\Http\Controllers\Api\MonitoringController::class, 'overview']);
});

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Protected Admin Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        // Dashboard stats & logs
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/logs', [AdminDashboardController::class, 'logs']);

        // Featured Cities Management
        Route::get('/featured-cities', [AdminDashboardController::class, 'featuredCities']);
        Route::post('/featured-cities', [AdminDashboardController::class, 'storeCity']);
        Route::put('/featured-cities/{id}', [AdminDashboardController::class, 'updateCity']);
        Route::delete('/featured-cities/{id}', [AdminDashboardController::class, 'deleteCity']);

        // App Settings Management
        Route::get('/settings', [AdminDashboardController::class, 'getSettings']);
        Route::post('/settings', [AdminDashboardController::class, 'updateSettings']);
    });
});
