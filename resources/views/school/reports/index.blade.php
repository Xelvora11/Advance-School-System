<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                    Reports
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Operational Reports</h1>
                <p class="mt-1 text-sm text-gray-600">Students, collections, open fees, and attendance summaries.</p>
            </div>
            <a href="{{ route('reports.students.csv') }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                Student CSV
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <form class="app-card grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-12">
            <select name="class_id">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <select name="section_id">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->schoolClass?->name }} - {{ $section->name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">Open fee statuses</option>
                @foreach (['unpaid', 'partial', 'overdue', 'paid', 'waived'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="student_id">
                <option value="">All students</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(request('student_id') == $student->id)>{{ $student->name }}</option>
                @endforeach
            </select>
            <select name="teacher_id">
                <option value="">All teachers</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            <select name="salary_status">
                <option value="">Salary statuses</option>
                @foreach (['unpaid', 'partial', 'paid'] as $status)
                    <option value="{{ $status }}" @selected(request('salary_status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input type="number" min="1" max="12" name="month" value="{{ request('month') }}" placeholder="Month">
            <input type="number" min="2020" max="2100" name="year" value="{{ request('year') }}" placeholder="Year">
            <select name="method">
                <option value="">Payment method</option>
                @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}">
            <button class="app-button app-button-dark">
                <i data-lucide="filter" class="h-4 w-4"></i>
                Apply
            </button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Students', $studentCount, 'graduation-cap', 'text-blue-700', 'bg-blue-50'],
                ['Teachers', $teacherCount, 'user-round-check', 'text-emerald-700', 'bg-emerald-50'],
                ['Open Fee Balance', 'PKR '.number_format($pendingBalance, 2), 'receipt', 'text-amber-700', 'bg-amber-50'],
                ['Filtered Collections', 'PKR '.number_format($paymentTotal, 2), 'badge-dollar-sign', 'text-green-700', 'bg-green-50'],
                ['Unpaid Fines', 'PKR '.number_format($fineTotal, 2), 'badge-alert', 'text-red-700', 'bg-red-50'],
                ['Discounts / Waivers', 'PKR '.number_format($discountTotal, 2), 'badge-percent', 'text-sky-700', 'bg-sky-50'],
                ['Salary Due', 'PKR '.number_format($salaryDueTotal, 2), 'wallet-cards', 'text-indigo-700', 'bg-indigo-50'],
            ] as [$label, $value, $icon, $color, $bg])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-lg {{ $bg }} p-2 {{ $color }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-[#0B1F3A]">Student Fines</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $studentFines->count() }} fine records shown.</p>
                    </div>
                    <a href="{{ route('reports.student-fines.csv', request()->query()) }}" class="app-button app-button-light">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[820px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Fine</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($studentFines as $fine)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $fine->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $fine->student?->schoolClass?->name }} {{ $fine->student?->section?->name }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $fine->title }}</div>
                                        <div class="text-gray-500">{{ \App\Models\StudentFine::TYPES[$fine->fine_type] ?? Str::headline($fine->fine_type) }}</div>
                                    </td>
                                    <td>{{ optional($fine->fine_date)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ ucfirst($fine->status) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $fine->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">No fine records for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-[#0B1F3A]">Discounts / Waivers</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $studentDiscounts->count() }} discount records shown.</p>
                    </div>
                    <a href="{{ route('reports.student-discounts.csv', request()->query()) }}" class="app-button app-button-light">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[820px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Reason</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($studentDiscounts as $discount)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $discount->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $discount->student?->schoolClass?->name }} {{ $discount->student?->section?->name }}</div>
                                    </td>
                                    <td>{{ \App\Models\StudentDiscount::TYPES[$discount->discount_type] ?? Str::headline($discount->discount_type) }}</td>
                                    <td>{{ optional($discount->discount_date)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ $discount->reason }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $discount->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">No discount records for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-[#0B1F3A]">Teacher Salaries</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $salaryRecords->count() }} salary records shown.</p>
                    </div>
                    <a href="{{ route('reports.teacher-salaries.csv', request()->query()) }}" class="app-button app-button-light">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[900px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Teacher</th>
                                <th class="text-left">Month</th>
                                <th class="text-right">Gross</th>
                                <th class="text-right">Deduction</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Balance</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salaryRecords as $salary)
                                <tr>
                                    <td class="font-semibold">{{ $salary->teacher?->name ?: 'Unknown teacher' }}</td>
                                    <td>{{ $salary->salary_month }}/{{ $salary->salary_year }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $salary->gross_salary, 2) }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $salary->deductions, 2) }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $salary->paid_amount, 2) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $salary->balance, 2) }}</td>
                                    <td>{{ ucfirst($salary->payment_status) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-gray-500">No salary records for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-[#0B1F3A]">Unpaid / Defaulters</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $defaulters->count() }} open records shown.</p>
                    </div>
                    <a href="{{ route('reports.fee-defaulters.csv', request()->query()) }}" class="app-button app-button-light">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[760px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Fee</th>
                                <th class="text-left">Due</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($defaulters as $fee)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $fee->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $fee->student?->schoolClass?->name }} {{ $fee->student?->section?->name }}</div>
                                    </td>
                                    <td>{{ $fee->feeHead?->name ?: 'Fee' }} · {{ $fee->month }}/{{ $fee->year }}</td>
                                    <td>{{ optional($fee->due_date)->format('d M Y') ?: '-' }}</td>
                                    <td>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $fee->status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($fee->status) }}</span>
                                    </td>
                                    <td class="text-right font-bold">PKR {{ number_format($fee->balance(), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">No open fee records for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-[#0B1F3A]">Payment Collections</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $payments->count() }} payment records shown.</p>
                    </div>
                    <a href="{{ route('reports.payments.csv', request()->query()) }}" class="app-button app-button-light">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[820px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Receipt</th>
                                <th class="text-left">Student</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Method</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $payment->receipt_number }}</div>
                                        <div class="text-gray-500">{{ $payment->reference_number ?: 'No reference' }}</div>
                                    </td>
                                    <td>
                                        <div class="font-semibold">{{ $payment->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $payment->student?->schoolClass?->name }} {{ $payment->student?->section?->name }}</div>
                                    </td>
                                    <td>{{ optional($payment->paid_on)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ Str::headline($payment->method) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $payment->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">No payments for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="app-card p-5">
            <h2 class="font-semibold text-[#0B1F3A]">Attendance Summary</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-4">
                @foreach (['present', 'absent', 'late', 'leave'] as $status)
                    <div class="rounded-md bg-gray-50 p-4">
                        <div class="text-sm text-gray-500">{{ ucfirst($status) }}</div>
                        <div class="mt-1 text-xl font-bold">{{ $attendanceSummary[$status] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
