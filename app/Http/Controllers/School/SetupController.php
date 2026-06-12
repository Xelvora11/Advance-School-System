<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\MessageSetting;
use App\Models\SchoolTemplateSetting;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function edit(): View
    {
        return view('school.setup.edit', [
            'school' => SchoolContext::school(),
            'template' => SchoolTemplateSetting::firstOrCreate([
                'school_id' => SchoolContext::id(),
                'template_type' => 'marksheet',
            ]),
            'messageSettings' => MessageSetting::forSchoolId(SchoolContext::id()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = SchoolContext::school();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:50'],
            'default_fee_due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'primary_color' => ['required', 'string', 'max:20'],
            'secondary_color' => ['required', 'string', 'max:20'],
            'header_text' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'show_grading_table' => ['nullable', 'boolean'],
            'show_attendance_summary' => ['nullable', 'boolean'],
            'show_teacher_remarks' => ['nullable', 'boolean'],
            'show_principal_remarks' => ['nullable', 'boolean'],
            'show_school_stamp' => ['nullable', 'boolean'],
            'show_principal_signature' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'signature' => ['nullable', 'image', 'max:2048'],
            'stamp' => ['nullable', 'image', 'max:2048'],
            'allow_parent_to_admin_messages' => ['nullable', 'boolean'],
            'allow_parent_to_teacher_messages' => ['nullable', 'boolean'],
            'allow_teacher_to_parent_messages' => ['nullable', 'boolean'],
            'allow_student_messages' => ['nullable', 'boolean'],
            'allow_teacher_class_notices' => ['nullable', 'boolean'],
            'allow_attachments_in_messages' => ['nullable', 'boolean'],
            'message_attachment_max_size' => ['required', 'integer', 'min:512', 'max:5120'],
            'message_retention_days' => ['nullable', 'integer', 'min:30', 'max:3650'],
        ]);

        foreach (['logo' => 'logo_path', 'signature' => 'signature_path', 'stamp' => 'stamp_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $validated[$column] = $request->file($input)->store('schools/'.$school->id, 'public');
            }
        }

        $school->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'principal_name' => $validated['principal_name'] ?? null,
            'academic_year' => $validated['academic_year'] ?? null,
            'default_fee_due_day' => $validated['default_fee_due_day'],
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'],
            'logo_path' => $validated['logo_path'] ?? $school->logo_path,
            'signature_path' => $validated['signature_path'] ?? $school->signature_path,
            'stamp_path' => $validated['stamp_path'] ?? $school->stamp_path,
            'setup_completed' => true,
        ]);

        SchoolTemplateSetting::updateOrCreate(
            ['school_id' => $school->id, 'template_type' => 'marksheet'],
            [
                'header_text' => $validated['header_text'] ?? null,
                'footer_text' => $validated['footer_text'] ?? null,
                'show_grading_table' => $request->boolean('show_grading_table'),
                'show_attendance_summary' => $request->boolean('show_attendance_summary'),
                'show_teacher_remarks' => $request->boolean('show_teacher_remarks'),
                'show_principal_remarks' => $request->boolean('show_principal_remarks'),
                'extra_settings' => [
                    'show_school_stamp' => $request->boolean('show_school_stamp'),
                    'show_principal_signature' => $request->boolean('show_principal_signature'),
                ],
            ],
        );

        MessageSetting::updateOrCreate(
            ['school_id' => $school->id],
            [
                'allow_parent_to_admin_messages' => $request->boolean('allow_parent_to_admin_messages'),
                'allow_parent_to_teacher_messages' => $request->boolean('allow_parent_to_teacher_messages'),
                'allow_teacher_to_parent_messages' => $request->boolean('allow_teacher_to_parent_messages'),
                'allow_student_messages' => $request->boolean('allow_student_messages'),
                'allow_teacher_class_notices' => $request->boolean('allow_teacher_class_notices'),
                'allow_attachments_in_messages' => $request->boolean('allow_attachments_in_messages'),
                'message_attachment_max_size' => $validated['message_attachment_max_size'],
                'message_retention_days' => $validated['message_retention_days'] ?? null,
            ],
        );

        Activity::log('school_setup_updated', 'School setup and document template settings updated.');

        return redirect()->route('school.dashboard')->with('status', 'School settings saved.');
    }

    public function preview(string $type): View
    {
        abort_unless(in_array($type, ['marksheet', 'challan', 'receipt'], true), 404);

        return view('school.setup.preview', [
            'school' => SchoolContext::school(),
            'template' => SchoolTemplateSetting::firstOrCreate([
                'school_id' => SchoolContext::id(),
                'template_type' => 'marksheet',
            ]),
            'type' => $type,
        ]);
    }
}
