<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Enrollment::query()
            ->select([
                'enrollments.id',
                'enrollments.student_id',
                'enrollments.course_id',
                'enrollments.academic_year',
                'enrollments.semester',
                'enrollments.status',
                'enrollments.created_at',
            ])
            ->with([
                'student:id,nim,name',
                'course:id,code,name,credits',
            ]);

        $matchMode = strtoupper($request->input('match_mode', 'AND')) === 'OR' ? 'OR' : 'AND';

        // TS-07, TS-08, TS-09, TS-10: Filtering & Search Logic
        $hasSearch = $request->filled('search');
        $hasSemester = $request->filled('semester');
        $hasStatus = $request->filled('status');

        if ($hasSearch || $hasSemester || $hasStatus) {
            $query->where(function ($q) use ($request, $matchMode, $hasSearch, $hasSemester, $hasStatus) {
                $boolean = $matchMode === 'OR' ? 'orWhere' : 'where';

                if ($hasSearch) {
                    $search = $request->search;
                    $q->$boolean(function ($subQ) use ($search) {
                        $subQ->whereHas('student', function ($sq) use ($search) {
                            $sq->where('nim', 'LIKE', "{$search}%")
                                ->orWhere('name', 'ILIKE', "%{$search}%");
                        })->orWhereHas('course', function ($cq) use ($search) {
                            $cq->where('code', 'ILIKE', "{$search}%")
                                ->orWhere('name', 'ILIKE', "%{$search}%");
                        });
                    });
                }

                if ($hasSemester) {
                    $q->$boolean('semester', $request->semester);
                }

                if ($hasStatus) {
                    $q->$boolean('status', $request->status);
                }
            });
        }

        // TS-06: Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = strtolower($request->input('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sortField) {
            'student_nim' => $query->join('students', 'enrollments.student_id', '=', 'students.id')->orderBy('students.nim', $sortDirection),
            'student_name' => $query->join('students', 'enrollments.student_id', '=', 'students.id')->orderBy('students.name', $sortDirection),
            'course_code' => $query->join('courses', 'enrollments.course_id', '=', 'courses.id')->orderBy('courses.code', $sortDirection),
            'course_name' => $query->join('courses', 'enrollments.course_id', '=', 'courses.id')->orderBy('courses.name', $sortDirection),
            'academic_year' => $query->orderBy('academic_year', $sortDirection),
            'semester' => $query->orderBy('semester', $sortDirection),
            'status' => $query->orderBy('status', $sortDirection),
            default => $query->orderBy('enrollments.id', $sortDirection),
        };

        // TS-05: Server-Side Pagination
        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100], true) ? (int) $request->per_page : 10;
        $enrollments = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Enrollments/Index', [
            'enrollments' => $enrollments,
            'filters' => $request->only(['search', 'semester', 'status', 'match_mode', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }
}
