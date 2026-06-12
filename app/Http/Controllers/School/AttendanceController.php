<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEditLog;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $date = $request->date('attendance_date') ?: today();

        $records = AttendanceRecord::with(['student', 'schoolClass', 'section'])
            ->forSchool($schoolId)
            ->whereDate('attendance_date', $date);

        if ($request->filled('school_class_id')) {
            $records->where('school_class_id', $request->integer('school_class_id'));
        }

        if ($request->filled('section_id')) {
            $records->where('section_id', $request->integer('section_id'));
        }

        return view('school.attendance.index', [
            'records' => $records->latest()->paginate(25)->withQueryString(),
            'classes' => $this->availableClasses(),
            'sections' => Section::forSchool($schoolId)->orderBy('name')->get(),
            'selectedDate' => $date,
        ]);
    }

    public function create(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $classId = $request->integer('school_class_id');
        $sectionId = $request->integer('section_id') ?: null;
        $date = $request->date('attendance_date') ?: today();

        $students = Student::forSchool($schoolId)
            ->where('status', 'active')
            ->when($classId, fn ($query) => $query->where('school_class_id', $classId))
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->orderBy('roll_number')
            ->orderBy('name')
            ->get();

        return view('school.attendance.create', [
            'classes' => $this->availableClasses(),
            'sections' => Section::forSchool($schoolId)->orderBy('name')->get(),
            'students' => $students,
            'selectedClassId' => $classId,
            'selectedSectionId' => $sectionId,
            'selectedDate' => $date,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', Rule::in(['present', 'absent', 'late', 'leave'])],
        ]);

        foreach ($validated['attendance'] as $studentId => $status) {
            $student = Student::forSchool(SchoolContext::id())->findOrFail($studentId);

            AttendanceRecord::updateOrCreate(
                [
                    'school_id' => SchoolContext::id(),
                    'student_id' => $student->id,
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'school_class_id' => $validated['school_class_id'],
                    'section_id' => $validated['section_id'] ?? null,
                    'status' => $status,
                    'marked_by' => SchoolContext::user()->id,
                ],
            );
        }

        Activity::log('attendance_marked', 'Attendance marked for '.$validated['attendance_date']);

        return redirect()->route('attendance.index', [
            'attendance_date' => $validated['attendance_date'],
            'school_class_id' => $validated['school_class_id'],
            'section_id' => $validated['section_id'] ?? null,
        ])->with('status', 'Attendance saved.');
    }

    public function update(Request $request, AttendanceRecord $attendance): RedirectResponse
    {
        abort_unless((int) $attendance->school_id === SchoolContext::id(), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'leave'])],
            'edit_reason' => ['required', 'string', 'max:1000'],
        ]);

        $oldStatus = $attendance->status;
        $attendance->update([
            'status' => $validated['status'],
            'edited_by' => SchoolContext::user()->id,
            'edit_reason' => $validated['edit_reason'],
        ]);

        AttendanceEditLog::create([
            'school_id' => SchoolContext::id(),
            'attendance_id' => $attendance->id,
            'user_id' => SchoolContext::user()->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'reason' => $validated['edit_reason'],
        ]);

        Activity::log('attendance_edited', 'Attendance edited with reason.', ['attendance_id' => $attendance->id]);

        return back()->with('status', 'Attendance updated.');
    }

    private function availableClasses()
    {
        $query = SchoolClass::forSchool(SchoolContext::id())->where('is_active', true)->orderBy('sort_order');

        if (SchoolContext::user()->role === 'teacher') {
            $teacher = Teacher::where('user_id', SchoolContext::user()->id)->first();
            $classIds = $teacher?->assignments()->pluck('school_class_id') ?? collect();
            $query->whereIn('id', $classIds);
        }

        return $query->get();
    }
}
