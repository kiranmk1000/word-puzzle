<?php

namespace App\Services;

use App\Models\Puzzle;
use App\Models\Student;
use App\Models\Submission;
use App\Models\Leaderboard;
use App\Services\Interfaces\WordSubmissionServiceInterface;
use App\Services\Interfaces\PuzzleServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class WordSubmissionService implements WordSubmissionServiceInterface
{
    private array $dictionary;
    private PuzzleServiceInterface $puzzleService;

    public function __construct(PuzzleServiceInterface $puzzleService)
    {
        $this->puzzleService = $puzzleService;
        $this->dictionary = $this->puzzleService->getDictionary();
    }

    /**
     * Submit a word for scoring
     *
     * @param int $studentId
     * @param int $puzzleId
     * @param string $word
     * @return array
     */
    public function submitWord(int $studentId, int $puzzleId, string $word): array
    {
        // Get the puzzle with eager loading
        $puzzle = Puzzle::with(['submissions' => function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        }])->findOrFail($puzzleId);

        // Get student's previous submissions for this puzzle (already loaded)
        $previousSubmissions = $puzzle->submissions->pluck('word')->toArray();

        // Validate the word
        $validationResult = $this->validateWord($word, $puzzle->letters, $previousSubmissions);
        if (!$validationResult['valid']) {
            return $validationResult;
        }

        // Calculate score
        $score = strlen($word);

        // Create submission using DB transaction for data consistency
        DB::beginTransaction();
        try {
            $submission = Submission::create([
                'student_id' => $studentId,
                'puzzle_id' => $puzzleId,
                'word' => $word,
                'score' => $score
            ]);

            // Update leaderboard if score is high enough
            $this->updateLeaderboard($word, $score);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Get remaining letters
        $remainingLetters = $this->getRemainingLetters($puzzle->letters, $previousSubmissions, $word);

        // Check if there are any valid words remaining
        $remainingWords = $this->findRemainingValidWords($remainingLetters);

        return [
            'valid' => true,
            'score' => $score,
            'remaining_letters' => $remainingLetters,
            'remaining_words' => $remainingWords,
            'message' => 'Word submitted successfully!'
        ];
    }

    /**
     * Get the top 10 highest-scoring submissions
     *
     * @return array
     */
    public function getTopScores(): array
    {
        // Use cache for frequently accessed leaderboard data
        return cache()->remember('top_scores', 60, function () {
            return Leaderboard::orderBy('score', 'desc')
                ->take(10)
                ->get()
                ->toArray();
        });
    }

    /**
     * Validate a word submission
     *
     * @param string $word
     * @param string $puzzleLetters
     * @param array $previousSubmissions
     * @return array
     */
    private function validateWord(string $word, string $puzzleLetters, array $previousSubmissions): array
    {
        // Check if word was already submitted
        if (in_array($word, $previousSubmissions)) {
            return [
                'valid' => false,
                'message' => 'Word already submitted.'
            ];
        }

        // Check if word is in dictionary
        if (!in_array(strtolower($word), $this->dictionary)) {
            return [
                'valid' => false,
                'message' => 'Not a valid English word.'
            ];
        }

        // Check if word can be formed from remaining letters
        $remainingLetters = $this->getRemainingLetters($puzzleLetters, $previousSubmissions, '');
        if (!$this->canFormWord($word, $remainingLetters)) {
            return [
                'valid' => false,
                'message' => 'Word cannot be formed from remaining letters.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get remaining letters after using a word
     *
     * @param string $puzzleLetters
     * @param array $previousSubmissions
     * @param string $currentWord
     * @return string
     */
    private function getRemainingLetters(string $puzzleLetters, array $previousSubmissions, string $currentWord): string
    {
        $usedLetters = '';
        foreach ($previousSubmissions as $word) {
            $usedLetters .= $word;
        }
        $usedLetters .= $currentWord;

        $remaining = $puzzleLetters;
        for ($i = 0; $i < strlen($usedLetters); $i++) {
            $pos = strpos($remaining, $usedLetters[$i]);
            if ($pos !== false) {
                $remaining = substr_replace($remaining, '', $pos, 1);
            }
        }

        return $remaining;
    }

    /**
     * Check if a word can be formed from the given letters
     *
     * @param string $word
     * @param string $letters
     * @return bool
     */
    private function canFormWord(string $word, string $letters): bool
    {
        $wordLetters = count_chars(strtolower($word), 1);
        $availableLetters = count_chars(strtolower($letters), 1);

        foreach ($wordLetters as $char => $count) {
            if (!isset($availableLetters[$char]) || $availableLetters[$char] < $count) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find all valid words that can be formed from remaining letters
     *
     * @param string $letters
     * @return array
     */
    private function findRemainingValidWords(string $letters): array
    {
        $validWords = [];
        foreach ($this->dictionary as $word) {
            if ($this->canFormWord($word, $letters)) {
                $validWords[] = $word;
            }
        }
        return $validWords;
    }

    /**
     * Update the leaderboard with a new high score
     *
     * @param string $word
     * @param int $score
     * @return void
     */
    private function updateLeaderboard(string $word, int $score): void
    {
        // Use DB transaction for data consistency
        DB::beginTransaction();
        try {
            // Check if word already exists in leaderboard
            $existingEntry = Leaderboard::where('word', $word)->first();

            if ($existingEntry) {
                // Only update if new score is higher
                if ($score > $existingEntry->score) {
                    $existingEntry->update(['score' => $score]);
                    // Clear the cache when leaderboard is updated
                    cache()->forget('top_scores');
                }
            } else {
                // Check if we need to remove lowest score to make room
                $lowestScore = Leaderboard::orderBy('score', 'asc')->first();
                if ($lowestScore && Leaderboard::count() >= 10) {
                    if ($score > $lowestScore->score) {
                        $lowestScore->delete();
                        Leaderboard::create([
                            'word' => $word,
                            'score' => $score
                        ]);
                        // Clear the cache when leaderboard is updated
                        cache()->forget('top_scores');
                    }
                } else {
                    Leaderboard::create([
                        'word' => $word,
                        'score' => $score
                    ]);
                    // Clear the cache when leaderboard is updated
                    cache()->forget('top_scores');
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
