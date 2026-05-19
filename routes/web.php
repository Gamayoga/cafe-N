<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('logout', function (App\Livewire\Actions\Logout $logout) {
        $logout();
        return redirect()->route('login');
    })->name('logout');
});

Route::redirect('/', '/login');

Route::get('dashboard', function () {
    if (auth()->user()->role === 'owner') {
        return redirect()->route('owner.dashboard');
    }
    return redirect()->route('cashier.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:pegawai'])->prefix('pos')->group(function () {
    Livewire\Volt\Volt::route('dashboard', 'cashier.dashboard')->name('cashier.dashboard');
    Livewire\Volt\Volt::route('kitchen', 'cashier.kds')->name('cashier.kds');
    Livewire\Volt\Volt::route('/', 'cashier.pos')->name('cashier.pos');
    Livewire\Volt\Volt::route('attendance', 'cashier.attendance')->name('cashier.attendance');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'role:owner'])->prefix('owner')->group(function () {
    Livewire\Volt\Volt::route('dashboard', 'owner.dashboard')->name('owner.dashboard');

    // Inventory Module
    Route::prefix('inventory')->group(function () {
        Livewire\Volt\Volt::route('suppliers', 'owner.inventory.suppliers')->name('owner.inventory.suppliers');
        Livewire\Volt\Volt::route('ingredients', 'owner.inventory.ingredients')->name('owner.inventory.ingredients');
        Livewire\Volt\Volt::route('products', 'owner.inventory.products')->name('owner.inventory.products');
        Livewire\Volt\Volt::route('history', 'owner.inventory.history')->name('owner.inventory.history');
    });

    // Management Module
    Livewire\Volt\Volt::route('employees', 'owner.employees')->name('owner.employees');
    Livewire\Volt\Volt::route('attendance', 'owner.attendance')->name('owner.attendance');
    Livewire\Volt\Volt::route('reports', 'owner.reports')->name('owner.reports');
    Route::get('reports/export/pdf', [\App\Http\Controllers\ReportExportController::class, 'exportPdf'])->name('owner.reports.export.pdf');
    Route::get('reports/export/excel', [\App\Http\Controllers\ReportExportController::class, 'exportExcel'])->name('owner.reports.export.excel');
});


require __DIR__.'/auth.php';
