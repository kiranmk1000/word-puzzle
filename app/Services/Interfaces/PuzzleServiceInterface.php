<?php

namespace App\Services\Interfaces;

interface PuzzleServiceInterface
{
    /**
     * Get the dictionary of valid words
     *
     * @return array
     */
    public function getDictionary(): array;

    /**
     * Generate a 14-letter string containing at least one valid word
     * and store it in the database
     *
     * @return string
     */
    public function generatePuzzle(): string;

    /**
     * Check if the given string contains at least one valid word (anagram-style)
     *
     * @param string $puzzle
     * @return bool
     */
    public function containsValidWord(string $puzzle): bool;
}
