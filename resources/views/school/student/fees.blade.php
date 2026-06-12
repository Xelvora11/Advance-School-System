<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">My Fees</h1>
        <p class="mt-1 text-sm text-gray-600">View fee records and payment history.</p>
    </x-slot>

    @php
        $assigned = $fees->sum(fn ($fee) => $fee->payableAmount());
        $paid = $fees->sum('paid_amount');
        $balance = $fees->sum(fn ($fee) => $fee->balance());
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ([['Assigned', $assigned], ['Paid', $paid], ['Balance', $balance]] as [$label, $value])
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-black text-[#0B1F3A]">PKR {{ number_format($value, 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-bold text-[#0B1F3A]">Fee Records</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[900px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Fee</th>
                            <th class="text-left">Month</th>
                            <th class="text-right">Payable</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-left">Due</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($fees as $fee)
                            <tr>
                                <td class="font-semibold">{{ $fee->feeHead?->name ?: 'Fee' }}</td>
                                <td>{{ $fee->month }}/{{ $fee->year }}</td>
                                <td class="text-right">PKR {{ number_format($fee->payableAmount(), 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->paid_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format($fee->balance(), 2) }}</td>
                                <td>{{ optional($fee->due_date)->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($fee->status) {
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        'overdue' => 'bg-red-100 text-red-700',
                                        'carried_forward' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ $fee->status === 'carried_forward' ? 'Carried Forward' : Str::headline($fee->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-gray-500">No fee records available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-bold text-[#0B1F3A]">Payment History</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm">
                        <div>
                            <div class="font-bold text-[#0B1F3A]">{{ $payment->receipt_number }}</div>
                            <div class="mt-1 text-gray-500">{{ optional($payment->paid_on)->format('d M Y') }} · {{ $payment->studentFee?->feeHead?->name ?: 'Fee' }} · {{ Str::headline($payment->method) }}</div>
                        </div>
                        <div class="font-black text-[#0B1F3A]">PKR {{ number_format((float) $payment->amount, 2) }}</div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No payment history yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
