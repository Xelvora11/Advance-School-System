<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\LoginHistory;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentFine;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherSalaryPayment;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return redirect()->route(auth()->user()->dashboardRoute());
    }

    public function superAdmin(): View
    {
        return view('super-admin.dashboard', [
            'totalSchools' => School::count(),
            'activeSchools' => School::where('status', 'active')->whereIn('account_status', ['active', 'trial'])->count(),
            'inactiveSchools' => School::where(function ($query) {
                $query->where('status', 'inactive')->orWhereIn('account_status', ['inactive', 'suspended']);
            })->count(),
            'totalStudents' => Student::count(),
            'totalTeachers' => Teacher::count(),
            'recentSchools' => School::latest()->take(5)->get(),
            'recentLogins' => LoginHistory::with('school')->latest('logged_in_at')->take(8)->get(),
        ]);
    }

    public function activity(Request $request): View
    {
        $query = ActivityLog::with(['school', 'user'])->latest();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->integer('school_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('role')) {
            $query->where(function ($roleQuery) use ($request) {
                $roleQuery->where('role', $request->role)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('role', $request->role));
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return view('super-admin.activity', [
            'logs' => $query->paginate(25)->withQueryString(),
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'role']),
            'actions' => ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function school(): View
    {
        $schoolId = SchoolContext::id();
        $today = today();

        $attendanceMarkedGroups = AttendanceRecord::forSchool($schoolId)
            ->whereDate('attendance_date', $today)
            ->select('school_class_id', 'section_id')
            ->distinct()
            ->get();

        $attendanceMarkedClasses = $attendanceMarkedGroups->count();
        $activeSections = Section::forSchool($schoolId)
            ->with('schoolClass')
            ->where('is_active', true)
            ->whereHas('schoolClass', fn ($query) => $query->where('is_active', true))
            ->whereHas('students', fn ($query) => $query->where('status', 'active'))
            ->get();

        $markedKeys = $attendanceMarkedGroups
            ->map(fn (AttendanceRecord $record) => $record->school_class_id.'-'.$record->section_id)
            ->all();

        $missingAttendance = $activeSections
            ->reject(fn (Section $section) => in_array($section->school_class_id.'-'.$section->id, $markedKeys, true))
            ->values();

        $pendingFeeBalance = StudentFee::forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get()
            ->sum(fn (StudentFee $fee) => $fee->balance());

        $openFees = StudentFee::forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get();

        $overdueFeeBalance = $openFees
            ->filter(fn (StudentFee $fee) => $fee->due_date?->lt($today))
            ->sum(fn (StudentFee $fee) => $fee->balance());

        $feesDueThisWeek = $openFees
            ->filter(fn (StudentFee $fee) => $fee->due_date && $fee->due_date->betweenIncluded($today, $today->copy()->addDays(7)))
            ->sum(fn (StudentFee $fee) => $fee->balance());

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $unpaidFineQuery = StudentFine::forSchool($schoolId)->where('status', 'unpaid');
        $teacherSalaryDueQuery = TeacherSalaryPayment::forSchool($schoolId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('salary_month', $today->month)
            ->where('salary_year', $today->year);

        return view('school.dashboard.index', [
            'school' => SchoolContext::school(),
            'students' => Student::forSchool($schoolId)->count(),
            'teachers' => Teacher::forSchool($schoolId)->count(),
            'pendingFees' => $pendingFeeBalance,
            'overdueFees' => $overdueFeeBalance,
            'feesDueThisWeek' => $feesDueThisWeek,
            'unpaidFines' => (clone $unpaidFineQuery)->sum('amount'),
            'unpaidFineCount' => (clone $unpaidFineQuery)->count(),
            'teacherSalariesDue' => (clone $teacherSalaryDueQuery)->sum('balance'),
            'teacherSalaryDueCount' => (clone $teacherSalaryDueQuery)->count(),
            'salaryPaidThisMonth' => TeacherSalaryPayment::forSchool($schoolId)
                ->where('salary_month', $today->month)
                ->where('salary_year', $today->year)
                ->sum('paid_amount'),
            'collectedThisMonth' => Payment::forSchool($schoolId)
                ->whereBetween('paid_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount'),
            'todaysCollection' => Payment::forSchool($schoolId)
                ->whereDate('paid_on', $today)
                ->sum('amount'),
            'attendanceMarkedClasses' => $attendanceMarkedClasses,
            'attendanceTotalClasses' => $activeSections->count(),
            'missingAttendance' => $missingAttendance->take(5),
            'pendingAdmissions' => Admission::forSchool($schoolId)->whereIn('status', ['inquiry', 'pending'])->count(),
            'feeDefaulters' => StudentFee::forSchool($schoolId)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->whereDate('due_date', '<', $today)
                ->distinct('student_id')
                ->count('student_id'),
            'unpublishedExams' => Exam::forSchool($schoolId)->where('is_published', false)->count(),
            'notices' => Notice::forSchool($schoolId)->latest()->take(5)->get(),
        ]);
    }

    public function principal(): View
    {
        return $this->school();
    }

    public function teacher(): View
    {
        $user = SchoolContext::user();
        $teacher = Teacher::forSchool(SchoolContext::id())->where('user_id', $user->id)->first();

        return view('school.dashboard.teacher', [
            'teacher' => $teacher,
            'assignments' => $teacher
                ? TeacherAssignment::with(['schoolClass', 'section', 'subject'])->where('teacher_id', $teacher->id)->get()
                : collect(),
            'notices' => Notice::forSchool(SchoolContext::id())
                ->where('is_published', true)
                ->whereIn('audience_type', ['teachers', 'all'])
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }

    public function parent(): View
    {
        $guardian = Guardian::with(['students.schoolClass', 'students.section'])
            ->forSchool(SchoolContext::id())
            ->where('user_id', SchoolContext::user()->id)
            ->first();

        $studentIds = $guardian?->students->pluck('id') ?? collect();
        $classIds = $guardian?->students->pluck('school_class_id')->filter()->unique() ?? collect();
        $sectionIds = $guardian?->students->pluck('section_id')->filter()->unique() ?? collect();

        return view('school.parent.dashboard', [
            'guardian' => $guardian,
            'students' => $guardian?->students ?? collect(),
            'fees' => StudentFee::with(['student', 'feeHead'])
                ->with('payments')
                ->forSchool(SchoolContext::id())
                ->whereIn('student_id', $studentIds)
                ->latest()
                ->take(12)
                ->get(),
            'notices' => Notice::forSchool(SchoolContext::id())
                ->where('is_published', true)
                ->where(function ($query) use ($classIds, $sectionIds, $studentIds) {
                    $query->whereIn('audience_type', ['all_parents', 'all_students', 'all'])
                        ->orWhere('target_user_id', SchoolContext::user()->id)
                        ->orWhereIn('target_student_id', $studentIds)
                        ->orWhere(function ($classQuery) use ($classIds) {
                            $classQuery->where('audience_type', 'class')->whereIn('school_class_id', $classIds);
                        })
                        ->orWhere(function ($sectionQuery) use ($sectionIds) {
                            $sectionQuery->where('audience_type', 'section')->whereIn('section_id', $sectionIds);
                        });
                })
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }
}
