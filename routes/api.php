<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', 'Auth\LoginController@login')->name('login');
Route::group(['middleware'=>['auth:sanctum']], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('lkh', App\Http\Controllers\Api\LkhController::class);
});
