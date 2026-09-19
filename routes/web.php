<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('enrollments.index');
});

Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
