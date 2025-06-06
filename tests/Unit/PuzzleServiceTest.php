<?php

namespace Tests\Unit;

use App\Services\PuzzleService;
use Tests\TestCase;

class PuzzleServiceTest extends TestCase
{
    private PuzzleService $puzzleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->puzzleService = new PuzzleService();
    }

    public function test_contains_valid_word()
    {
        $puzzle = 'dgeftoikbvxuaa';
        $this->assertTrue(
            $this->puzzleService->containsValidWord($puzzle),
            "The puzzle should contain the word 'fox'"
        );
    }

    public function test_contains_valid_word_with_duplicate_letters()
    {
        $puzzle = 'aabbccddeeffgg';
        $this->assertTrue(
            $this->puzzleService->containsValidWord($puzzle),
            "The puzzle should contain words with duplicate letters"
        );
    }

    public function test_does_not_contain_valid_word()
    {
        $puzzle = 'xyzxyzxyzxyzxy';
        $this->assertFalse(
            $this->puzzleService->containsValidWord($puzzle),
            "The puzzle should not contain any valid words"
        );
    }

    public function test_generate_puzzle_contains_valid_word()
    {
        $puzzle = $this->puzzleService->generatePuzzle();
        $this->assertTrue(
            $this->puzzleService->containsValidWord($puzzle),
            "Generated puzzle should contain at least one valid word"
        );
    }

    public function test_generate_puzzle_length()
    {
        $puzzle = $this->puzzleService->generatePuzzle();
        $this->assertEquals(14, strlen($puzzle), "Generated puzzle should be 14 characters long");
    }

    public function test_generate_puzzle_contains_only_letters()
    {
        $puzzle = $this->puzzleService->generatePuzzle();
        $this->assertTrue(
            ctype_alpha($puzzle),
            "Generated puzzle should contain only letters"
        );
    }

    public function test_dictionary_loading()
    {
        $dictionary = $this->puzzleService->getDictionary();
        $this->assertIsArray($dictionary, "Dictionary should be an array");
        $this->assertNotEmpty($dictionary, "Dictionary should not be empty");
        $this->assertTrue(
            array_reduce($dictionary, fn($carry, $item) => $carry && is_string($item), true),
            "Dictionary should contain only strings"
        );
    }

    public function test_dictionary_words_are_lowercase()
    {
        $dictionary = $this->puzzleService->getDictionary();
        foreach ($dictionary as $word) {
            $this->assertEquals(
                strtolower($word),
                $word,
                "Dictionary words should be lowercase"
            );
        }
    }
}
