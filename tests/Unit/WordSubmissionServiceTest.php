<?php

namespace Tests\Unit;

use App\Models\Puzzle;
use App\Models\Student;
use App\Models\Submission;
use App\Services\PuzzleService;
use App\Services\WordSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private WordSubmissionService $service;
    private PuzzleService $puzzleService;
    private Student $student;
    private Puzzle $puzzle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->puzzleService = new PuzzleService();
        $this->service = new WordSubmissionService($this->puzzleService);

        // Create a test student
        $this->student = Student::create([
            'name' => 'Test Student'
        ]);

        // Create a test puzzle
        $this->puzzle = Puzzle::create([
            'letters' => 'dgeftoikbvxuaa',
            'is_active' => true
        ]);
    }

    public function test_can_submit_valid_word()
    {
        $result = $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'get'
        );

        //var_dump($result); // Debug output

        $this->assertTrue($result['valid']);
        $this->assertEquals(3, $result['score']);
        $this->assertArrayHasKey('remaining_letters', $result);
        $this->assertArrayHasKey('remaining_words', $result);
        $this->assertEquals('Word submitted successfully!', $result['message']);

        // Check if submission was created
        $this->assertDatabaseHas('submissions', [
            'student_id' => $this->student->id,
            'puzzle_id' => $this->puzzle->id,
            'word' => 'get',
            'score' => 3
        ]);
    }

    public function test_cannot_submit_invalid_word()
    {
        $result = $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'XYZ'
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('Not a valid English word.', $result['message']);
    }

    public function test_cannot_submit_duplicate_word()
    {
        // Submit word first time
        $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'get'
        );

        // Try to submit same word again
        $result = $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'get'
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('Word already submitted.', $result['message']);
    }

    public function test_cannot_submit_word_with_unavailable_letters()
    {
        $result = $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'over'
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('Word cannot be formed from remaining letters.', $result['message']);
    }

    public function test_can_get_top_scores()
    {
        // Submit some words
        $this->service->submitWord($this->student->id, $this->puzzle->id, 'get');
        $this->service->submitWord($this->student->id, $this->puzzle->id, 'fox');

        $scores = $this->service->getTopScores();

        $this->assertIsArray($scores);
        $this->assertNotEmpty($scores);
        $this->assertLessThanOrEqual(10, count($scores));

        // Verify scores are ordered by score descending
        $previousScore = PHP_INT_MAX;
        foreach ($scores as $score) {
            $this->assertLessThanOrEqual($previousScore, $score['score']);
            $previousScore = $score['score'];
        }
    }

    public function test_submission_updates_leaderboard()
    {
        // Submit a word
        $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'get'
        );

        // Check if word was added to leaderboard
        $this->assertDatabaseHas('leaderboards', [
            'word' => 'get',
            'score' => 3
        ]);
    }

    public function test_submission_tracks_remaining_letters()
    {
        // Submit a word
        $result = $this->service->submitWord(
            $this->student->id,
            $this->puzzle->id,
            'get'
        );

        $this->assertArrayHasKey('remaining_letters', $result);
        $this->assertIsString($result['remaining_letters']);

        // Verify remaining letters don't contain used letters
        $usedLetters = str_split('get');
        foreach ($usedLetters as $letter) {
            $this->assertStringNotContainsString($letter, $result['remaining_letters']);
        }
    }
}
