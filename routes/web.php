<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('enrollments.index');
});

Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update');
Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
