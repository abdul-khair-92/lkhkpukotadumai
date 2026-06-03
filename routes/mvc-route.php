<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('mvc.route_prefix')], function () { // remove this line if you dont have route group prefix
    Route::group(['middleware' => ['userRoles']], function () {
        // l-k-h
        Route::prefix('l-k-h')->group(function () {
            Route::get('data/{bulan?}/{tahun?}', 'LKH\LKHController@data')->name('l-k-h.data');
            Route::get('delete/{id}', 'LKH\LKHController@delete')->name('l-k-h.delete');
            Route::post('pengajuan', 'LKH\LKHController@submitPengajuan')->name('l-k-h.pengajuan');
            Route::get('laporan-approved-pdf', 'LKH\LKHController@downloadApprovedPdf')->name('l-k-h.laporan-approved-pdf');
            Route::get('picker-config', 'LKH\LKHController@pickerConfig')->name('l-k-h.picker-config');
            Route::get('generate-pdf', 'LKH\LKHController@generatePdf')->name('l-k-h.generate-pdf');
            Route::post('upload-kpu-sekretaris', 'LKH\LKHController@uploadSekretarisKpu')->name('l-k-h.upload-kpu-sekretaris');
        });
        Route::resource('l-k-h', 'LKH\LKHController');
        // end-l-k-h
        // pengajuan LKH bulanan (atasan / root)
        Route::prefix('lkh-pengajuan')->as('lkh-pengajuan.')->middleware('lkhPengajuanAccess')->group(function () {
            Route::get('/', 'LkhPengajuan\LkhPengajuanController@index')->name('index');
            Route::get('data', 'LkhPengajuan\LkhPengajuanController@data')->name('data');
            Route::get('laporan/{id}', 'LkhPengajuan\LkhPengajuanController@laporan')->name('laporan');
            Route::post('{id}/approve', 'LkhPengajuan\LkhPengajuanController@approve')->name('approve');
            Route::post('{id}/revisi', 'LkhPengajuan\LkhPengajuanController@revisi')->name('revisi');
            Route::post('{id}/batalkan', 'LkhPengajuan\LkhPengajuanController@batalkan')->name('batalkan');
        });
        // end pengajuan LKH
        // rekap LKH
        Route::prefix('lkh-rekap')->as('lkh-rekap.')->group(function () {
            Route::get('/', 'LkhRekap\LkhRekapController@index')->name('index');
            Route::get('data/{bulan}/{tahun}', 'LkhRekap\LkhRekapController@data')->name('data');
            Route::get('pdf/{bulan}/{tahun}', 'LkhRekap\LkhRekapController@exportPdf')->name('pdf');
        });
        // end rekap LKH
        // hari libur LKH
        Route::prefix('lkh-hari-libur')->group(function () {
            Route::get('data', 'LkhHariLibur\LkhHariLiburController@data')->name('lkh-hari-libur.data');
            Route::get('delete/{id}', 'LkhHariLibur\LkhHariLiburController@delete')->name('lkh-hari-libur.delete');
        });
        Route::resource('lkh-hari-libur', 'LkhHariLibur\LkhHariLiburController');
        // end hari libur LKH
        // monitoring LKH bawahan
        Route::prefix('lkh-monitoring')->as('lkh-monitoring.')->middleware('lkhMonitoringAccess')->group(function () {
            Route::get('/', 'LkhMonitoring\LkhMonitoringController@index')->name('index');
            Route::get('data', 'LkhMonitoring\LkhMonitoringController@data')->name('data');
            Route::get('detail/{pegawaiId}/{bulan}/{tahun}', 'LkhMonitoring\LkhMonitoringController@detail')->name('detail');
        });
        // end monitoring LKH bawahan
        // {{route replacer}} DON'T REMOVE THIS LINE
    });
});
