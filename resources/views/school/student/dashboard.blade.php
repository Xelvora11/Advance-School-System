<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                Student Portal
            </div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Welcome, {{ $student->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</p>
        </div>
    </x-slot>

    @php
        $feeBalance = $fees->sum(fn ($fee) => $fee->balance());
        $present = (int) ($attendanceSummary['present'] ?? 0);
        $absent = (int) ($attendanceSummary['absent'] ?? 0);
        $late = (int) ($attendanceSummary['late'] ?? 0);
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-4">
            @foreach ([
                ['Present', $present, 'calendar-check', 'text-green-700 bg-green-50'],
                ['Absent', $absent, 'calendar-x', 'text-red-700 bg-red-50'],
                ['Late', $late, 'clock', 'text-amber-700 bg-amber-50'],
                ['Fee Balance', 'PKR '.number_format($feeBalance, 2), 'wallet', 'text-[#1DA1F2] bg-blue-50'],
            ] as [$label, $value, $icon, $style])
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-gray-500">{{ $label }}</span>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $style }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Recent Fees</h2>
                    <a href="{{ route('student.fees') }}" class="text-sm font-bold text-[#1DA1F2]">View all</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($fees as $fee)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm">
                            <div>
                                <div class="font-bold text-[#0B1F3A]">{{ $fee->feeHead?->name ?: 'Fee' }}</div>
                                <div class="mt-1 text-gray-500">{{ $fee->month }}/{{ $fee->year }} · Due {{ optional($fee->due_date)->format('d M Y') ?: '-' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-[#0B1F3A]">PKR {{ number_format($fee->balance(), 2) }}</div>
                                <span class="mt-1 inline-block rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600">{{ $fee->status === 'carried_forward' ? 'Carried Forward' : Str::headline($fee->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-sm text-gray-500">No fee records available.</div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 class="font-bold text-[#0B1F3A]">Published Results</h2>
                        <a href="{{ route('student.results') }}" class="text-sm font-bold text-[#1DA1F2]">View all</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($exams as $exam)
                            @php($marks = $exam->marks)
                            <div class="px-5 py-4 text-sm">
                                <div class="font-bold text-[#0B1F3A]">{{ $exam->name }}</div>
                                <div class="mt-1 text-gray-500">{{ $exam->session ?: 'Session not set' }} · {{ $marks->sum('marks_obtained') }} / {{ $marks->sum('total_marks') }}</div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-gray-500">No published results yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 class="font-bold text-[#0B1F3A]">Notices</h2>
                        <a href="{{ route('student.notices') }}" class="text-sm font-bold text-[#1DA1F2]">View all</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($notices as $notice)
                            <div class="px-5 py-4 text-sm">
                                <div class="font-bold text-[#0B1F3A]">{{ $notice->title }}</div>
                                <p class="mt-1 text-gray-600">{{ Str::limit($notice->body, 120) }}</p>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-gray-500">No notices yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
