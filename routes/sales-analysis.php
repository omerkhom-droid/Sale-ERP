<?php

use App\Http\Controllers\SalesAnalysisController;
use Illuminate\Support\Facades\Route;

// Included inside the existing authenticated, licensed application route group.
Route::prefix('sales-analysis')->name('sales-analysis.')
    ->middleware(['permission:sales_reports.view', 'permission:sales_profit_reports.view'])
    ->group(function () {
        Route::get('/attributions/{invoice}', [SalesAnalysisController::class, 'attribution'])
            ->whereNumber('invoice')->middleware('permission:sales_analysis.assign')->name('attribution');
        Route::patch('/attributions/{invoice}', [SalesAnalysisController::class, 'assign'])
            ->whereNumber('invoice')->middleware('permission:sales_analysis.assign')->name('assign');
        Route::get('/{kind}/data', [SalesAnalysisController::class, 'data'])->name('data');
        Route::get('/{kind}/print', [SalesAnalysisController::class, 'print'])->name('print');
        Route::get('/{kind}/export', [SalesAnalysisController::class, 'export'])->name('export');
        Route::get('/{kind}', [SalesAnalysisController::class, 'index'])->name('index');
    });
