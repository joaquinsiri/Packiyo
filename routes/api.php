<?php

use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use LaravelJsonApi\Laravel\Routing\Relationships;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

JsonApiRoute::server('v1')->prefix('v1')->resources(function (ResourceRegistrar $server) {
    $server->resource('orders', OrderController::class)
        ->only('show', 'store')
        ->relationships(function (Relationships $relations) {
            $relations->hasOne('customer');
            $relations->hasMany('lineItems');
        });
});
