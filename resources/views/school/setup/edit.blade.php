<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">School Setup</h1>
        <p class="mt-1 text-sm text-gray-600">Profile, document branding, and default template options.</p>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('school.setup.update') }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-medium">School name
                    <input name="name" value="{{ old('name', $school->name) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                </label>
                <label class="block text-sm font-medium">Short name
                    <input name="short_name" value="{{ old('short_name', $school->short_name) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Email
                    <input type="email" name="email" value="{{ old('email', $school->email) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Phone
                    <input name="phone" value="{{ old('phone', $school->phone) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Principal name
                    <input name="principal_name" value="{{ old('principal_name', $school->principal_name) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Academic year
                    <input name="academic_year" value="{{ old('academic_year', $school->academic_year) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Default fee due day
                    <input type="number" min="1" max="28" name="default_fee_due_day" value="{{ old('default_fee_due_day', $school->default_fee_due_day) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Primary color
                    <input name="primary_color" value="{{ old('primary_color', $school->primary_color) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Secondary color
                    <input name="secondary_color" value="{{ old('secondary_color', $school->secondary_color) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Logo
                    <input type="file" name="logo" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="block text-sm font-medium">Principal signature
                    <input type="file" name="signature" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="block text-sm font-medium">School stamp
                    <input type="file" name="stamp" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            </div>

            <label class="block text-sm font-medium">Address
                <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('address', $school->address) }}</textarea>
            </label>

            <div class="border-t border-gray-200 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-[#0B1F3A]">Marksheet Template</h2>
                        <p class="mt-1 text-sm text-gray-500">Board-style marksheet remains the default; use these switches for school branding.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('school.setup.preview', 'marksheet') }}" target="_blank" class="app-button app-button-light">
                            <i data-lucide="file-text" class="h-4 w-4"></i>
                            Preview Marksheet
                        </a>
                        <a href="{{ route('school.setup.preview', 'challan') }}" target="_blank" class="app-button app-button-light">
                            <i data-lucide="receipt" class="h-4 w-4"></i>
                            Preview Fee Challan
                        </a>
                        <a href="{{ route('school.setup.preview', 'receipt') }}" target="_blank" class="app-button app-button-light">
                            <i data-lucide="badge-dollar-sign" class="h-4 w-4"></i>
                            Preview Fee Receipt
                        </a>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium">Header text
                        <input name="header_text" value="{{ old('header_text', $template->header_text) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Footer text
                        <input name="footer_text" value="{{ old('footer_text', $template->footer_text) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach (['show_grading_table' => 'Show grading table', 'show_attendance_summary' => 'Show attendance summary', 'show_teacher_remarks' => 'Show teacher remarks', 'show_principal_remarks' => 'Show principal remarks'] as $field => $label)
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $template->{$field})) class="rounded border-gray-300 text-[#1DA1F2]">
                            {{ $label }}
                        </label>
                    @endforeach
                    <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                        <input type="checkbox" name="show_school_stamp" value="1" @checked(old('show_school_stamp', data_get($template->extra_settings, 'show_school_stamp', true))) class="rounded border-gray-300 text-[#1DA1F2]">
                        Show school stamp
                    </label>
                    <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                        <input type="checkbox" name="show_principal_signature" value="1" @checked(old('show_principal_signature', data_get($template->extra_settings, 'show_principal_signature', true))) class="rounded border-gray-300 text-[#1DA1F2]">
                        Show principal signature
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-[#0B1F3A]">Messaging Settings</h2>
                <p class="mt-1 text-sm text-gray-500">Keep parent-teacher communication controlled for your school.</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ([
                        'allow_parent_to_admin_messages' => 'Allow parents to message admin/principal',
                        'allow_parent_to_teacher_messages' => 'Allow parents to message linked teachers',
                        'allow_teacher_to_parent_messages' => 'Allow teachers to message parents in assigned classes',
                        'allow_student_messages' => 'Allow student messages to admin/teachers',
                        'allow_teacher_class_notices' => 'Allow teachers to send class notices',
                        'allow_attachments_in_messages' => 'Allow message attachments',
                    ] as $field => $label)
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $messageSettings->{$field})) class="rounded border-gray-300 text-[#1DA1F2]">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium">Attachment max size (KB)
                        <input type="number" min="512" max="5120" name="message_attachment_max_size" value="{{ old('message_attachment_max_size', $messageSettings->message_attachment_max_size) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Retention days
                        <input type="number" min="30" max="3650" name="message_retention_days" value="{{ old('message_retention_days', $messageSettings->message_retention_days) }}" placeholder="Optional" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button class="rounded-md bg-[#1DA1F2] px-5 py-2 text-sm font-semibold text-white">Save School Settings</button>
            </div>
        </form>
    </div>
</x-app-layout>
