<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordSubmissionController;
use App\Http\Controllers\PuzzleController;
use App\Http\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Student routes
Route::apiResource('students', StudentController::class);
Route::get('/students/{student}/submissions', [StudentController::class, 'submissions']);
Route::get('/students/{student}/score', [StudentController::class, 'score']);

// Puzzle routes
Route::post('/generate-puzzle', [PuzzleController::class, 'generate']);
Route::get('/puzzles/{puzzle}/valid-words', [PuzzleController::class, 'validWords']);

// Word submission routes
Route::post('/submit-word', [WordSubmissionController::class, 'submit']);
Route::get('/leaderboard', [WordSubmissionController::class, 'leaderboard']);
