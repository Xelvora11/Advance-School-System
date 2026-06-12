<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">Exams & Results</h1>
        <p class="mt-1 text-sm text-gray-600">Create exams, add subjects, enter marks, and publish results.</p>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[.8fr_1.2fr] lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-[#0B1F3A]">Create Exam</h2>
            <form method="POST" action="{{ route('exams.store') }}" class="mt-4 grid gap-3">
                @csrf
                <input name="name" placeholder="First Term Exam" class="rounded-md border-gray-300" required>
                <select name="type" class="rounded-md border-gray-300">
                    <option value="monthly_test">Monthly Test</option>
                    <option value="term_exam">Term Exam</option>
                    <option value="final_exam">Final Exam</option>
                </select>
                <select name="school_class_id" class="rounded-md border-gray-300" required>
                    <option value="">Class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <select name="section_id" class="rounded-md border-gray-300">
                    <option value="">All sections</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="exam_date" class="rounded-md border-gray-300">
                <input name="session" placeholder="2026-2027" class="rounded-md border-gray-300">
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Create Exam</button>
            </form>
        </div>

        <div class="space-y-5">
            @forelse ($exams as $exam)
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-[#0B1F3A]">{{ $exam->name }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ $exam->schoolClass?->name }} {{ $exam->section?->name }} · {{ ucfirst(str_replace('_', ' ', $exam->type)) }}</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('exams.marks', $exam) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold">Marks</a>
                            <form method="POST" action="{{ route('exams.publish', $exam) }}">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-md bg-[#0B1F3A] px-3 py-2 text-sm font-semibold text-white">{{ $exam->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('exams.subjects.store', $exam) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                        @csrf
                        <select name="subject_id" class="rounded-md border-gray-300" required>
                            <option value="">Subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="1" name="total_marks" value="100" class="rounded-md border-gray-300" required>
                        <input type="number" step="0.01" min="0" name="passing_marks" value="33" class="rounded-md border-gray-300" required>
                        <button class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold">Add Subject</button>
                    </form>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse ($exam->examSubjects as $examSubject)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold">{{ $examSubject->subject?->name }} · {{ $examSubject->total_marks }}</span>
                        @empty
                            <span class="text-sm text-gray-500">No subjects added.</span>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white px-5 py-10 text-center text-gray-500">No exams created yet.</div>
            @endforelse
            {{ $exams->links() }}
        </div>
    </div>
</x-app-layout>
