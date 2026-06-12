<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-[#0B1F3A]">{{ $student->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</p>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Attendance</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($attendance as $record)
                    <div class="flex justify-between px-5 py-3 text-sm">
                        <span>{{ $record->attendance_date->format('d M Y') }}</span>
                        <span class="font-semibold">{{ ucfirst($record->status) }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No attendance records.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Fees</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($fees as $fee)
                    <div class="px-5 py-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ $fee->feeHead?->name }}</div>
                                <div class="text-gray-500">{{ $fee->month }}/{{ $fee->year }} · {{ $fee->status === 'carried_forward' ? 'Carried Forward' : ucfirst($fee->status) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">PKR {{ number_format($fee->balance(), 2) }}</div>
                                <a href="{{ route('parent.fees.challan', $fee) }}" class="text-xs font-semibold text-[#1DA1F2]">Challan</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No fee records.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Results</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($exams as $exam)
                    <div class="flex justify-between gap-3 px-5 py-4 text-sm">
                        <div>
                            <div class="font-medium">{{ $exam->name }}</div>
                            <div class="text-gray-500">{{ $exam->session }}</div>
                        </div>
                        <a href="{{ route('parent.exams.marksheet', [$exam, $student]) }}" class="font-semibold text-[#1DA1F2]">Marksheet</a>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No published results.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
