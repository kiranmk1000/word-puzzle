<?php

namespace App\Http\Controllers;

use App\Models\Puzzle;
use App\Services\PuzzleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PuzzleController extends Controller
{
    private PuzzleService $puzzleService;

    public function __construct(PuzzleService $puzzleService)
    {
        $this->puzzleService = $puzzleService;
    }

    /**
     * Generate a new puzzle
     *
     * @return JsonResponse
     */
    public function generate(): JsonResponse
    {
        try {
            $puzzle = $this->puzzleService->generatePuzzle();

            return response()->json([
                'success' => true,
                'puzzle' => $puzzle,
                'message' => 'Puzzle generated successfully!'
            ]);
        } catch (QueryException $e) {
            Log::error('Database error while generating puzzle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to store puzzle in database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error generating puzzle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate puzzle'
            ], 500);
        }
    }

    /**
     * Get valid words for a puzzle.
     *
     * @param Puzzle $puzzle
     * @return JsonResponse
     */
    public function validWords(Puzzle $puzzle): JsonResponse
    {
        try {
            $validWords = $this->puzzleService->getValidWords($puzzle->letters);

            return response()->json([
                'success' => true,
                'puzzle_id' => $puzzle->id,
                'valid_words' => $validWords
            ]);
        } catch (Exception $e) {
            Log::error('Error retrieving valid words: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve valid words'
            ], 500);
        }
    }
}
