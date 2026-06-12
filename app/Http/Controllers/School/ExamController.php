<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Guardian;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\SchoolTemplateSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Activity;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $schoolId = SchoolContext::id();

        return view('school.exams.index', [
            'exams' => Exam::with(['schoolClass', 'section', 'examSubjects.subject'])->forSchool($schoolId)->latest()->paginate(15),
            'classes' => SchoolClass::forSchool($schoolId)->orderBy('sort_order')->get(),
            'sections' => Section::forSchool($schoolId)->orderBy('name')->get(),
            'subjects' => Subject::forSchool($schoolId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['monthly_test', 'term_exam', 'final_exam'])],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'exam_date' => ['nullable', 'date'],
            'session' => ['nullable', 'string', 'max:50'],
        ]);

        $exam = Exam::create($validated + ['school_id' => SchoolContext::id()]);
        Activity::log('exam_created', 'Exam created: '.$exam->name, ['exam_id' => $exam->id]);

        return back()->with('status', 'Exam created.');
    }

    public function storeSubject(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', SchoolContext::id())],
            'total_marks' => ['required', 'numeric', 'min:1'],
            'passing_marks' => ['required', 'numeric', 'min:0', 'lte:total_marks'],
        ]);

        ExamSubject::updateOrCreate(
            ['exam_id' => $exam->id, 'subject_id' => $validated['subject_id']],
            $validated + ['school_id' => SchoolContext::id()],
        );

        Activity::log('exam_subject_added', 'Exam subject added.', ['exam_id' => $exam->id]);

        return back()->with('status', 'Subject added to exam.');
    }

    public function marks(Exam $exam): View
    {
        $this->authorizeExam($exam);

        $students = Student::forSchool(SchoolContext::id())
            ->where('school_class_id', $exam->school_class_id)
            ->when($exam->section_id, fn ($query) => $query->where('section_id', $exam->section_id))
            ->where('status', 'active')
            ->orderBy('roll_number')
            ->orderBy('name')
            ->get();

        return view('school.exams.marks', [
            'exam' => $exam->load('examSubjects.subject', 'schoolClass', 'section'),
            'students' => $students,
            'marks' => Mark::forSchool(SchoolContext::id())->where('exam_id', $exam->id)->get()->keyBy(fn ($mark) => $mark->student_id.'-'.$mark->subject_id),
        ]);
    }

    public function saveMarks(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $subjects = $exam->examSubjects()->get()->keyBy('subject_id');

        DB::transaction(function () use ($validated, $exam, $subjects) {
            foreach ($validated['marks'] as $studentId => $subjectMarks) {
                $student = Student::forSchool(SchoolContext::id())->findOrFail($studentId);

                foreach ($subjectMarks as $subjectId => $obtained) {
                    if ($obtained === null || $obtained === '') {
                        continue;
                    }

                    $examSubject = $subjects->get((int) $subjectId);
                    abort_unless($examSubject, 422, 'Invalid subject selected.');
                    abort_if((float) $obtained > (float) $examSubject->total_marks, 422, 'Marks cannot be greater than total marks.');

                    Mark::updateOrCreate(
                        ['exam_id' => $exam->id, 'subject_id' => $subjectId, 'student_id' => $student->id],
                        [
                            'school_id' => SchoolContext::id(),
                            'marks_obtained' => $obtained,
                            'total_marks' => $examSubject->total_marks,
                            'passing_marks' => $examSubject->passing_marks,
                            'grade' => $this->grade((float) $obtained, (float) $examSubject->total_marks),
                            'status' => (float) $obtained >= (float) $examSubject->passing_marks ? 'pass' : 'fail',
                            'entered_by' => SchoolContext::user()->id,
                        ],
                    );
                }
            }
        });

        Activity::log('marks_entered', 'Marks saved for exam: '.$exam->name, ['exam_id' => $exam->id]);

        return back()->with('status', 'Marks saved.');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        if (! $exam->is_published) {
            $subjectCount = $exam->examSubjects()->count();
            if ($subjectCount === 0) {
                return back()->withErrors(['exam' => 'Add at least one subject before publishing results.']);
            }

            $students = Student::forSchool(SchoolContext::id())
                ->where('school_class_id', $exam->school_class_id)
                ->when($exam->section_id, fn ($query) => $query->where('section_id', $exam->section_id))
                ->where('status', 'active')
                ->count();

            if ($students === 0) {
                return back()->withErrors(['exam' => 'No active students found for this exam class/section.']);
            }

            $marksCount = Mark::forSchool(SchoolContext::id())
                ->where('exam_id', $exam->id)
                ->count();

            if ($marksCount < ($students * $subjectCount)) {
                return back()->withErrors(['exam' => 'Complete marks for all students and subjects before publishing.']);
            }
        }

        $exam->update(['is_published' => ! $exam->is_published]);
        Activity::log($exam->is_published ? 'result_published' : 'result_unpublished', $exam->name);

        return back()->with('status', $exam->is_published ? 'Result published.' : 'Result unpublished.');
    }

    public function marksheet(Exam $exam, Student $student)
    {
        $this->authorizeExam($exam);
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);
        $this->authorizeStudentForParent($student);

        $data = [
            'school' => SchoolContext::school(),
            'template' => SchoolTemplateSetting::firstOrCreate(['school_id' => SchoolContext::id(), 'template_type' => 'marksheet']),
            'exam' => $exam->load(['schoolClass', 'section', 'examSubjects.subject']),
            'student' => $student->load(['schoolClass', 'section']),
            'marks' => Mark::with('subject')->forSchool(SchoolContext::id())->where('exam_id', $exam->id)->where('student_id', $student->id)->get(),
            'pdf' => request()->boolean('download'),
        ];

        if ($data['pdf']) {
            return Pdf::loadView('pdf.marksheet', $data)->download('marksheet-'.$student->registration_number.'.pdf');
        }

        return view('pdf.marksheet', $data);
    }

    private function grade(float $marks, float $total): string
    {
        $percent = $total > 0 ? ($marks / $total) * 100 : 0;

        return match (true) {
            $percent >= 90 => 'A+',
            $percent >= 80 => 'A',
            $percent >= 70 => 'B',
            $percent >= 60 => 'C',
            $percent >= 50 => 'D',
            default => 'F',
        };
    }

    private function authorizeExam(Exam $exam): void
    {
        abort_unless((int) $exam->school_id === SchoolContext::id(), 404);

        if (SchoolContext::user()->role === 'parent') {
            abort_unless($exam->is_published, 404);
        }
    }

    private function authorizeStudentForParent(Student $student): void
    {
        if (SchoolContext::user()->role !== 'parent') {
            return;
        }

        $ownsStudent = Guardian::forSchool(SchoolContext::id())
            ->where('user_id', SchoolContext::user()->id)
            ->whereHas('students', fn ($query) => $query->where('students.id', $student->id))
            ->exists();

        abort_unless($ownsStudent, 404);
    }
}
