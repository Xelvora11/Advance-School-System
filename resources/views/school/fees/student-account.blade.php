<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Student Fee Account
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $student->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</p>
            </div>
            <a href="{{ route('fees.index', ['tab' => 'accounts']) }}" class="app-button app-button-light">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to Student Accounts
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = fn ($status) => match($status) {
            'paid' => 'bg-green-100 text-green-700',
            'partial' => 'bg-blue-100 text-blue-700',
            'overdue' => 'bg-red-100 text-red-700',
            'carried_forward' => 'bg-gray-100 text-gray-700',
            default => 'bg-amber-100 text-amber-700',
        };
        $statusLabel = fn ($status) => $status === 'carried_forward' ? 'Carried Forward' : ucfirst((string) $status);
        $tabs = [
            ['Fee Records', 'records', 'receipt'],
            ['Payment History', 'payments', 'badge-dollar-sign'],
            ['Fines', 'fines', 'badge-alert'],
            ['Discounts', 'discounts', 'badge-percent'],
            ['Ledger', 'ledger', 'list'],
        ];
    @endphp

    <div x-data="{ tab: 'records', fineOpen: false }" class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="app-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <div class="text-gray-500">Student</div>
                        <div class="font-bold text-[#0B1F3A]">{{ $student->name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Registration</div>
                        <div class="font-bold text-[#0B1F3A]">{{ $student->registration_number }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Class / Section</div>
                        <div class="font-bold text-[#0B1F3A]">{{ $student->schoolClass?->name }} {{ $student->section?->name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Guardian</div>
                        <div class="font-bold text-[#0B1F3A]">{{ $student->guardian_name ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Guardian Phone</div>
                        <div class="font-bold text-[#0B1F3A]">{{ $student->guardian_phone ?: '-' }}</div>
                    </div>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $student->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($student->status) }}</span>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['Total Due', $summary['totalDue'], 'receipt', 'bg-blue-50 text-blue-700'],
                ['Total Paid', $summary['totalPaid'], 'badge-dollar-sign', 'bg-green-50 text-green-700'],
                ['Remaining Balance', $summary['remainingBalance'], 'wallet', 'bg-red-50 text-red-700'],
                ['Previous Balance', $summary['previousBalance'], 'history', 'bg-amber-50 text-amber-700'],
                ['Total Fines', $summary['totalFines'], 'badge-alert', 'bg-orange-50 text-orange-700'],
                ['Total Discounts', $summary['totalDiscounts'], 'badge-percent', 'bg-sky-50 text-sky-700'],
            ] as [$label, $value, $icon, $color])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-xl {{ $color }} p-2"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></div>
                    </div>
                    <div class="mt-3 text-xl font-black text-[#0B1F3A]">PKR {{ number_format((float) $value, 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="app-card flex flex-wrap gap-2 p-3">
            @include('school.fees.partials.student-payment-modal', ['student' => $student, 'openFees' => $openFees, 'buttonClass' => 'app-button app-button-primary'])
            <button type="button" @click="fineOpen = true" class="app-button app-button-light">
                <i data-lucide="badge-alert" class="h-4 w-4"></i>
                Add Fine
            </button>
            @include('school.fees.partials.student-discount-modal', ['student' => $student, 'openFees' => $openFees, 'discountTypes' => $discountTypes, 'buttonClass' => 'app-button app-button-light'])
            @if ($fees->first())
                <a href="{{ route('fees.challan', $fees->first()) }}" class="app-button app-button-light">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    Download Challan
                </a>
            @endif
            @if ($latestReceiptPayment)
                <a href="{{ route('fees.receipt', $latestReceiptPayment) }}" class="app-button app-button-light">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                    Download Latest Receipt
                </a>
            @endif
            <a href="{{ route('students.show', $student) }}#fees" class="app-button app-button-light">
                <i data-lucide="user" class="h-4 w-4"></i>
                Student Profile
            </a>
        </div>

        <div x-show="fineOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <form method="POST" action="{{ route('students.fines.store', $student) }}" class="w-full max-w-lg rounded-lg bg-white p-5 shadow-xl">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-[#0B1F3A]">Add Fine / Penalty</h3>
                        <p class="mt-1 text-sm text-gray-500">Fine increases outstanding balance and appears in the ledger.</p>
                    </div>
                    <button type="button" @click="fineOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <select name="fine_type" required>
                        @foreach ($fineTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="Amount">
                    <input name="title" required placeholder="Fine reason/title">
                    <input type="date" name="fine_date" value="{{ now()->format('Y-m-d') }}" required>
                    <input type="date" name="due_date">
                    <select name="student_fee_id">
                        <option value="">Not linked to fee</option>
                        @foreach ($openFees as $fee)
                            <option value="{{ $fee->id }}">{{ $fee->feeHead?->name ?: 'Fee' }} {{ $fee->month }}/{{ $fee->year }}</option>
                        @endforeach
                    </select>
                    <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="fineOpen = false" class="app-button app-button-light">Cancel</button>
                    <button class="app-button app-button-primary">Save Fine</button>
                </div>
            </form>
        </div>

        <div class="app-card overflow-hidden">
            <div class="flex gap-2 overflow-x-auto p-3">
                @foreach ($tabs as [$label, $key, $icon])
                    <button type="button" @click="tab = '{{ $key }}'" class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold transition" :class="tab === '{{ $key }}' ? 'bg-[#0B1F3A] text-white shadow-sm' : 'text-gray-600 hover:bg-blue-50 hover:text-[#0B1F3A]'">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <section x-show="tab === 'records'" x-cloak class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Fee Records</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[1320px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Month / Year</th>
                            <th class="text-left">Fee Category</th>
                            <th class="text-right">Base Amount</th>
                            <th class="text-right">Previous Balance</th>
                            <th class="text-right">Fine</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Total Due</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-left">Due Date</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($fees as $fee)
                            <tr>
                                <td>{{ $fee->month }}/{{ $fee->year }}</td>
                                <td class="font-semibold">{{ $fee->feeHead?->name ?: 'Fee' }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->amount, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->arrears, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->fine, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->discount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format($fee->payableAmount(), 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->paid_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format($fee->balance(), 2) }}</td>
                                <td>{{ optional($fee->due_date)->format('d M Y') ?: '-' }}</td>
                                <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($fee->status) }}">{{ $statusLabel($fee->status) }}</span></td>
                                <td class="text-right">@include('school.fees.partials.actions', ['fee' => $fee, 'fineTypes' => $fineTypes, 'discountTypes' => $discountTypes])</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="py-10 text-center text-gray-500">No fee records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'payments'" x-cloak class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Payment History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[980px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Method</th>
                            <th class="text-left">Reference</th>
                            <th class="text-left">Received By</th>
                            <th class="text-left">Note</th>
                            <th class="text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ optional($payment->paid_on)->format('d M Y') }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ Str::headline($payment->method) }}</td>
                                <td>{{ $payment->reference_number ?: '-' }}</td>
                                <td>{{ $payment->receiver?->name ?: '-' }}</td>
                                <td>{{ $payment->note ?: '-' }}</td>
                                <td class="text-right"><a href="{{ route('fees.receipt', $payment) }}" class="app-button app-button-light"><i data-lucide="receipt" class="h-4 w-4"></i> Receipt</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-gray-500">No payment history yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'fines'" x-cloak class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Fines</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[920px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Reason</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Due</th>
                            <th class="text-left">Linked Fee</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->fines as $fine)
                            <tr>
                                <td>{{ optional($fine->fine_date)->format('d M Y') }}</td>
                                <td>{{ $fineTypes[$fine->fine_type] ?? Str::headline($fine->fine_type) }}</td>
                                <td>{{ $fine->title }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $fine->amount, 2) }}</td>
                                <td>{{ optional($fine->due_date)->format('d M Y') ?: '-' }}</td>
                                <td>{{ $fine->studentFee?->feeHead?->name ? $fine->studentFee->feeHead->name.' '.$fine->studentFee->month.'/'.$fine->studentFee->year : '-' }}</td>
                                <td>{{ ucfirst($fine->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-gray-500">No fines recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'discounts'" x-cloak class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Discounts</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[920px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Reason</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Approved By</th>
                            <th class="text-left">Linked Fee</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->discounts as $discount)
                            <tr>
                                <td>{{ optional($discount->discount_date)->format('d M Y') }}</td>
                                <td>{{ $discountTypes[$discount->discount_type] ?? Str::headline($discount->discount_type) }}</td>
                                <td>{{ $discount->reason }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $discount->amount, 2) }}</td>
                                <td>{{ $discount->approved_by ?: '-' }}</td>
                                <td>{{ $discount->studentFee?->feeHead?->name ? $discount->studentFee->feeHead->name.' '.$discount->studentFee->month.'/'.$discount->studentFee->year : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-gray-500">No discounts recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'ledger'" x-cloak class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Ledger</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[920px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Description</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Balance After</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->feeLedgerEntries as $entry)
                            <tr>
                                <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                <td>{{ $ledgerTypes[$entry->type] ?? Str::headline($entry->type) }}</td>
                                <td>{{ $entry->description }}</td>
                                <td class="text-right">PKR {{ number_format((float) $entry->debit, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $entry->credit, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $entry->balance_after, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-gray-500">No ledger entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
