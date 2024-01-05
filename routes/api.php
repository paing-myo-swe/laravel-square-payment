<?php

use App\Http\Controllers\CardPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

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

Route::get('/locations', [PaymentController::class, 'getLocations']);
Route::get('/customers', [PaymentController::class, 'getCustomers']);
Route::get('/cards', [PaymentController::class, 'getCards']);

Route::get('/payments', [PaymentController::class, 'getPayments']);
Route::post('/payments', [PaymentController::class, 'createPayment']);

Route::get('/payment-links', [PaymentController::class, 'getPaymentLinks']);
Route::post('/payment-links', [PaymentController::class, 'createPaymentLink']);
Route::get('/payment-links/{id}', [PaymentController::class, 'getPaymentLinkDetails']);

Route::post('/orders', [PaymentController::class, 'createOrder']);
Route::get('/orders/{id}', [PaymentController::class, 'getOrderDetails']);

Route::post('card-payment', [CardPaymentController::class, 'showPaymentForm']);

