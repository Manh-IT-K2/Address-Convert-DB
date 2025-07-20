<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressConverterController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/address-converter', [AddressConverterController::class, 'showConverter'])->name('address.converter');
Route::post('/address-convert', [AddressConverterController::class, 'convertAddresses'])->name('address.convert');

Route::get('/test-convert', function () {
    return view('test-convert');
});

Route::post('/test-convert', [AddressConverterController::class, 'handleTestConvert'])->name('test.convert.address');
