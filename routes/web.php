<?php

use App\Http\Controllers\CardPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('cards', [CardPaymentController::class, 'myCards'])->name('cards.index');
Route::get('cards/create', [CardPaymentController::class, 'showCardForm'])->name('cards.create');
Route::post('cards', [CardPaymentController::class, 'addNewCard'])->name('cards.store');
Route::post('/pay', [CardPaymentController::class, 'pay'])->name('pay');

// Route::get('card-payment', [CardPaymentController::class, 'showPaymentForm']);
// Route::post('/payment', [CardPaymentController::class, 'createPayment'])->name('payment');

