<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $students = Student::all();
            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (QueryException $e) {
            Log::error('Database error while retrieving students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students from database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error retrieving students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students'
            ], 500);
        }
    }

    /**
     * Store a newly created student in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $student = Student::create($validated);

            return response()->json([
                'success' => true,
                'student' => $student,
                'message' => 'Student created successfully!'
            ], 201);
        } catch (ValidationException $e) {
            Log::warning('Validation failed for student creation: ' . json_encode($request->all()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error while creating student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student in database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error creating student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student'
            ], 500);
        }
    }

    /**
     * Display the specified student.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($id);
            return response()->json([
                'success' => true,
                'student' => $student
            ]);
        } catch (QueryException $e) {
            Log::error('Database error while retrieving student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student from database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error retrieving student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
    }

    /**
     * Update the specified student in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $student = Student::findOrFail($id);
            $student->update($validated);

            return response()->json([
                'success' => true,
                'student' => $student,
                'message' => 'Student updated successfully!'
            ]);
        } catch (ValidationException $e) {
            Log::warning('Validation failed for student update: ' . json_encode($request->all()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error while updating student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student in database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error updating student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
    }

    /**
     * Remove the specified student from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($id);
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully!'
            ]);
        } catch (QueryException $e) {
            Log::error('Database error while deleting student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student from database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error deleting student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
    }

    /**
     * Get all submissions for a student.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function submissions(Student $student): JsonResponse
    {
        $submissions = Submission::with(['puzzle'])
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($submission) {
                return [
                    'puzzle_id' => $submission->puzzle_id,
                    'word' => $submission->word,
                    'score' => $submission->score,
                    'created_at' => $submission->created_at,
                    'puzzle_letters' => $submission->puzzle->letters
                ];
            });

        return response()->json($submissions);
    }

    /**
     * Get total score for a student.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function score(Student $student): JsonResponse
    {
        try {
            $totalScore = Submission::where('student_id', $student->id)
                ->sum('score');

            return response()->json([
                'success' => true,
                'student_id' => $student->id,
                'total_score' => $totalScore
            ]);
        } catch (QueryException $e) {
            Log::error('Database error while retrieving student score: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student score from database'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error retrieving student score: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student score'
            ], 500);
        }
    }
}
