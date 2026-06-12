<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#0B1F3A]">Marks Entry</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $exam->name }} · {{ $exam->schoolClass?->name }} {{ $exam->section?->name }}</p>
            </div>
            <a href="{{ route('exams.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">Back</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($exam->examSubjects->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">Add subjects before entering marks.</div>
        @else
            <form method="POST" action="{{ route('exams.marks.store', $exam) }}" class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                @foreach ($exam->examSubjects as $examSubject)
                                    <th class="px-5 py-3">{{ $examSubject->subject?->name }}<div class="font-normal">/{{ $examSubject->total_marks }}</div></th>
                                @endforeach
                                <th class="px-5 py-3">Marksheet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-5 py-4 font-medium">{{ $student->name }}<div class="text-xs font-normal text-gray-500">{{ $student->registration_number }}</div></td>
                                    @foreach ($exam->examSubjects as $examSubject)
                                        @php($key = $student->id.'-'.$examSubject->subject_id)
                                        <td class="px-5 py-4">
                                            <input type="number" step="0.01" min="0" max="{{ $examSubject->total_marks }}" name="marks[{{ $student->id }}][{{ $examSubject->subject_id }}]" value="{{ optional($marks->get($key))->marks_obtained }}" class="w-24 rounded-md border-gray-300 text-sm">
                                        </td>
                                    @endforeach
                                    <td class="px-5 py-4">
                                        <a href="{{ route('exams.marksheet', [$exam, $student]) }}" class="font-semibold text-[#1DA1F2]">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $exam->examSubjects->count() + 2 }}" class="px-5 py-10 text-center text-gray-500">No active students in this class.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-gray-200 px-5 py-4">
                    <button class="rounded-md bg-[#1DA1F2] px-5 py-2 text-sm font-semibold text-white">Save Marks</button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
