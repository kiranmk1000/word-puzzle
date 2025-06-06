<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\WordSubmissionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class WordSubmissionController extends Controller
{
    private WordSubmissionServiceInterface $wordSubmissionService;

    public function __construct(WordSubmissionServiceInterface $wordSubmissionService)
    {
        $this->wordSubmissionService = $wordSubmissionService;
    }

    /**
     * Submit a word for scoring
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|integer|exists:students,id',
                'puzzle_id' => 'required|integer|exists:puzzles,id',
                'word' => 'required|string|min:2|max:14|regex:/^[a-zA-Z]+$/'
            ]);

            $result = $this->wordSubmissionService->submitWord(
                $validated['student_id'],
                $validated['puzzle_id'],
                $validated['word']
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            Log::warning('Validation failed for word submission: ' . json_encode($request->all()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error while submitting word: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to store submission in database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error submitting word: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit word'
            ], 500);
        }
    }

    /**
     * Get the top 10 highest-scoring submissions
     *
     * @return JsonResponse
     */
    public function leaderboard(): JsonResponse
    {
        try {
            $scores = $this->wordSubmissionService->getTopScores();
            return response()->json([
                'success' => true,
                'scores' => $scores
            ]);
        } catch (QueryException $e) {
            Log::error('Database error while retrieving leaderboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leaderboard from database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error retrieving leaderboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leaderboard'
            ], 500);
        }
    }
}
