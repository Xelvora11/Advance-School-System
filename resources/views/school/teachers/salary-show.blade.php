<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                    Salary Record
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $salary->teacher?->name ?: 'Teacher' }} - {{ $salary->salary_month }}/{{ $salary->salary_year }}</h1>
                <p class="mt-1 text-sm text-gray-600">Created {{ optional($salary->created_at)->format('d M Y') }}{{ $salary->creator?->name ? ' by '.$salary->creator->name : '' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($salary->teacher)
                    <a href="{{ route('teachers.show', $salary->teacher) }}#salary" class="app-button app-button-light">
                        <i data-lucide="user-round-check" class="h-4 w-4"></i>
                        Teacher Profile
                    </a>
                @endif
                <a href="{{ route('salaries.index', ['teacher_id' => $salary->teacher_id]) }}" class="app-button app-button-primary">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to Salaries
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['Gross Salary', $salary->gross_salary, 'wallet'],
                ['Deductions', $salary->deductions, 'minus-circle'],
                ['Paid Amount', $salary->paid_amount, 'badge-dollar-sign'],
                ['Balance', $salary->balance, 'receipt'],
                ['Status', ucfirst($salary->payment_status), 'circle-alert'],
                ['Last Payment', optional($salary->payment_date)->format('d M Y') ?: '-', 'calendar-check'],
            ] as [$label, $value, $icon])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-lg bg-[#E8F4FE] p-2 text-[#1DA1F2]">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xl font-black text-[#0B1F3A]">
                        {{ is_numeric($value) ? 'PKR '.number_format((float) $value, 2) : $value }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <form method="POST" action="{{ route('salaries.payment', $salary) }}" class="app-card grid gap-3 p-5">
                @csrf
                @method('PATCH')
                <h2 class="font-bold text-[#0B1F3A]">Record Payment</h2>
                <input type="number" step="0.01" min="1" max="{{ $salary->balance }}" name="amount" required placeholder="Amount paid">
                <select name="payment_method" required>
                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                <input name="reference_number" placeholder="Reference number optional">
                <textarea name="note" rows="2" class="rounded-md border-gray-300" placeholder="Note optional"></textarea>
                <button class="app-button app-button-primary" @disabled((float) $salary->balance <= 0)>
                    <i data-lucide="badge-dollar-sign" class="h-4 w-4"></i>
                    Save Payment
                </button>
            </form>

            <form method="POST" action="{{ route('salaries.deduction', $salary) }}" class="app-card grid gap-3 p-5">
                @csrf
                @method('PATCH')
                <h2 class="font-bold text-[#0B1F3A]">Add Deduction</h2>
                <input type="number" step="0.01" min="0.01" name="deductions" required placeholder="Deduction amount">
                <input name="deduction_reason" required placeholder="Leave deduction, advance adjustment, other">
                <textarea name="note" rows="2" class="rounded-md border-gray-300" placeholder="Note optional"></textarea>
                <button class="app-button app-button-dark">
                    <i data-lucide="minus-circle" class="h-4 w-4"></i>
                    Add Deduction
                </button>
            </form>

            <form method="POST" action="{{ route('salaries.update', $salary) }}" class="app-card grid gap-3 p-5">
                @csrf
                @method('PATCH')
                <h2 class="font-bold text-[#0B1F3A]">Edit Record</h2>
                <input type="number" step="0.01" min="0" name="gross_salary" value="{{ $salary->gross_salary }}" required>
                <textarea name="deduction_reason" rows="2" class="rounded-md border-gray-300" placeholder="Deduction summary">{{ $salary->deduction_reason }}</textarea>
                <textarea name="note" rows="2" class="rounded-md border-gray-300" placeholder="Record note">{{ $salary->note }}</textarea>
                <button class="app-button app-button-light" @disabled($salary->payment_status === 'paid')>
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Save Record
                </button>
            </form>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Payment Entries</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[760px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Date</th>
                                <th class="text-left">Method</th>
                                <th class="text-left">Reference</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salary->paymentEntries as $entry)
                                <tr>
                                    <td>{{ optional($entry->payment_date)->format('d M Y') }}</td>
                                    <td>{{ Str::headline($entry->payment_method) }}</td>
                                    <td>{{ $entry->reference_number ?: '-' }}<div class="text-gray-500">{{ $entry->note }}</div></td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $entry->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-gray-500">No payment entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Deduction Entries</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[760px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Reason</th>
                                <th class="text-left">Note</th>
                                <th class="text-left">Created</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salary->deductionEntries as $entry)
                                <tr>
                                    <td class="font-semibold">{{ $entry->reason }}</td>
                                    <td>{{ $entry->note ?: '-' }}</td>
                                    <td>{{ optional($entry->created_at)->format('d M Y') }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $entry->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-gray-500">No deductions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
