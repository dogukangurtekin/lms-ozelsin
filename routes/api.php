<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\WhatsappController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('books', BookController::class)->names('api.books');
    Route::apiResource('assignments', AssignmentController::class)->names('api.assignments');
    Route::apiResource('meetings', MeetingController::class)->names('api.meetings');

    Route::post('assignments/{assignment}/submit', [AssignmentController::class, 'submit']);
    Route::post('assignment-submissions/{submission}/grade', [AssignmentController::class, 'grade']);

    Route::post('whatsapp/send', [WhatsappController::class, 'send'])->middleware('throttle:20,1');
});
