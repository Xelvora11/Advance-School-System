<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">Notices</h1>
        <p class="mt-1 text-sm text-gray-600">Publish notices for parents, students, classes, sections, and staff.</p>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[.8fr_1.2fr] lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-[#0B1F3A]">Create Notice</h2>
            <form
                method="POST"
                action="{{ route('notices.store') }}"
                enctype="multipart/form-data"
                class="mt-4 grid gap-3"
                x-data="{ audience: '{{ old('audience_type', request('audience_type', 'all_parents')) }}' }"
            >
                @csrf
                <input name="title" value="{{ old('title') }}" placeholder="Notice title" class="rounded-md border-gray-300" required>
                <textarea name="body" rows="5" placeholder="Notice details" class="rounded-md border-gray-300" required>{{ old('body') }}</textarea>

                <label class="block text-sm font-medium text-gray-700">
                    Audience
                    <select name="audience_type" x-model="audience" class="mt-1 w-full rounded-md border-gray-300">
                        <option value="all_parents">All parents</option>
                        <option value="all_students">All students</option>
                        <option value="specific_parent">Specific parent</option>
                        <option value="specific_student">Specific student</option>
                        <option value="class">Specific class</option>
                        <option value="section">Specific section</option>
                        <option value="teachers">Teachers</option>
                        <option value="all">Everyone</option>
                    </select>
                </label>

                <label x-show="audience === 'class'" x-cloak class="block text-sm font-medium text-gray-700">
                    Class
                    <select name="school_class_id" class="mt-1 w-full rounded-md border-gray-300">
                        <option value="">Select class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label x-show="audience === 'section'" x-cloak class="block text-sm font-medium text-gray-700">
                    Section
                    <select name="section_id" class="mt-1 w-full rounded-md border-gray-300">
                        <option value="">Select section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>{{ $section->schoolClass?->name ? $section->schoolClass->name.' - ' : '' }}{{ $section->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label x-show="audience === 'specific_student'" x-cloak class="block text-sm font-medium text-gray-700">
                    Student
                    <select name="target_student_id" class="mt-1 w-full rounded-md border-gray-300">
                        <option value="">Select student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected((string) old('target_student_id', request('student_id')) === (string) $student->id)>
                                {{ $student->name }} - {{ $student->registration_number }} - {{ $student->schoolClass?->name }} {{ $student->section?->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label x-show="audience === 'specific_parent'" x-cloak class="block text-sm font-medium text-gray-700">
                    Parent
                    <select name="target_user_id" class="mt-1 w-full rounded-md border-gray-300">
                        <option value="">Select parent</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->user_id }}" @selected(old('target_user_id') == $parent->user_id)>
                                {{ $parent->name }} - {{ $parent->email ?: $parent->user?->email }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <input type="file" name="attachment" class="rounded-md border border-gray-300 px-3 py-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="rounded border-gray-300 text-[#1DA1F2]">
                    Publish now
                </label>
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Save Notice</button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">All Notices</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($notices as $notice)
                    @php
                        $audienceLabel = match ($notice->audience_type) {
                            'class' => 'Class: '.($notice->schoolClass?->name ?: 'Unknown class'),
                            'section' => 'Section: '.($notice->section?->schoolClass?->name ? $notice->section->schoolClass->name.' - ' : '').($notice->section?->name ?: 'Unknown section'),
                            'specific_student' => 'Student: '.($notice->targetStudent?->name ?: 'Unknown student'),
                            'specific_parent' => 'Parent: '.($notice->targetUser?->name ?: 'Unknown parent'),
                            default => Str::headline($notice->audience_type),
                        };
                    @endphp
                    <div class="p-5">
                        <form method="POST" action="{{ route('notices.update', $notice) }}" class="grid gap-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                                <input name="title" value="{{ $notice->title }}" class="rounded-md border-gray-300 font-medium">
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                                    <input type="checkbox" name="is_published" value="1" @checked($notice->is_published) class="rounded border-gray-300 text-[#1DA1F2]">
                                    Published
                                </label>
                            </div>
                            <textarea name="body" rows="3" class="rounded-md border-gray-300">{{ $notice->body }}</textarea>
                            <input type="hidden" name="audience_type" value="{{ $notice->audience_type }}">
                            <input type="hidden" name="school_class_id" value="{{ $notice->school_class_id }}">
                            <input type="hidden" name="section_id" value="{{ $notice->section_id }}">
                            <input type="hidden" name="target_student_id" value="{{ $notice->target_student_id }}">
                            <input type="hidden" name="target_user_id" value="{{ $notice->target_user_id }}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="text-sm text-gray-500">{{ $audienceLabel }} · {{ optional($notice->published_at)->format('d M Y') ?: 'Draft' }}</span>
                                <div class="flex gap-2">
                                    <button class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-gray-500">No notices yet.</div>
                @endforelse
            </div>
            <div class="border-t border-gray-200 px-5 py-4">{{ $notices->links() }}</div>
        </div>
    </div>
</x-app-layout>
