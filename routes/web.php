<?php

use App\Http\Controllers\NbsController as C;
use Illuminate\Support\Facades\Route;

Route::get('/', [C::class, 'overview'])->name('overview');
Route::post('/version/{v}', [C::class, 'version'])->name('version');

Route::get('/modules/{code}', [C::class, 'module'])->name('module');
Route::get('/process/{process}', [C::class, 'process'])->name('process');

Route::get('/customer', [C::class, 'customer'])->name('customer');
Route::post('/customer/{account}/adjustment', [C::class, 'storeAdjustment'])->name('adjustment.store');

Route::get('/receipting', [C::class, 'receipting'])->name('receipting');
Route::post('/receipting', [C::class, 'storeReceipt'])->name('receipt.store');
Route::post('/receipting/{receipt}/void', [C::class, 'voidReceipt'])->name('receipt.void');

Route::get('/billing', [C::class, 'billing'])->name('billing');
Route::post('/billing/run', [C::class, 'runBilling'])->name('billing.run');

Route::get('/debt', [C::class, 'debt'])->name('debt');
Route::post('/debt/arrangement', [C::class, 'storeArrangement'])->name('arrangement.store');

Route::get('/enquiries', [C::class, 'enquiries'])->name('enquiries');
Route::post('/enquiries', [C::class, 'storeEnquiry'])->name('enquiry.store');
Route::post('/enquiries/{enquiry}/transition', [C::class, 'transitionEnquiry'])->name('enquiry.transition');

Route::get('/forecasting', [C::class, 'forecasting'])->name('forecasting');
Route::post('/forecasting/compute', [C::class, 'computeForecast'])->name('forecast.compute');
Route::post('/forecasting/{forecast}/freeze', [C::class, 'freezeForecast'])->name('forecast.freeze');
