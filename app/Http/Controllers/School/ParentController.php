<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentFee;
use App\Support\SchoolContext;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function show(Student $student): View
    {
        $this->authorizeStudent($student);

        return view('school.parent.student', [
            'student' => $student->load(['schoolClass', 'section']),
            'attendance' => AttendanceRecord::forSchool(SchoolContext::id())
                ->where('student_id', $student->id)
                ->latest('attendance_date')
                ->take(30)
                ->get(),
            'fees' => StudentFee::with(['feeHead', 'payments'])
                ->forSchool(SchoolContext::id())
                ->where('student_id', $student->id)
                ->latest()
                ->take(20)
                ->get(),
            'exams' => Exam::with('marks')
                ->forSchool(SchoolContext::id())
                ->where('school_class_id', $student->school_class_id)
                ->when($student->section_id, fn ($query) => $query->where(function ($inner) use ($student) {
                    $inner->whereNull('section_id')->orWhere('section_id', $student->section_id);
                }))
                ->where('is_published', true)
                ->latest()
                ->get(),
        ]);
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);

        $ownsStudent = Guardian::forSchool(SchoolContext::id())
            ->where('user_id', SchoolContext::user()->id)
            ->whereHas('students', fn ($query) => $query->where('students.id', $student->id))
            ->exists();

        abort_unless($ownsStudent, 404);
    }
}
