<?php

use App\Http\Controllers\Api\OperatorApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('operator')->group(function () {
    Route::get('/status/{counterId}', [OperatorApiController::class, 'status']);
    Route::post('/call/{counterId}', [OperatorApiController::class, 'callNext']);
    Route::post('/queue/{queueId}/recall', [OperatorApiController::class, 'recall']);
    Route::post('/queue/{queueId}/served', [OperatorApiController::class, 'served']);
    Route::post('/queue/{queueId}/skip', [OperatorApiController::class, 'skip']);
});
