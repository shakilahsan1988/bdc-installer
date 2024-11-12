<?php

use Bdc\Installer\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'install', 'as' => 'BdcInstaller::', 'middleware' => ['web']], function () {
    Route::get('/', [InstallController::class, 'checkConfiguration'])->name('check.configuration');
    Route::post('configuration/validation', [InstallController::class, 'checkValidation'])->name('check-validation');
    Route::get('/process', [InstallController::class, 'installationProcess'])->name('installation.process');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/process-store', [InstallController::class, 'processStore'])->name('process.store');
});
