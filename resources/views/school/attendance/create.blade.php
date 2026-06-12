<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">Mark Attendance</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('attendance.create') }}" class="mb-5 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-4">
            <input type="date" name="attendance_date" value="{{ $selectedDate->format('Y-m-d') }}" class="rounded-md border-gray-300">
            <select name="school_class_id" class="rounded-md border-gray-300" required>
                <option value="">Select class</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <select name="section_id" class="rounded-md border-gray-300">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected($selectedSectionId == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-[#0B1F3A] px-4 py-2 text-sm font-semibold text-white">Load Students</button>
        </form>

        <form method="POST" action="{{ route('attendance.store') }}" class="rounded-lg border border-gray-200 bg-white">
            @csrf
            <input type="hidden" name="attendance_date" value="{{ $selectedDate->format('Y-m-d') }}">
            <input type="hidden" name="school_class_id" value="{{ $selectedClassId }}">
            <input type="hidden" name="section_id" value="{{ $selectedSectionId }}">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">{{ $students->count() }} students loaded</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <div class="font-medium">{{ $student->name }}</div>
                            <div class="text-sm text-gray-500">{{ $student->registration_number }} · Roll {{ $student->roll_number ?: '-' }}</div>
                        </div>
                        <div class="grid grid-cols-4 gap-2 text-sm">
                            @foreach (['present', 'absent', 'late', 'leave'] as $status)
                                <label class="rounded-md border border-gray-200 px-3 py-2 text-center">
                                    <input type="radio" name="attendance[{{ $student->id }}]" value="{{ $status }}" @checked($status === 'present') class="sr-only peer">
                                    <span class="peer-checked:font-bold peer-checked:text-[#1DA1F2]">{{ ucfirst($status) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-gray-500">Select a class to load students.</div>
                @endforelse
            </div>
            @if ($students->isNotEmpty())
                <div class="flex justify-end border-t border-gray-200 px-5 py-4">
                    <button class="rounded-md bg-[#1DA1F2] px-5 py-2 text-sm font-semibold text-white">Save Attendance</button>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
