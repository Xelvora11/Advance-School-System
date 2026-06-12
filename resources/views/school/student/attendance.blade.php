<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">My Attendance</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $student->name }} · {{ $student->registration_number }}</p>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
        <div class="app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Class</th>
                            <th class="text-left">Status</th>
                            <th class="text-left">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($attendance as $record)
                            <tr>
                                <td class="font-semibold">{{ $record->attendance_date->format('d M Y') }}</td>
                                <td>{{ $student->schoolClass?->name }} {{ $student->section?->name }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($record->status) {
                                        'present' => 'bg-green-100 text-green-700',
                                        'absent' => 'bg-red-100 text-red-700',
                                        'late' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    } }}">{{ Str::headline($record->status) }}</span>
                                </td>
                                <td>{{ optional($record->updated_at)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-gray-500">No attendance records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>{{ $attendance->links() }}</div>
    </div>
</x-app-layout>
