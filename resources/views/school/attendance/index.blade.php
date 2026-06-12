<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#0B1F3A]">Attendance</h1>
                <p class="mt-1 text-sm text-gray-600">Daily manual attendance with edit reasons.</p>
            </div>
            <a href="{{ route('attendance.create') }}" class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Mark Attendance</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <form class="mb-5 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-4">
            <input type="date" name="attendance_date" value="{{ $selectedDate->format('Y-m-d') }}" class="rounded-md border-gray-300">
            <select name="school_class_id" class="rounded-md border-gray-300">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('school_class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <select name="section_id" class="rounded-md border-gray-300">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-[#0B1F3A] px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Edit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-5 py-4">{{ $record->student?->name }}</td>
                            <td class="px-5 py-4">{{ $record->schoolClass?->name }} {{ $record->section?->name }}</td>
                            <td class="px-5 py-4">{{ $record->attendance_date->format('d M Y') }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold">{{ ucfirst($record->status) }}</span></td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('attendance.update', $record) }}" class="grid gap-2 md:grid-cols-[120px_1fr_auto]">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="rounded-md border-gray-300 text-sm">
                                        @foreach (['present', 'absent', 'late', 'leave'] as $status)
                                            <option value="{{ $status }}" @selected($record->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <input name="edit_reason" placeholder="Reason required" class="rounded-md border-gray-300 text-sm">
                                    <button class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No attendance records for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</x-app-layout>
