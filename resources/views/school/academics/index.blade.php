<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">Academics</h1>
        <p class="mt-1 text-sm text-gray-600">Classes, sections, subjects, and class teacher assignment.</p>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-[#0B1F3A]">Add Class</h2>
            <form method="POST" action="{{ route('academics.classes.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" placeholder="Class name" class="w-full rounded-md border-gray-300" required>
                <input name="code" placeholder="Code" class="w-full rounded-md border-gray-300">
                <input type="number" name="sort_order" placeholder="Sort order" class="w-full rounded-md border-gray-300">
                <button class="w-full rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Add Class</button>
            </form>
            <div class="mt-5 divide-y divide-gray-100">
                @forelse ($classes as $class)
                    <div class="py-3">
                        <form method="POST" action="{{ route('academics.classes.update', $class) }}" class="grid gap-2">
                            @csrf
                            @method('PUT')
                            <input name="name" value="{{ $class->name }}" class="rounded-md border-gray-300 text-sm">
                            <div class="grid grid-cols-2 gap-2">
                                <input name="code" value="{{ $class->code }}" class="rounded-md border-gray-300 text-sm">
                                <input type="number" name="sort_order" value="{{ $class->sort_order }}" class="rounded-md border-gray-300 text-sm">
                            </div>
                            <label class="text-xs"><input type="checkbox" name="is_active" value="1" @checked($class->is_active)> Active · {{ $class->students_count }} students</label>
                            <button class="rounded-md border border-gray-300 px-3 py-1 text-xs font-semibold">Save</button>
                        </form>
                    </div>
                @empty
                    <p class="py-6 text-sm text-gray-500">No classes yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-[#0B1F3A]">Add Section</h2>
            <form method="POST" action="{{ route('academics.sections.store') }}" class="mt-4 space-y-3">
                @csrf
                <select name="school_class_id" class="w-full rounded-md border-gray-300" required>
                    <option value="">Select class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <input name="name" placeholder="Section name" class="w-full rounded-md border-gray-300" required>
                <select name="class_teacher_id" class="w-full rounded-md border-gray-300">
                    <option value="">Class teacher</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <button class="w-full rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Add Section</button>
            </form>
            <div class="mt-5 divide-y divide-gray-100">
                @forelse ($sections as $section)
                    <div class="py-3 text-sm">
                        <div class="font-medium">{{ $section->schoolClass?->name }} - {{ $section->name }}</div>
                        <div class="text-gray-500">{{ $section->classTeacher?->name ?: 'No class teacher' }}</div>
                    </div>
                @empty
                    <p class="py-6 text-sm text-gray-500">No sections yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-[#0B1F3A]">Add Subject</h2>
            <form method="POST" action="{{ route('academics.subjects.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" placeholder="Subject name" class="w-full rounded-md border-gray-300" required>
                <input name="code" placeholder="Code" class="w-full rounded-md border-gray-300">
                <button class="w-full rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Add Subject</button>
            </form>
            <div class="mt-5 divide-y divide-gray-100">
                @forelse ($subjects as $subject)
                    <div class="py-3">
                        <form method="POST" action="{{ route('academics.subjects.update', $subject) }}" class="flex gap-2">
                            @csrf
                            @method('PUT')
                            <input name="name" value="{{ $subject->name }}" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
                            <button class="rounded-md border border-gray-300 px-3 py-1 text-xs font-semibold">Save</button>
                        </form>
                    </div>
                @empty
                    <p class="py-6 text-sm text-gray-500">No subjects yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 lg:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-[#0B1F3A]">Assign Subjects to Classes</h2>
                    <p class="mt-1 text-sm text-gray-500">Subjects stay global, then get assigned to classes or sections. Teacher is optional.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $classSubjects->count() }} assigned</span>
            </div>
            <form method="POST" action="{{ route('academics.class-subjects.store') }}" class="mt-4 grid gap-3 md:grid-cols-5">
                @csrf
                <select name="school_class_id" class="rounded-md border-gray-300" required>
                    <option value="">Class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <select name="section_id" class="rounded-md border-gray-300">
                    <option value="">All sections</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->schoolClass?->name }} - {{ $section->name }}</option>
                    @endforeach
                </select>
                <select name="subject_id" class="rounded-md border-gray-300" required>
                    <option value="">Subject</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
                <select name="teacher_id" class="rounded-md border-gray-300">
                    <option value="">Subject teacher optional</option>
                    @foreach ($subjectTeachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Assign Subject</button>
            </form>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                            <th class="px-4 py-3">Class</th>
                            <th class="px-4 py-3">Section</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Teacher</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($classSubjects as $assignment)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $assignment->schoolClass?->name }}</td>
                                <td class="px-4 py-3">{{ $assignment->section?->name ?: 'All sections' }}</td>
                                <td class="px-4 py-3">{{ $assignment->subject?->name }}</td>
                                <td class="px-4 py-3">{{ $assignment->teacher?->name ?: 'Not assigned' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('academics.class-subjects.destroy', $assignment) }}" onsubmit="return confirm('Remove this subject assignment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-bold text-red-600">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No class subject assignments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
