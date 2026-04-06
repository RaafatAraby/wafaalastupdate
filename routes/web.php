<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportsExportController;



Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->get('/admin/reports/export', ReportsExportController::class)
    ->name('reports.export');