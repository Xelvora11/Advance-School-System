<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        return view('school.notices.index', [
            'notices' => Notice::with(['schoolClass', 'section', 'targetStudent', 'targetUser'])->forSchool(SchoolContext::id())->latest()->paginate(15),
            'classes' => SchoolClass::forSchool(SchoolContext::id())->orderBy('sort_order')->get(),
            'sections' => Section::with('schoolClass')->forSchool(SchoolContext::id())->orderBy('name')->get(),
            'students' => Student::with(['schoolClass', 'section'])->forSchool(SchoolContext::id())->orderBy('name')->get(),
            'parents' => Guardian::with('user')
                ->forSchool(SchoolContext::id())
                ->whereNotNull('user_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated = $this->normalizeAudience($validated);
        $validated['school_id'] = SchoolContext::id();
        $validated['created_by'] = SchoolContext::user()->id;
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['is_published'] ? now() : null;

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('notices/'.SchoolContext::id(), 'public');
        }

        $notice = Notice::create($validated);
        Activity::log('notice_created', 'Notice created: '.$notice->title, ['notice_id' => $notice->id]);

        return back()->with('status', 'Notice saved.');
    }

    public function update(Request $request, Notice $notice): RedirectResponse
    {
        abort_unless((int) $notice->school_id === SchoolContext::id(), 404);

        $validated = $this->validated($request);
        $validated = $this->normalizeAudience($validated);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['is_published'] ? ($notice->published_at ?: now()) : null;

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('notices/'.SchoolContext::id(), 'public');
        }

        $notice->update($validated);
        Activity::log('notice_updated', 'Notice updated: '.$notice->title, ['notice_id' => $notice->id]);

        return back()->with('status', 'Notice updated.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        abort_unless((int) $notice->school_id === SchoolContext::id(), 404);
        $notice->delete();
        Activity::log('notice_deleted', 'Notice deleted.');

        return back()->with('status', 'Notice deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
            'audience_type' => ['required', Rule::in(['all', 'all_parents', 'all_students', 'class', 'section', 'teachers', 'specific_student', 'specific_parent'])],
            'school_class_id' => ['nullable', 'required_if:audience_type,class', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', 'required_if:audience_type,section', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'target_student_id' => ['nullable', 'required_if:audience_type,specific_student', Rule::exists('students', 'id')->where('school_id', SchoolContext::id())],
            'target_user_id' => [
                'nullable',
                'required_if:audience_type,specific_parent',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('school_id', SchoolContext::id())
                    ->where('role', User::ROLE_PARENT)),
            ],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:4096'],
        ]);
    }

    private function normalizeAudience(array $validated): array
    {
        $type = $validated['audience_type'];

        if ($type !== 'class') {
            $validated['school_class_id'] = null;
        }

        if ($type !== 'section') {
            $validated['section_id'] = null;
        }

        if ($type !== 'specific_student') {
            $validated['target_student_id'] = null;
        }

        if ($type !== 'specific_parent') {
            $validated['target_user_id'] = null;
        }

        return $validated;
    }
}
