<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\LkhController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->name('api.login');
Route::get('/holidays', [LkhController::class, 'holidays'])->name('holidays.public');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('api.logout');

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/lkh/picker-config', [LkhController::class, 'pickerConfig'])->name('lkh.picker-config');
    Route::get('/lkh/holidays', [LkhController::class, 'holidays'])->name('lkh.holidays');
    Route::get('/lkh/check-date', [LkhController::class, 'checkDate'])->name('lkh.check-date');
    Route::apiResource('lkh', LkhController::class);
});
