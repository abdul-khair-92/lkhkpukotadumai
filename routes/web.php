<?php

use App\Http\Controllers\jsController;
use App\Http\Controllers\LkhDokumenVerifikasiController;
use Illuminate\Support\Facades\Route;

Route::get('lkh-dokumen/pengaju/{token}', [LkhDokumenVerifikasiController::class, 'pengaju'])->name('lkh-dokumen.pengaju');
Route::get('lkh-dokumen/penyetuju/{token}', [LkhDokumenVerifikasiController::class, 'penyetuju'])->name('lkh-dokumen.penyetuju');
Route::get('lkh-dokumen/{token}', [LkhDokumenVerifikasiController::class, 'show'])->name('lkh-dokumen.verifikasi');

Route::prefix('js')->as('js')->group(function () {
    Route::any('/{layout}/{page}/{file}', [jsController::class, 'javaScript']);
});

Route::get('/', fn () => redirect()->route('login'));
Route::get('login', 'Backend\Auth\AuthController@formLogin')->name('login');
Route::get('register', 'Backend\Auth\AuthController@formRegister')->name('register');
Route::post('sign-in', 'Backend\Auth\AuthController@login')->name('sign-in');
Route::post('sign-up', 'Backend\Auth\AuthController@register')->name('sign-up');

Route::middleware(['auth', 'backend'])->group(function () {
    Route::get('/profile', [\App\Http\Controllers\Backend\ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/photo', [\App\Http\Controllers\Backend\ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::get('/integration-api-docs', [\App\Http\Controllers\Backend\ProfileController::class, 'apiDocs'])->name('api-docs.index');
});
