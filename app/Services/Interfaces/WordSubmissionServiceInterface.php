<?php

namespace App\Services\Interfaces;

interface WordSubmissionServiceInterface
{
    /**
     * Submit a word for scoring
     *
     * @param int $studentId
     * @param int $puzzleId
     * @param string $word
     * @return array
     */
    public function submitWord(int $studentId, int $puzzleId, string $word): array;

    /**
     * Get the top 10 highest-scoring submissions
     *
     * @return array
     */
    public function getTopScores(): array;
}
