<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Route;

// Public (applicant-facing)
Route::get('/skills', [SkillController::class, 'index']);
Route::post('/applications', [ApplicationController::class, 'store']);

// Protected (reviewer-facing)
Route::middleware('auth0')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::patch('/applications/{application}/review', [ApplicationController::class, 'review']);
});
