<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                    Student Records
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Students</h1>
                <p class="mt-1 text-sm text-gray-600">Active, inactive, left, transferred, and graduated students.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.students.csv') }}" class="app-button app-button-light">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    Export CSV
                </a>
                <a href="{{ route('students.create') }}" class="app-button app-button-primary">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Add Student
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <form class="app-card grid gap-3 p-4 md:grid-cols-6">
            <div class="md:col-span-2">
                <input name="search" value="{{ request('search') }}" placeholder="Search name, registration, phone">
            </div>
            <select name="class_id">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <select name="section_id">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All status</option>
                @foreach (['active', 'inactive', 'left', 'transferred', 'graduated'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="app-button app-button-dark">
                <i data-lucide="search" class="h-4 w-4"></i>
                Filter
            </button>
        </form>

        <div class="app-card overflow-visible">
            <div class="overflow-x-auto">
            <table class="app-table min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Student</th>
                        <th class="text-left">Class</th>
                        <th class="text-left">Guardian</th>
                        <th class="text-left">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($students as $student)
                        <tr class="hover:bg-gray-50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#0B1F3A] text-sm font-bold text-white">{{ Str::of($student->name)->substr(0, 1) }}</div>
                                    <div>
                                        <div class="font-bold text-gray-900">{{ $student->name }}</div>
                                        <div class="text-gray-500">{{ $student->registration_number }} · Roll {{ $student->roll_number ?: '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $student->schoolClass?->name ?: '-' }} {{ $student->section?->name }}</td>
                            <td>{{ $student->guardian_name ?: '-' }}<div class="text-gray-500">{{ $student->guardian_phone }}</div></td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $student->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($student->status) }}</span>
                            </td>
                            <td class="text-right">
                                <div
                                    x-data="{
                                        open: false,
                                        menuStyle: '',
                                        toggle($refs) {
                                            if (this.open) {
                                                this.open = false;
                                                return;
                                            }

                                            const rect = $refs.button.getBoundingClientRect();
                                            const width = 208;
                                            const height = 292;
                                            const gap = 8;
                                            const top = rect.bottom + height + gap > window.innerHeight
                                                ? Math.max(gap, rect.top - height - gap)
                                                : rect.bottom + gap;
                                            const left = Math.min(window.innerWidth - width - gap, Math.max(gap, rect.right - width));

                                            this.menuStyle = `position: fixed; top: ${top}px; left: ${left}px; width: ${width}px;`;
                                            this.open = true;
                                            this.$nextTick(() => window.lucide && window.lucide.createIcons());
                                        }
                                    }"
                                    @keydown.escape.window="open = false"
                                    @scroll.window="open = false"
                                    @resize.window="open = false"
                                    class="relative inline-block"
                                >
                                    <button x-ref="button" @click="toggle($refs)" type="button" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Actions">
                                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open" x-cloak @click.outside="open = false" :style="menuStyle" class="z-[9999] rounded-lg border border-gray-200 bg-white py-1 text-left shadow-xl">
                                        <a href="{{ route('students.show', $student) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="eye" class="h-4 w-4"></i> View Profile</a>
                                        <a href="{{ route('students.edit', $student) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="pencil" class="h-4 w-4"></i> Edit</a>
                                        <a href="{{ route('students.show', $student) }}#attendance" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="calendar-check" class="h-4 w-4"></i> Attendance</a>
                                        <a href="{{ route('students.show', $student) }}#fees" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="receipt" class="h-4 w-4"></i> Fees</a>
                                        <a href="{{ route('students.show', $student) }}#results" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="clipboard-list" class="h-4 w-4"></i> Results</a>
                                        <a href="{{ route('students.show', $student) }}#access" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="user-plus" class="h-4 w-4"></i> Parent Login</a>
                                        <a href="{{ route('students.show', $student) }}#access" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="graduation-cap" class="h-4 w-4"></i> Student Login</a>
                                        <a href="{{ route('notices.index', ['audience_type' => 'specific_student', 'student_id' => $student->id]) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="megaphone" class="h-4 w-4"></i> Send Notice</a>
                                        <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Mark this student inactive?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-700 hover:bg-red-50"><i data-lucide="user-x" class="h-4 w-4"></i> Change Status</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-gray-500">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div>{{ $students->links() }}</div>
    </div>
</x-app-layout>
