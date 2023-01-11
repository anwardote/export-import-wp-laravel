<?php


use \Illuminate\Support\Facades\Route;
use Anwardote\ExportImportWpLaravel\Http\Controllers\ExportController;

Route::get('/export', [ExportController::class, 'index']);
