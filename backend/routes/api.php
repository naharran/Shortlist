<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Route;

Route::get('/skills', [SkillController::class, 'index']);
Route::post('/applications', [ApplicationController::class, 'store']);
