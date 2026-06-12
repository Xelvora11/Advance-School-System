@php
    $currentSalaryRecords = $teacher->salaryPayments
        ->where('salary_month', now()->month)
        ->where('salary_year', now()->year);
    $currentSalary = $currentSalaryRecords->first();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="user-round-check" class="h-4 w-4"></i>
                    Teacher Profile
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $teacher->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $teacher->qualification ?: 'Qualification not set' }}</p>
            </div>
            <a href="{{ route('teachers.edit', $teacher) }}" class="app-button app-button-primary">
                <i data-lucide="pencil" class="h-4 w-4"></i>
                Edit Teacher
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="app-card flex flex-wrap gap-2 p-3">
            @foreach ([['Basic Info', 'basic-info'], ['Assignments', 'assignments'], ['Salary', 'salary'], ['Login', 'login']] as [$label, $target])
                <a href="#{{ $target }}" class="rounded-md px-3 py-2 text-sm font-bold text-gray-700 hover:bg-blue-50 hover:text-[#0B1F3A]">{{ $label }}</a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[.8fr_1fr]">
            <div id="basic-info" class="app-card scroll-mt-24 p-6">
                <h2 class="font-semibold text-[#0B1F3A]">Basic Info</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    @foreach ([
                        ['Email', $teacher->email],
                        ['Phone', $teacher->phone],
                        ['CNIC', $teacher->cnic],
                        ['Qualification', $teacher->qualification],
                        ['Joining', optional($teacher->joining_date)->format('d M Y')],
                        ['Basic salary', $teacher->basic_salary ? 'PKR '.number_format((float) $teacher->basic_salary, 2) : null],
                        ['Payment method', $teacher->salary_payment_method ? Str::headline($teacher->salary_payment_method) : null],
                        ['Login', $teacher->user ? 'Created' : 'Not created'],
                        ['Login status', $teacher->user?->is_active ? 'Active' : ($teacher->user ? 'Inactive' : 'No login')],
                        ['Status', ucfirst($teacher->status)],
                    ] as [$label, $value])
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">{{ $label }}</dt>
                            <dd class="font-medium text-right">{{ $value ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($teacher->bank_account_note)
                    <div class="mt-5 rounded-lg bg-gray-50 p-3 text-sm">
                        <div class="font-semibold text-gray-500">Bank/account note</div>
                        <div class="mt-1 whitespace-pre-line text-[#0B1F3A]">{{ $teacher->bank_account_note }}</div>
                    </div>
                @endif
            </div>

            <div id="assignments" class="app-card scroll-mt-24 p-6">
                <h2 class="font-semibold text-[#0B1F3A]">Assignments</h2>
                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($teacher->assignments as $assignment)
                        <div class="py-3">
                            <div class="font-medium">{{ $assignment->schoolClass?->name }} {{ $assignment->section?->name }}</div>
                            <div class="text-sm text-gray-500">{{ $assignment->subject?->name ?: 'No subject selected' }}</div>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-gray-500">No assignments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div id="login" class="app-card scroll-mt-24 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Login</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $teacher->user ? 'Teacher login is connected to '.$teacher->user->email.'.' : 'No teacher login account is connected yet.' }}</p>
                </div>
                @if ($teacher->user)
                    <form method="POST" action="{{ route('teachers.password', $teacher) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="password" minlength="8" required placeholder="New password" class="rounded-md border-gray-300">
                        <button class="app-button app-button-dark">
                            <i data-lucide="key-round" class="h-4 w-4"></i>
                            Reset Password
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div id="salary" class="app-card scroll-mt-24 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Salary Records</h2>
                    <p class="mt-1 text-sm text-gray-500">Monthly salary, deductions, manual payments, and remaining balance.</p>
                </div>
                <a href="{{ route('salaries.index', ['teacher_id' => $teacher->id]) }}" class="app-button app-button-light">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Open Salary Management
                </a>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                @foreach ([
                    ['Basic Salary', $teacher->basic_salary ? 'PKR '.number_format((float) $teacher->basic_salary, 2) : '-', 'wallet'],
                    ['Current Status', $currentSalary ? ucfirst($currentSalary->payment_status) : 'Not generated', 'circle-alert'],
                    ['Paid This Month', 'PKR '.number_format((float) $currentSalaryRecords->sum('paid_amount'), 2), 'badge-dollar-sign'],
                    ['Balance This Month', 'PKR '.number_format((float) $currentSalaryRecords->sum('balance'), 2), 'receipt'],
                ] as [$label, $value, $icon])
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-center justify-between gap-3 text-sm text-gray-500">
                            <span>{{ $label }}</span>
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-[#1DA1F2]"></i>
                        </div>
                        <div class="mt-2 text-lg font-black text-[#0B1F3A]">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('teachers.salary.store', $teacher) }}" class="mt-5 grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 md:grid-cols-6">
                @csrf
                <input type="number" min="1" max="12" name="salary_month" value="{{ old('salary_month', now()->month) }}" required>
                <input type="number" min="2020" max="2100" name="salary_year" value="{{ old('salary_year', now()->year) }}" required>
                <input type="number" step="0.01" min="0" name="gross_salary" value="{{ old('gross_salary', $teacher->basic_salary) }}" placeholder="Gross salary">
                <input type="number" step="0.01" min="0" name="deductions" value="{{ old('deductions') }}" placeholder="Deductions">
                <input name="deduction_reason" value="{{ old('deduction_reason') }}" placeholder="Deduction reason">
                <button class="app-button app-button-dark">
                    <i data-lucide="circle-plus" class="h-4 w-4"></i>
                    Generate
                </button>
                <textarea name="note" rows="2" class="md:col-span-6 rounded-md border-gray-300" placeholder="Salary note optional">{{ old('note') }}</textarea>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="app-table min-w-[1120px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Month</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Deductions</th>
                            <th class="text-right">Payable</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-left">Payment</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($teacher->salaryPayments as $salary)
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ DateTime::createFromFormat('!m', (string) $salary->salary_month)->format('F') }} {{ $salary->salary_year }}</div>
                                    <div class="text-gray-500">{{ $salary->creator?->name ? 'Created by '.$salary->creator->name : 'Manual record' }}</div>
                                </td>
                                <td class="text-right">PKR {{ number_format((float) $salary->gross_salary, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->deductions, 2) }}</td>
                                <td class="text-right">PKR {{ number_format($salary->payableAmount(), 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->paid_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $salary->balance, 2) }}</td>
                                <td>
                                    <div>{{ $salary->payment_method ? Str::headline($salary->payment_method) : '-' }}</div>
                                    <div class="text-gray-500">{{ optional($salary->payment_date)->format('d M Y') ?: '' }}</div>
                                </td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($salary->payment_status) {
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ ucfirst($salary->payment_status) }}</span>
                                </td>
                                <td class="text-right">
                                    <div x-data="{ payOpen: false, deductionOpen: false }" class="inline-flex justify-end gap-2">
                                        @if ((float) $salary->balance > 0)
                                            <button type="button" @click="payOpen = true" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Record salary payment">
                                                <i data-lucide="badge-dollar-sign" class="h-4 w-4 text-[#1DA1F2]"></i>
                                            </button>
                                        @endif
                                        <button type="button" @click="deductionOpen = true" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Add salary deduction">
                                            <i data-lucide="minus-circle" class="h-4 w-4 text-amber-700"></i>
                                        </button>

                                        <div x-show="payOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                            <form method="POST" action="{{ route('salaries.payment', $salary) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                @csrf
                                                @method('PATCH')
                                                <h3 class="text-lg font-bold text-[#0B1F3A]">Record Salary Payment</h3>
                                                <p class="mt-1 text-sm text-gray-500">Balance: PKR {{ number_format((float) $salary->balance, 2) }}</p>
                                                <input type="number" step="0.01" min="1" max="{{ $salary->balance }}" name="amount" class="mt-4 rounded-md border-gray-300" required placeholder="Amount paid">
                                                <select name="payment_method" class="mt-3 rounded-md border-gray-300" required>
                                                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                                                        <option value="{{ $value }}" @selected(($teacher->salary_payment_method ?: 'cash') === $value)>{{ $label }}</option>
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
                                    </div>
                                </td>
                            </tr>
                            @if ($salary->deduction_reason || $salary->note)
                                <tr class="bg-gray-50">
                                    <td colspan="9" class="px-5 py-3 text-sm text-gray-600">
                                        @if ($salary->deduction_reason)
                                            <span class="font-semibold text-gray-700">Deduction:</span> {{ $salary->deduction_reason }}
                                        @endif
                                        @if ($salary->note)
                                            <span class="ml-4 font-semibold text-gray-700">Note:</span> {{ $salary->note }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="9" class="py-8 text-center text-gray-500">No salary records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
                $salaryPaymentEntries = $teacher->salaryPayments->flatMap(fn ($record) => $record->paymentEntries)->sortByDesc('payment_date')->values();
                $salaryDeductionEntries = $teacher->salaryPayments->flatMap(fn ($record) => $record->deductionEntries)->sortByDesc('created_at')->values();
            @endphp

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="font-bold text-[#0B1F3A]">Payment History</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($salaryPaymentEntries->take(8) as $entry)
                            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                                <div>
                                    <div class="font-semibold">{{ optional($entry->payment_date)->format('d M Y') }} · {{ Str::headline($entry->payment_method) }}</div>
                                    <div class="text-gray-500">{{ $entry->reference_number ?: 'No reference' }}</div>
                                </div>
                                <div class="font-bold text-[#0B1F3A]">PKR {{ number_format((float) $entry->amount, 2) }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-sm text-gray-500">No salary payments yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="font-bold text-[#0B1F3A]">Deduction History</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($salaryDeductionEntries->take(8) as $entry)
                            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                                <div>
                                    <div class="font-semibold">{{ $entry->reason }}</div>
                                    <div class="text-gray-500">{{ $entry->note ?: 'No note' }}</div>
                                </div>
                                <div class="font-bold text-[#0B1F3A]">PKR {{ number_format((float) $entry->amount, 2) }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-sm text-gray-500">No salary deductions yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
