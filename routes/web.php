<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\School\AcademicsController;
use App\Http\Controllers\School\AccountController;
use App\Http\Controllers\School\AdmissionController;
use App\Http\Controllers\School\AttendanceController;
use App\Http\Controllers\School\ExamController;
use App\Http\Controllers\School\FeeController;
use App\Http\Controllers\School\NoticeController;
use App\Http\Controllers\School\ParentManagementController;
use App\Http\Controllers\School\ParentController;
use App\Http\Controllers\School\ReportController;
use App\Http\Controllers\School\SetupController;
use App\Http\Controllers\School\StudentAccessController;
use App\Http\Controllers\School\StudentController;
use App\Http\Controllers\School\StudentFineController;
use App\Http\Controllers\School\StudentPortalController;
use App\Http\Controllers\School\TeacherController;
use App\Http\Controllers\SuperAdmin\SchoolController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'school.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'superAdmin'])->name('dashboard');
        Route::get('/activity', [DashboardController::class, 'activity'])->name('activity');
        Route::resource('schools', SchoolController::class)
            ->except(['destroy'])
            ->missing(fn () => redirect()
                ->route('super-admin.schools.index')
                ->withErrors(['school' => 'School account was not found.']));
        Route::patch('schools/{school}/status', [SchoolController::class, 'toggleStatus'])
            ->name('schools.status')
            ->missing(fn () => redirect()
                ->route('super-admin.schools.index')
                ->withErrors(['school' => 'School account was not found.']));
        Route::post('schools/{school}/notes', [SchoolController::class, 'storeNote'])
            ->name('schools.notes.store')
            ->missing(fn () => redirect()
                ->route('super-admin.schools.index')
                ->withErrors(['school' => 'School account was not found.']));
        Route::patch('schools/{school}/admins/{user}/password', [SchoolController::class, 'resetAdminPassword'])
            ->name('schools.admins.password')
            ->missing(fn () => redirect()
                ->route('super-admin.schools.index')
                ->withErrors(['school' => 'School account was not found.']));
    });

    Route::get('/school/dashboard', [DashboardController::class, 'school'])
        ->middleware('role:school_admin')
        ->name('school.dashboard');

    Route::get('/principal/dashboard', [DashboardController::class, 'principal'])
        ->middleware('role:principal')
        ->name('principal.dashboard');

    Route::get('/teacher/dashboard', [DashboardController::class, 'teacher'])
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::get('/parent/dashboard', [DashboardController::class, 'parent'])
        ->middleware('role:parent')
        ->name('parent.dashboard');

    Route::middleware('role:parent')->prefix('parent')->name('parent.')->group(function () {
        Route::get('/students/{student}', [ParentController::class, 'show'])->name('students.show');
        Route::get('/fees/{studentFee}/challan', [FeeController::class, 'challan'])->name('fees.challan');
        Route::get('/payments/{payment}/receipt', [FeeController::class, 'receipt'])->name('fees.receipt');
        Route::get('/exams/{exam}/students/{student}/marksheet', [ExamController::class, 'marksheet'])->name('exams.marksheet');
    });

    Route::get('/student/dashboard', [StudentPortalController::class, 'dashboard'])
        ->middleware('role:student')
        ->name('student.dashboard');

    Route::middleware(['role:student', 'school.setup'])->prefix('student')->name('student.')->group(function () {
        Route::get('/profile', [StudentPortalController::class, 'profile'])->name('profile');
        Route::get('/attendance', [StudentPortalController::class, 'attendance'])->name('attendance');
        Route::get('/fees', [StudentPortalController::class, 'fees'])->name('fees');
        Route::get('/results', [StudentPortalController::class, 'results'])->name('results');
        Route::get('/notices', [StudentPortalController::class, 'notices'])->name('notices');
    });

    Route::middleware(['role:school_admin,principal,teacher,parent,student', 'school.setup'])->prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/new', [MessageController::class, 'create'])->name('create');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::get('/{message}', [MessageController::class, 'show'])->name('show');
        Route::post('/{message}/reply', [MessageController::class, 'reply'])->name('reply');
    });

    Route::middleware('role:school_admin')->prefix('school')->group(function () {
        Route::get('/setup', [SetupController::class, 'edit'])->name('school.setup.edit');
        Route::get('/setup/preview/{type}', [SetupController::class, 'preview'])->name('school.setup.preview');
        Route::put('/setup', [SetupController::class, 'update'])->name('school.setup.update');
    });

    Route::middleware(['role:school_admin', 'school.setup'])->prefix('school')->group(function () {
        Route::get('/academics', [AcademicsController::class, 'index'])->name('academics.index');
        Route::post('/academics/classes', [AcademicsController::class, 'storeClass'])->name('academics.classes.store');
        Route::put('/academics/classes/{class}', [AcademicsController::class, 'updateClass'])->name('academics.classes.update');
        Route::delete('/academics/classes/{class}', [AcademicsController::class, 'destroyClass'])->name('academics.classes.destroy');
        Route::post('/academics/sections', [AcademicsController::class, 'storeSection'])->name('academics.sections.store');
        Route::put('/academics/sections/{section}', [AcademicsController::class, 'updateSection'])->name('academics.sections.update');
        Route::delete('/academics/sections/{section}', [AcademicsController::class, 'destroySection'])->name('academics.sections.destroy');
        Route::post('/academics/subjects', [AcademicsController::class, 'storeSubject'])->name('academics.subjects.store');
        Route::put('/academics/subjects/{subject}', [AcademicsController::class, 'updateSubject'])->name('academics.subjects.update');
        Route::delete('/academics/subjects/{subject}', [AcademicsController::class, 'destroySubject'])->name('academics.subjects.destroy');
        Route::post('/academics/class-subjects', [AcademicsController::class, 'storeClassSubject'])->name('academics.class-subjects.store');
        Route::delete('/academics/class-subjects/{classSubject}', [AcademicsController::class, 'destroyClassSubject'])->name('academics.class-subjects.destroy');

        Route::resource('students', StudentController::class);
        Route::post('/students/{student}/documents', [StudentController::class, 'storeDocument'])->name('students.documents.store');
        Route::delete('/student-documents/{document}', [StudentController::class, 'destroyDocument'])->name('students.documents.destroy');
        Route::post('/students/{student}/parent-login', [StudentAccessController::class, 'createParentLogin'])->name('students.parent-login');
        Route::post('/students/{student}/link-parent', [StudentAccessController::class, 'linkParent'])->name('students.link-parent');
        Route::post('/students/{student}/student-login', [StudentAccessController::class, 'createStudentLogin'])->name('students.student-login');
        Route::patch('/students/{student}/student-password', [StudentAccessController::class, 'resetStudentPassword'])->name('students.student-password');
        Route::patch('/students/{student}/student-access/disable', [StudentAccessController::class, 'disableStudentAccess'])->name('students.student-access.disable');
        Route::patch('/students/{student}/parents/{parent}/access/disable', [StudentAccessController::class, 'disableParentAccess'])->name('students.parent-access.disable');
        Route::resource('parents', ParentManagementController::class)->except(['create', 'edit', 'destroy']);
        Route::patch('/parents/{parent}/login', [ParentManagementController::class, 'createLogin'])->name('parents.login');
        Route::patch('/parents/{parent}/toggle', [ParentManagementController::class, 'toggle'])->name('parents.toggle');
        Route::post('/parents/{parent}/message', [ParentManagementController::class, 'message'])->name('parents.message');
        Route::post('/students/{student}/fines', [StudentFineController::class, 'store'])->name('students.fines.store');
        Route::post('/student-fines', [StudentFineController::class, 'store'])->name('student-fines.store');
        Route::patch('/student-fines/{studentFine}', [StudentFineController::class, 'update'])->name('student-fines.update');
        Route::patch('/student-fines/{studentFine}/paid', [StudentFineController::class, 'markPaid'])->name('student-fines.paid');
        Route::patch('/student-fines/{studentFine}/waive', [StudentFineController::class, 'waive'])->name('student-fines.waive');
        Route::delete('/student-fines/{studentFine}', [StudentFineController::class, 'destroy'])->name('student-fines.destroy');
        Route::get('/salaries', [TeacherController::class, 'salaries'])->name('salaries.index');
        Route::post('/salaries/generate', [TeacherController::class, 'generateMonthlySalaries'])->name('salaries.generate');
        Route::post('/salaries/teacher-salary', [TeacherController::class, 'updateTeacherSalary'])->name('salaries.teacher.update');
        Route::get('/salaries/{salaryPayment}', [TeacherController::class, 'salaryShow'])->name('salaries.show');
        Route::patch('/salaries/{salaryPayment}', [TeacherController::class, 'updateSalaryRecord'])->name('salaries.update');
        Route::patch('/salaries/{salaryPayment}/payment', [TeacherController::class, 'recordSalaryPayment'])->name('salaries.payment');
        Route::patch('/salaries/{salaryPayment}/deduction', [TeacherController::class, 'recordSalaryDeduction'])->name('salaries.deduction');
        Route::get('/teacher-salaries', [TeacherController::class, 'salaries'])->name('teacher-salaries.index');
        Route::post('/teacher-salaries/generate', [TeacherController::class, 'generateMonthlySalaries'])->name('teacher-salaries.generate');
        Route::patch('/teacher-salaries/{salaryPayment}', [TeacherController::class, 'updateSalaryRecord'])->name('teacher-salaries.update');
        Route::resource('teachers', TeacherController::class);
        Route::patch('/teachers/{teacher}/password', [TeacherController::class, 'resetPassword'])->name('teachers.password');
        Route::patch('/teachers/{teacher}/status', [TeacherController::class, 'toggleStatus'])->name('teachers.status');
        Route::post('/teachers/{teacher}/salary', [TeacherController::class, 'generateSalary'])->name('teachers.salary.store');
        Route::patch('/teacher-salaries/{salaryPayment}/payment', [TeacherController::class, 'recordSalaryPayment'])->name('teachers.salary.payment');
        Route::patch('/teacher-salaries/{salaryPayment}/deduction', [TeacherController::class, 'recordSalaryDeduction'])->name('teachers.salary.deduction');

        Route::get('/admissions', [AdmissionController::class, 'index'])->name('admissions.index');
        Route::post('/admissions', [AdmissionController::class, 'store'])->name('admissions.store');
        Route::put('/admissions/{admission}', [AdmissionController::class, 'update'])->name('admissions.update');
        Route::patch('/admissions/{admission}/approve', [AdmissionController::class, 'approve'])->name('admissions.approve');
        Route::patch('/admissions/{admission}/reject', [AdmissionController::class, 'reject'])->name('admissions.reject');
        Route::post('/admissions/{admission}/convert', [AdmissionController::class, 'convert'])->name('admissions.convert');
        Route::get('/admissions/{admission}/form', [AdmissionController::class, 'form'])->name('admissions.form');

        Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
        Route::post('/fees/heads', [FeeController::class, 'storeHead'])->name('fees.heads.store');
        Route::post('/fees/structures', [FeeController::class, 'storeStructure'])->name('fees.structures.store');
        Route::post('/fees/generate', [FeeController::class, 'generate'])->name('fees.generate');
        Route::get('/fees/students/{student}/account', [FeeController::class, 'studentAccount'])->name('fees.students.account');
        Route::post('/fees/students/{student}/payments', [FeeController::class, 'studentPayment'])->name('fees.students.payments.store');
        Route::post('/fees/students/{student}/discounts', [FeeController::class, 'studentDiscount'])->name('fees.students.discounts.store');
        Route::patch('/fees/{studentFee}', [FeeController::class, 'updateFee'])->name('fees.update');
        Route::post('/fees/{studentFee}/fine', [FeeController::class, 'addFine'])->name('fees.fine');
        Route::post('/fees/{studentFee}/discount', [FeeController::class, 'addDiscount'])->name('fees.discount');
        Route::post('/fees/{studentFee}/payments', [FeeController::class, 'payment'])->name('fees.payments.store');
        Route::get('/fees/payments/{payment}/receipt', [FeeController::class, 'receipt'])->name('fees.receipt');
        Route::get('/fees/{studentFee}/challan', [FeeController::class, 'challan'])->name('fees.challan');

        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/income', [AccountController::class, 'income'])->name('accounts.income');
        Route::post('/accounts/income', [AccountController::class, 'storeIncome'])->name('accounts.income.store');
        Route::get('/accounts/expenses', [AccountController::class, 'expenses'])->name('accounts.expenses');
        Route::post('/accounts/expenses', [AccountController::class, 'storeExpense'])->name('accounts.expenses.store');
        Route::get('/accounts/ledger', [AccountController::class, 'ledger'])->name('accounts.ledger');
        Route::get('/accounts/cash-book', [AccountController::class, 'cashBook'])->name('accounts.cash-book');
        Route::get('/accounts/reports/student-dues', [AccountController::class, 'studentDues'])->name('accounts.reports.student-dues');
        Route::get('/accounts/reports/salary', [AccountController::class, 'salaryReport'])->name('accounts.reports.salary');
        Route::get('/accounts/reports/summary', [AccountController::class, 'summary'])->name('accounts.reports.summary');
        Route::get('/accounts/exports/income.csv', [AccountController::class, 'incomeCsv'])->name('accounts.exports.income');
        Route::get('/accounts/exports/expenses.csv', [AccountController::class, 'expenseCsv'])->name('accounts.exports.expenses');
        Route::get('/accounts/exports/ledger.csv', [AccountController::class, 'ledgerCsv'])->name('accounts.exports.ledger');
        Route::get('/accounts/exports/student-dues.csv', [AccountController::class, 'studentDuesCsv'])->name('accounts.exports.student-dues');
        Route::get('/accounts/exports/salary-report.csv', [AccountController::class, 'salaryReportCsv'])->name('accounts.exports.salary-report');
        Route::get('/accounts/exports/summary.csv', [AccountController::class, 'summaryCsv'])->name('accounts.exports.summary');

        Route::get('/notices', [NoticeController::class, 'index'])->name('notices.index');
        Route::post('/notices', [NoticeController::class, 'store'])->name('notices.store');
        Route::put('/notices/{notice}', [NoticeController::class, 'update'])->name('notices.update');
        Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');
    });

    Route::middleware(['role:school_admin,teacher,principal', 'school.setup'])->prefix('school')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');

        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::post('/exams/{exam}/subjects', [ExamController::class, 'storeSubject'])->name('exams.subjects.store');
        Route::get('/exams/{exam}/marks', [ExamController::class, 'marks'])->name('exams.marks');
        Route::post('/exams/{exam}/marks', [ExamController::class, 'saveMarks'])->name('exams.marks.store');
        Route::patch('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');
        Route::get('/exams/{exam}/students/{student}/marksheet', [ExamController::class, 'marksheet'])->name('exams.marksheet');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/students.csv', [ReportController::class, 'studentsCsv'])->name('reports.students.csv');
        Route::get('/reports/fee-defaulters.csv', [ReportController::class, 'feeDefaultersCsv'])->name('reports.fee-defaulters.csv');
        Route::get('/reports/payments.csv', [ReportController::class, 'paymentsCsv'])->name('reports.payments.csv');
        Route::get('/reports/student-fines.csv', [ReportController::class, 'studentFinesCsv'])->name('reports.student-fines.csv');
        Route::get('/reports/student-discounts.csv', [ReportController::class, 'studentDiscountsCsv'])->name('reports.student-discounts.csv');
        Route::get('/reports/student-ledger.csv', [ReportController::class, 'studentLedgerCsv'])->name('reports.student-ledger.csv');
        Route::get('/reports/teacher-salaries.csv', [ReportController::class, 'teacherSalariesCsv'])->name('reports.teacher-salaries.csv');
    });
});

require __DIR__.'/auth.php';
