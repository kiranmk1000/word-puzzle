<?php

namespace App\Services;

use App\Models\Puzzle;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Services\Interfaces\PuzzleServiceInterface;
use Exception;
use RuntimeException;

class PuzzleService implements PuzzleServiceInterface
{
    private array $dictionary;
    private const LETTERS = 'abcdefghijklmnopqrstuvwxyz';

    public function __construct()
    {
        $this->dictionary = $this->loadDictionary();
    }

    /**
     * Get the dictionary of valid words
     *
     * @return array
     */
    public function getDictionary(): array
    {
        return $this->dictionary;
    }

    /**
     * Generate a 14-letter string containing at least one valid word
     * and store it in the database
     *
     * @return string
     */
    public function generatePuzzle(): string
    {
        do {
            // Generate a random string of 14 letters
            $puzzle = '';
            for ($i = 0; $i < 14; $i++) {
                $puzzle .= self::LETTERS[random_int(0, strlen(self::LETTERS) - 1)];
            }
        } while (!$this->containsValidWord($puzzle));

        try {
            Puzzle::create(['letters' => $puzzle]);
            return $puzzle;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to store puzzle: ' . $e->getMessage());
        }
    }

    /**
     * Check if the given string contains at least one valid word (anagram-style)
     *
     * @param string $puzzle
     * @return bool
     */
    public function containsValidWord(string $puzzle): bool
    {
        $puzzleLetters = count_chars(strtolower($puzzle), 1);
        foreach ($this->dictionary as $word) {
            if (strlen($word) > strlen($puzzle)) {
                continue;
            }
            $wordLetters = count_chars($word, 1);
            $canForm = true;
            foreach ($wordLetters as $char => $count) {
                if (!isset($puzzleLetters[$char]) || $puzzleLetters[$char] < $count) {
                    $canForm = false;
                    break;
                }
            }
            if ($canForm) {
                return true;
            }
        }
        return false;
    }

    /**
     * Load dictionary from our custom word list file
     *
     * @return array
     */
    private function loadDictionary(): array
    {
        $path = __DIR__ . '/words.json';

        if (!file_exists($path)) {
            throw new \RuntimeException('Word list file not found at: ' . $path);
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!isset($data['words']) || !is_array($data['words'])) {
            throw new \RuntimeException('Invalid word list format');
        }

        return array_map('strtolower', $data['words']);
    }

    /**
     * Get all valid words that can be formed from the puzzle letters.
     *
     * @param string $puzzle
     * @return array
     */
    public function getValidWords(string $puzzle): array
    {
        $validWords = [];
        $puzzleLetters = str_split(strtolower($puzzle));
        $letterCounts = array_count_values($puzzleLetters);

        foreach ($this->dictionary as $word) {
            if ($this->canFormWord($word, $letterCounts)) {
                $validWords[] = $word;
            }
        }

        return $validWords;
    }

    /**
     * Check if a word can be formed from the given letter counts.
     *
     * @param string $word
     * @param array $letterCounts
     * @return bool
     */
    private function canFormWord(string $word, array $letterCounts): bool
    {
        $wordLetters = str_split($word);
        $wordLetterCounts = array_count_values($wordLetters);

        foreach ($wordLetterCounts as $letter => $count) {
            if (!isset($letterCounts[$letter]) || $letterCounts[$letter] < $count) {
                return false;
            }
        }

        return true;
    }
}
