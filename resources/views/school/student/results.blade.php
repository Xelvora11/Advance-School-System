<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">My Results</h1>
        <p class="mt-1 text-sm text-gray-600">Published exam results for {{ $student->name }}.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
        @forelse ($exams as $exam)
            @php
                $marks = $exam->marks;
                $obtained = $marks->sum('marks_obtained');
                $total = $marks->sum('total_marks');
                $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : null;
            @endphp
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-[#0B1F3A]">{{ $exam->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $exam->session ?: 'Session not set' }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-black text-[#0B1F3A]">{{ $obtained }} / {{ $total }}</div>
                        <div class="text-sm text-gray-500">{{ $percentage !== null ? $percentage.'%' : '-' }}</div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Subject</th>
                                <th class="text-right">Marks</th>
                                <th class="text-left">Grade</th>
                                <th class="text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($marks as $mark)
                                <tr>
                                    <td class="font-semibold">{{ $mark->subject?->name ?: 'Subject' }}</td>
                                    <td class="text-right">{{ $mark->marks_obtained }} / {{ $mark->total_marks }}</td>
                                    <td>{{ $mark->grade ?: '-' }}</td>
                                    <td>{{ $mark->remarks ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-gray-500">No marks entered for this exam yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="app-card px-5 py-12 text-center text-gray-500">No published results yet.</div>
        @endforelse
    </div>
</x-app-layout>
