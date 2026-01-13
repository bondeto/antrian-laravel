<?php

use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// KIOSK
Route::get('/', [QueueController::class, 'kiosk'])->name('kiosk.index');
Route::post('/queue', [QueueController::class, 'store'])->name('queue.store');

// MONITOR
Route::get('/monitor/{floor_id}', [QueueController::class, 'monitor'])->name('monitor.show');

// OPERATOR (Protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect('/operator');
    })->name('dashboard');

    Route::get('/operator', [OperatorController::class, 'index'])->name('operator.index');
    Route::get('/operator/{counter}', [OperatorController::class, 'work'])->name('operator.work');
    Route::post('/operator/{counter}/call', [OperatorController::class, 'callNext'])->name('operator.call');
    
    // Actions on specific queue
    Route::post('/operator/queue/{queue}/recall', [OperatorController::class, 'recall'])->name('operator.recall');
    Route::post('/operator/queue/{queue}/served', [OperatorController::class, 'served'])->name('operator.served');
    Route::post('/operator/queue/{queue}/skip', [OperatorController::class, 'skip'])->name('operator.skip');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
