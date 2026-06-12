<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicsController extends Controller
{
    public function index(): View
    {
        $schoolId = SchoolContext::id();

        return view('school.academics.index', [
            'classes' => SchoolClass::withCount('students')->with('sections')->forSchool($schoolId)->orderBy('sort_order')->get(),
            'sections' => Section::with(['schoolClass', 'classTeacher'])->forSchool($schoolId)->latest()->get(),
            'subjects' => Subject::forSchool($schoolId)->orderBy('name')->get(),
            'teachers' => User::where('school_id', $schoolId)->whereIn('role', [User::ROLE_TEACHER, User::ROLE_PRINCIPAL])->orderBy('name')->get(),
            'subjectTeachers' => Teacher::forSchool($schoolId)->where('status', 'active')->orderBy('name')->get(),
            'classSubjects' => ClassSubject::with(['schoolClass', 'section', 'subject', 'teacher'])->forSchool($schoolId)->latest()->get(),
        ]);
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('school_classes')->where('school_id', SchoolContext::id())],
            'code' => ['nullable', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        SchoolClass::create($validated + ['school_id' => SchoolContext::id(), 'sort_order' => $validated['sort_order'] ?? 0]);
        Activity::log('class_created', 'Class created: '.$validated['name']);

        return back()->with('status', 'Class added.');
    }

    public function updateClass(Request $request, SchoolClass $class): RedirectResponse
    {
        $this->authorizeSchoolModel($class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('school_classes')->where('school_id', SchoolContext::id())->ignore($class->id)],
            'code' => ['nullable', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $class->update($validated + ['is_active' => $request->boolean('is_active')]);
        Activity::log('class_updated', 'Class updated: '.$class->name);

        return back()->with('status', 'Class updated.');
    }

    public function destroyClass(SchoolClass $class): RedirectResponse
    {
        $this->authorizeSchoolModel($class);

        if (Student::where('school_class_id', $class->id)->exists()) {
            return back()->withErrors(['delete' => 'This class has students and cannot be deleted.']);
        }

        $class->delete();
        Activity::log('class_deleted', 'Class deleted.');

        return back()->with('status', 'Class deleted.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'name' => ['required', 'string', 'max:80'],
            'class_teacher_id' => ['nullable', Rule::exists('users', 'id')->where('school_id', SchoolContext::id())],
        ]);

        Section::create($validated + ['school_id' => SchoolContext::id()]);
        Activity::log('section_created', 'Section created: '.$validated['name']);

        return back()->with('status', 'Section added.');
    }

    public function updateSection(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeSchoolModel($section);

        $validated = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'name' => ['required', 'string', 'max:80'],
            'class_teacher_id' => ['nullable', Rule::exists('users', 'id')->where('school_id', SchoolContext::id())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $section->update($validated + ['is_active' => $request->boolean('is_active')]);
        Activity::log('section_updated', 'Section updated: '.$section->name);

        return back()->with('status', 'Section updated.');
    }

    public function destroySection(Section $section): RedirectResponse
    {
        $this->authorizeSchoolModel($section);

        if (Student::where('section_id', $section->id)->exists()) {
            return back()->withErrors(['delete' => 'This section has students and cannot be deleted.']);
        }

        $section->delete();
        Activity::log('section_deleted', 'Section deleted.');

        return back()->with('status', 'Section deleted.');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('subjects')->where('school_id', SchoolContext::id())],
            'code' => ['nullable', 'string', 'max:30'],
        ]);

        Subject::create($validated + ['school_id' => SchoolContext::id()]);
        Activity::log('subject_created', 'Subject created: '.$validated['name']);

        return back()->with('status', 'Subject added.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorizeSchoolModel($subject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('subjects')->where('school_id', SchoolContext::id())->ignore($subject->id)],
            'code' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subject->update($validated + ['is_active' => $request->boolean('is_active')]);
        Activity::log('subject_updated', 'Subject updated: '.$subject->name);

        return back()->with('status', 'Subject updated.');
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        $this->authorizeSchoolModel($subject);
        $subject->delete();
        Activity::log('subject_deleted', 'Subject deleted.');

        return back()->with('status', 'Subject deleted.');
    }

    public function storeClassSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', SchoolContext::id())],
            'teacher_id' => ['nullable', Rule::exists('teachers', 'id')->where('school_id', SchoolContext::id())],
        ]);

        if (! empty($validated['section_id'])) {
            $sectionBelongsToClass = Section::forSchool(SchoolContext::id())
                ->where('id', $validated['section_id'])
                ->where('school_class_id', $validated['school_class_id'])
                ->exists();

            if (! $sectionBelongsToClass) {
                return back()->withErrors(['section_id' => 'Selected section does not belong to the selected class.']);
            }
        }

        ClassSubject::updateOrCreate(
            [
                'school_id' => SchoolContext::id(),
                'school_class_id' => $validated['school_class_id'],
                'section_id' => $validated['section_id'] ?? null,
                'subject_id' => $validated['subject_id'],
            ],
            [
                'teacher_id' => $validated['teacher_id'] ?? null,
                'is_active' => true,
            ],
        );

        Activity::log('class_subject_assigned', 'Subject assigned to class.', $validated);

        return back()->with('status', 'Subject assigned to class.');
    }

    public function destroyClassSubject(ClassSubject $classSubject): RedirectResponse
    {
        $this->authorizeSchoolModel($classSubject);
        $classSubject->delete();
        Activity::log('class_subject_removed', 'Class subject assignment removed.', ['class_subject_id' => $classSubject->id]);

        return back()->with('status', 'Subject assignment removed.');
    }

    private function authorizeSchoolModel(object $model): void
    {
        abort_unless((int) $model->school_id === SchoolContext::id(), 404);
    }
}
