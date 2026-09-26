<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::post('/transactions/analyze-proof', [TransactionController::class, 'analyzeProof'])->name('transactions.analyze-proof');
Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
Route::patch('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');
Route::post('/transactions/{transaction}/reprint', [TransactionController::class, 'reprint'])->name('transactions.reprint');
Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
Route::patch('/printers/{printer}/status', [TransactionController::class, 'printerStatus'])->name('printers.status');
Route::post('/management/customer', [ManagementController::class, 'customer'])->name('management.customer');
Route::post('/management/category', [ManagementController::class, 'category'])->name('management.category');
Route::post('/management/template', [ManagementController::class, 'template'])->name('management.template');
Route::post('/management/printer', [ManagementController::class, 'printer'])->name('management.printer');
Route::delete('/management/{type}/{id}', [ManagementController::class, 'destroy'])->name('management.destroy');
