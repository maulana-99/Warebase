<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('master')->where(['type' => 'categories|locations|suppliers|departments', 'id' => '[0-9]+'])->group(function () {
        Route::redirect('/', '/master/categories')->name('master.index');
        Route::get('{type}', [MasterDataController::class, 'list'])->name('master.list');

        Route::middleware('role:admin')->group(function () {
            Route::get('{type}/create', [MasterDataController::class, 'create'])->name('master.create');
            Route::post('{type}', [MasterDataController::class, 'store'])->name('master.store');
            Route::get('{type}/{id}/edit', [MasterDataController::class, 'edit'])->name('master.edit');
            Route::put('{type}/{id}', [MasterDataController::class, 'update'])->name('master.update');
            Route::delete('{type}/{id}', [MasterDataController::class, 'destroy'])->name('master.destroy');
        });
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
});
