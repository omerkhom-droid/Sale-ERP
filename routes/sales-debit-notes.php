<?php

use App\Http\Controllers\SalesDebitNoteController;
use Illuminate\Support\Facades\Route;

// Require this file inside the existing sales routes' protected middleware group.
Route::prefix('sales-debit-notes')->name('sales-debit-notes.')->group(function () {
    Route::get('/', [SalesDebitNoteController::class, 'index'])->name('index');
    Route::get('/create', [SalesDebitNoteController::class, 'create'])->name('create');
    Route::post('/', [SalesDebitNoteController::class, 'store'])->name('store');
    Route::get('/{salesDebitNote}/print', [SalesDebitNoteController::class, 'print'])->name('print');
    Route::post('/{salesDebitNote}/post', [SalesDebitNoteController::class, 'post'])->name('post');
    Route::post('/{salesDebitNote}/cancel', [SalesDebitNoteController::class, 'cancel'])->name('cancel');
    Route::get('/{salesDebitNote}', [SalesDebitNoteController::class, 'show'])->name('show');
});
