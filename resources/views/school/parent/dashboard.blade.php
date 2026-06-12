<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">Parent Dashboard</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_.9fr] lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Linked Children</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    <div class="flex items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <div class="font-medium">{{ $student->name }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</div>
                        </div>
                        <a href="{{ route('parent.students.show', $student) }}" class="text-sm font-semibold text-[#1DA1F2]">Open</a>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No children linked to this parent account.</div>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Fee Status</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($fees as $fee)
                        <div class="px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $fee->student?->name }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ $fee->feeHead?->name }} · {{ $fee->month }}/{{ $fee->year }} · Balance PKR {{ number_format($fee->balance(), 2) }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ match($fee->status) {
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        'overdue' => 'bg-red-100 text-red-700',
                                        'carried_forward' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ $fee->status === 'carried_forward' ? 'Carried Forward' : ucfirst($fee->status) }}</span>
                                    <div class="mt-2 space-x-2 text-xs font-semibold">
                                        <a href="{{ route('parent.fees.challan', $fee) }}" class="text-[#1DA1F2]">Challan</a>
                                        @if ($fee->payments->last())
                                            <a href="{{ route('parent.fees.receipt', $fee->payments->last()) }}" class="text-[#1DA1F2]">Receipt</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-sm text-gray-500">No fee records available.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Notices</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($notices as $notice)
                        <div class="px-5 py-4">
                            <div class="font-medium">{{ $notice->title }}</div>
                            <p class="mt-1 text-sm text-gray-600">{{ $notice->body }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-sm text-gray-500">No notices yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
