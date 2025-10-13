<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhooks/transcription-updates', [WebhookController::class, 'handle'])
    ->middleware('webhook.signature'); 



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/transcription', [TranscriptionController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);

});