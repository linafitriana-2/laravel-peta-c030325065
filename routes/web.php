<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SekolahController;

Route::get('/', function () {
    return view('welcome');
});
Route::prefix('admin')->name('admin.')->group(function () {
Route::resource('sekolah', SekolahController::class)->except('show');
});
