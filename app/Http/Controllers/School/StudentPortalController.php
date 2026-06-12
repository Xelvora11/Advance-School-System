<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use App\Support\SchoolContext;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function dashboard(): View
    {
        $student = $this->student();

        return view('school.student.dashboard', [
            'student' => $student->load(['schoolClass', 'section']),
            'attendanceSummary' => AttendanceRecord::forSchool(SchoolContext::id())
                ->where('student_id', $student->id)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'fees' => StudentFee::with(['feeHead', 'payments'])
                ->forSchool(SchoolContext::id())
                ->where('student_id', $student->id)
                ->latest()
                ->take(6)
                ->get(),
            'exams' => $this->publishedExams($student)->take(5)->get(),
            'notices' => $this->visibleNotices($student)->take(6)->get(),
        ]);
    }

    public function profile(): View
    {
        return view('school.student.profile', ['student' => $this->student()->load(['schoolClass', 'section'])]);
    }

    public function attendance(): View
    {
        $student = $this->student();

        return view('school.student.attendance', [
            'student' => $student,
            'attendance' => AttendanceRecord::forSchool(SchoolContext::id())
                ->where('student_id', $student->id)
                ->latest('attendance_date')
                ->paginate(30),
        ]);
    }

    public function fees(): View
    {
        $student = $this->student();
        $fees = StudentFee::with(['feeHead', 'payments.receiver'])
            ->forSchool(SchoolContext::id())
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return view('school.student.fees', [
            'student' => $student,
            'fees' => $fees,
            'payments' => Payment::with(['studentFee.feeHead', 'receiver'])
                ->forSchool(SchoolContext::id())
                ->where('student_id', $student->id)
                ->latest('paid_on')
                ->take(20)
                ->get(),
        ]);
    }

    public function results(): View
    {
        $student = $this->student();

        return view('school.student.results', [
            'student' => $student,
            'exams' => $this->publishedExams($student)->get(),
        ]);
    }

    public function notices(): View
    {
        $student = $this->student();

        return view('school.student.notices', [
            'student' => $student,
            'notices' => $this->visibleNotices($student)->paginate(15),
        ]);
    }

    private function student(): Student
    {
        return Student::with(['schoolClass', 'section'])
            ->forSchool(SchoolContext::id())
            ->where('user_id', SchoolContext::user()->id)
            ->firstOrFail();
    }

    private function publishedExams(Student $student)
    {
        return Exam::with(['marks' => fn ($query) => $query->where('student_id', $student->id), 'examSubjects.subject'])
            ->forSchool(SchoolContext::id())
            ->where('school_class_id', $student->school_class_id)
            ->when($student->section_id, fn ($query) => $query->where(function ($inner) use ($student) {
                $inner->whereNull('section_id')->orWhere('section_id', $student->section_id);
            }))
            ->where('is_published', true)
            ->latest();
    }

    private function visibleNotices(Student $student)
    {
        return Notice::forSchool(SchoolContext::id())
            ->where('is_published', true)
            ->where(function ($query) use ($student) {
                $query->whereIn('audience_type', ['all', 'all_students'])
                    ->orWhere('target_student_id', $student->id)
                    ->orWhere('target_user_id', SchoolContext::user()->id)
                    ->orWhere(function ($classQuery) use ($student) {
                        $classQuery->where('audience_type', 'class')->where('school_class_id', $student->school_class_id);
                    })
                    ->orWhere(function ($sectionQuery) use ($student) {
                        $sectionQuery->where('audience_type', 'section')->where('section_id', $student->section_id);
                    });
            })
            ->latest();
    }
}
