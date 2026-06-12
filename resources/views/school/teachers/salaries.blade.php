<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Teacher Finance
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Salary Management</h1>
                <p class="mt-1 text-sm text-gray-600">Generate monthly salary records, deductions, manual payments, and balances.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="app-button app-button-light">
                <i data-lucide="users" class="h-4 w-4"></i>
                Teachers
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['Total Teachers', $summary['totalTeachers'], 'users', false],
                ['Monthly Salary Amount', $summary['monthlySalaryAmount'], 'wallet', true],
                ['Paid This Month', $summary['paidThisMonth'], 'badge-dollar-sign', true],
                ['Pending This Month', $summary['pendingThisMonth'], 'receipt', true],
                ['Partial Payments', $summary['partialPayments'], 'circle-alert', false],
                ['Total Deductions', $summary['totalDeductions'], 'minus-circle', true],
            ] as [$label, $value, $icon, $money])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-lg bg-[#E8F4FE] p-2 text-[#1DA1F2]">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">{{ $money ? 'PKR '.number_format((float) $value, 2) : number_format((float) $value) }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <form method="POST" action="{{ route('salaries.generate') }}" class="app-card grid gap-3 p-5 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <h2 class="font-bold text-[#0B1F3A]">Generate Monthly Salaries</h2>
                    <p class="mt-1 text-sm text-gray-500">Uses each teacher basic salary and skips duplicates or teachers without salary.</p>
                </div>
                <input type="number" min="1" max="12" name="salary_month" value="{{ now()->month }}" required>
                <input type="number" min="2020" max="2100" name="salary_year" value="{{ now()->year }}" required>
                <select name="teacher_id" class="md:col-span-2">
                    <option value="">All teachers</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->basic_salary ? ' - PKR '.number_format((float) $teacher->basic_salary, 2) : ' - no salary set' }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 text-sm md:col-span-2">
                    <input type="hidden" name="active_only" value="0">
                    <input type="checkbox" name="active_only" value="1" checked>
                    Active teachers only
                </label>
                <button class="app-button app-button-dark md:col-span-2">
                    <i data-lucide="circle-plus" class="h-4 w-4"></i>
                    Generate Salary Records
                </button>
            </form>

            <form method="POST" action="{{ route('salaries.teacher.update') }}" class="app-card grid gap-3 p-5 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <h2 class="font-bold text-[#0B1F3A]">Assign / Update Teacher Salary</h2>
                    <p class="mt-1 text-sm text-gray-500">New salary generation uses the current basic salary only.</p>
                </div>
                <select name="teacher_id" class="md:col-span-2" required>
                    <option value="">Teacher</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0" name="basic_salary" required placeholder="Basic salary">
                <select name="salary_payment_method">
                    <option value="">Payment method</option>
                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="salary_effective_from" placeholder="Effective from">
                <textarea name="bank_account_note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Bank/account note optional"></textarea>
                <button class="app-button app-button-primary md:col-span-2">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Save Salary Settings
                </button>
            </form>

            <form class="app-card grid gap-3 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <h2 class="font-bold text-[#0B1F3A]">Filters</h2>
                    <p class="mt-1 text-sm text-gray-500">Review due, paid, partial, and deduction records.</p>
                </div>
                <select name="teacher_id">
                    <option value="">All teachers</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['unpaid', 'partial', 'paid'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="payment_method">
                    <option value="">All payment methods</option>
                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="number" min="1" max="12" name="month" value="{{ request('month') }}" placeholder="Month">
                <input type="number" min="2020" max="2100" name="year" value="{{ request('year') }}" placeholder="Year">
                <button class="app-button app-button-primary md:col-span-2">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Apply Filters
                </button>
            </form>
        </div>

        <div class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-bold text-[#0B1F3A]">Salary Table</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[1220px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Teacher</th>
                            <th class="text-left">Month</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Deductions</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-left">Payment</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($salaryRecords as $salary)
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ $salary->teacher?->name ?: 'Unknown teacher' }}</div>
                                    <div class="text-gray-500">{{ $salary->teacher?->email ?: 'No email' }}</div>
                                </td>
                                <td>{{ DateTime::createFromFormat('!m', (string) $salary->salary_month)->format('F') }} {{ $salary->salary_year }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->gross_salary, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->deductions, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->paid_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $salary->balance, 2) }}</td>
                                <td>{{ $salary->payment_method ? Str::headline($salary->payment_method) : '-' }}<div class="text-gray-500">{{ optional($salary->payment_date)->format('d M Y') }}</div></td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($salary->payment_status) {
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ ucfirst($salary->payment_status) }}</span>
                                </td>
                                <td class="text-right">
                                    <div x-data="{ open: false, payOpen: false, deductionOpen: false, editOpen: false }" class="relative inline-block">
                                        <button type="button" @click="open = !open" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Salary actions">
                                            <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                        </button>
                                        <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-2 w-52 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-lg">
                                            <a href="{{ route('salaries.show', $salary) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="eye" class="h-4 w-4"></i> View Details</a>
                                            @if ((float) $salary->balance > 0)
                                                <button type="button" @click="payOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="badge-dollar-sign" class="h-4 w-4"></i> Record Payment</button>
                                            @endif
                                            <button type="button" @click="deductionOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="minus-circle" class="h-4 w-4"></i> Add Deduction</button>
                                            @if ($salary->payment_status !== 'paid')
                                                <button type="button" @click="editOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="pencil" class="h-4 w-4"></i> Edit Record</button>
                                            @endif
                                            @if ($salary->teacher)
                                                <a href="{{ route('teachers.show', $salary->teacher) }}#salary" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="history" class="h-4 w-4"></i> View History</a>
                                            @endif
                                        </div>

                                        <div x-show="payOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                            <form method="POST" action="{{ route('salaries.payment', $salary) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                @csrf
                                                @method('PATCH')
                                                <h3 class="text-lg font-bold text-[#0B1F3A]">Record Salary Payment</h3>
                                                <p class="mt-1 text-sm text-gray-500">Balance: PKR {{ number_format((float) $salary->balance, 2) }}</p>
                                                <input type="number" step="0.01" min="1" max="{{ $salary->balance }}" name="amount" class="mt-4 rounded-md border-gray-300" required placeholder="Amount paid">
                                                <select name="payment_method" class="mt-3 rounded-md border-gray-300" required>
                                                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="mt-3 rounded-md border-gray-300" required>
                                                <input name="reference_number" class="mt-3 rounded-md border-gray-300" placeholder="Reference number optional">
                                                <textarea name="note" rows="2" class="mt-3 rounded-md border-gray-300" placeholder="Payment note optional"></textarea>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" @click="payOpen = false" class="app-button app-button-light">Cancel</button>
                                                    <button class="app-button app-button-primary">Save Payment</button>
                                                </div>
                                            </form>
                                        </div>

                                        <div x-show="deductionOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                            <form method="POST" action="{{ route('salaries.deduction', $salary) }}" class="w-full max-w-sm rounded-lg bg-white p-5 text-left shadow-xl">
                                                @csrf
                                                @method('PATCH')
                                                <h3 class="text-lg font-bold text-[#0B1F3A]">Add Deduction</h3>
                                                <input type="number" step="0.01" min="0" name="deductions" class="mt-4 rounded-md border-gray-300" required placeholder="Deduction amount">
                                                <input name="deduction_reason" class="mt-3 rounded-md border-gray-300" required placeholder="Reason">
                                                <textarea name="note" rows="2" class="mt-3 rounded-md border-gray-300" placeholder="Note optional"></textarea>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" @click="deductionOpen = false" class="app-button app-button-light">Cancel</button>
                                                    <button class="app-button app-button-dark">Add Deduction</button>
                                                </div>
                                            </form>
                                        </div>

                                        <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                            <form method="POST" action="{{ route('salaries.update', $salary) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                @csrf
                                                @method('PATCH')
                                                <h3 class="text-lg font-bold text-[#0B1F3A]">Edit Salary Record</h3>
                                                <input type="number" step="0.01" min="0" name="gross_salary" value="{{ $salary->gross_salary }}" class="mt-4 rounded-md border-gray-300" required>
                                                <textarea name="deduction_reason" rows="2" class="mt-3 rounded-md border-gray-300" placeholder="Deduction reason">{{ $salary->deduction_reason }}</textarea>
                                                <textarea name="note" rows="2" class="mt-3 rounded-md border-gray-300" placeholder="Note">{{ $salary->note }}</textarea>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" @click="editOpen = false" class="app-button app-button-light">Cancel</button>
                                                    <button class="app-button app-button-dark">Save Record</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-10 text-center text-gray-500">No salary records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-5 py-4">{{ $salaryRecords->links() }}</div>
        </div>
    </div>
</x-app-layout>
