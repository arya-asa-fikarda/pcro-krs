<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100], true) ? (int) $request->per_page : 10;
        $enrollments = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Enrollments/Index', [
            'enrollments' => $enrollments,
            'filters' => $request->only(['search', 'semester', 'status', 'match_mode', 'sort_field', 'sort_direction', 'per_page']),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi Ketat Dual-Layer
        $validated = $request->validate([
            'student_nim' => ['required', 'string', 'regex:/^[0-9]{8,12}$/'],
            'student_name' => ['required', 'string', 'min:3', 'max:100'],
            'student_email' => ['required', 'email'],
            'course_code' => ['required', 'string', 'regex:/^[A-Z]{2,4}[0-9]{3}$/'],
            'course_name' => ['required', 'string', 'min:3', 'max:120'],
            'course_credits' => ['required', 'integer', 'min:1', 'max:6'],
            'academic_year' => ['required', 'string', 'regex:/^[0-9]{4}\/[0-9]{4}$/'],
            'semester' => ['required', 'in:GANJIL,GENAP'],
            'status' => ['required', 'in:DRAFT,SUBMITTED,APPROVED,REJECTED'],
        ], [
            'student_nim.required' => 'NIM wajib diisi.',
            'student_nim.regex' => 'NIM harus berupa 8-12 digit angka murni.',
            'student_name.required' => 'Nama Mahasiswa wajib diisi.',
            'student_email.required' => 'Email wajib diisi.',
            'student_email.email' => 'Format email tidak valid.',
            'course_code.required' => 'Kode MK wajib diisi.',
            'course_code.regex' => 'Kode MK harus 2-4 huruf kapital diikuti 3 angka (contoh: IF101).',
            'course_name.required' => 'Nama Mata Kuliah wajib diisi.',
            'academic_year.required' => 'Tahun Ajaran wajib diisi.',
            'academic_year.regex' => 'Tahun Ajaran harus berformat YYYY/YYYY (contoh: 2025/2026).',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Upsert Student dengan pencegahan email bentrok
                $student = Student::where('nim', $validated['student_nim'])->first();
                if (! $student) {
                    $student = Student::create([
                        'nim' => $validated['student_nim'],
                        'name' => $validated['student_name'],
                        'email' => $validated['student_email'],
                    ]);
                }

                // Upsert Course
                $course = Course::where('code', $validated['course_code'])->first();
                if (! $course) {
                    $course = Course::create([
                        'code' => $validated['course_code'],
                        'name' => $validated['course_name'],
                        'credits' => $validated['course_credits'],
                    ]);
                }

                // Create Enrollment
                Enrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'academic_year' => $validated['academic_year'],
                    'semester' => $validated['semester'],
                    'status' => $validated['status'],
                ]);
            });

            return redirect()->back()->with('success', 'Data KRS berhasil ditambahkan dalam 1 transaksi atomic!');
        } catch (QueryException $e) {
            Log::error('DB Store Error: ' . $e->getMessage());

            $msg = $e->getMessage();
            if (str_contains($msg, 'students_email_unique')) {
                return redirect()->back()->with('error', 'Gagal: Email ' . $validated['student_email'] . ' sudah digunakan oleh mahasiswa lain.');
            }
            if (str_contains($msg, 'students_nim_unique')) {
                return redirect()->back()->with('error', 'Gagal: NIM ' . $validated['student_nim'] . ' sudah terdaftar.');
            }
            if (str_contains($msg, 'unique constraint') || $e->getCode() === '23505') {
                return redirect()->back()->with('error', 'Gagal: Mahasiswa ini sudah mengambil mata kuliah tersebut pada Tahun Ajaran dan Semester yang sama (Data Duplikat).');
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        } catch (\Exception $e) {
            Log::error('Store System Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^[0-9]{4}\/[0-9]{4}$/'],
            'semester' => ['required', 'in:GANJIL,GENAP'],
            'status' => ['required', 'in:DRAFT,SUBMITTED,APPROVED,REJECTED'],
        ], [
            'academic_year.required' => 'Tahun Ajaran wajib diisi.',
            'academic_year.regex' => 'Tahun Ajaran harus berformat YYYY/YYYY (contoh: 2025/2026).',
        ]);

        try {
            $enrollment->update($validated);
            return redirect()->back()->with('success', 'Data KRS berhasil diperbarui!');
        } catch (QueryException $e) {
            Log::error('DB Update Error: ' . $e->getMessage());
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique constraint')) {
                return redirect()->back()->with('error', 'Gagal: Perubahan menyebabkan duplikasi KRS pada periode yang sama.');
            }
            return redirect()->back()->with('error', 'Gagal memperbarui data.');
        }
    }

    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();

        return redirect()->back()->with('success', 'Data KRS berhasil dihapus!');
    }

    public function exportCsv(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $query = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->select([
                'enrollments.id',
                'students.nim as student_nim',
                'students.name as student_name',
                'courses.code as course_code',
                'courses.name as course_name',
                'enrollments.academic_year',
                'enrollments.semester',
                'enrollments.status',
                'enrollments.created_at',
            ]);

        $matchMode = strtoupper($request->input('match_mode', 'AND')) === 'OR' ? 'OR' : 'AND';
        $hasSearch = $request->filled('search');
        $hasSemester = $request->filled('semester');
        $hasStatus = $request->filled('status');

        if ($hasSearch || $hasSemester || $hasStatus) {
            $query->where(function ($q) use ($request, $matchMode, $hasSearch, $hasSemester, $hasStatus) {
                $boolean = $matchMode === 'OR' ? 'orWhere' : 'where';

                if ($hasSearch) {
                    $search = $request->search;
                    $q->$boolean(function ($subQ) use ($search) {
                        $subQ->where('students.nim', 'LIKE', "{$search}%")
                            ->orWhere('students.name', 'ILIKE', "%{$search}%")
                            ->orWhere('courses.code', 'ILIKE', "{$search}%")
                            ->orWhere('courses.name', 'ILIKE', "%{$search}%");
                    });
                }

                if ($hasSemester) {
                    $q->$boolean('enrollments.semester', $request->semester);
                }

                if ($hasStatus) {
                    $q->$boolean('enrollments.status', $request->status);
                }
            });
        }

        $filename = 'krs_export_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID Enrollment', 'NIM Mahasiswa', 'Nama Mahasiswa', 'Kode MK', 'Nama MK', 'Tahun Ajaran', 'Semester', 'Status KRS', 'Tanggal Dibuat']);

            $counter = 0;
            foreach ($query->orderBy('enrollments.id', 'asc')->cursor() as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->student_nim,
                    $row->student_name,
                    $row->course_code,
                    $row->course_name,
                    $row->academic_year,
                    $row->semester,
                    $row->status,
                    $row->created_at,
                ]);

                $counter++;
                if ($counter % 5000 === 0) {
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            fclose($handle);
            exit;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
