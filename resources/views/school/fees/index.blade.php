<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Finance
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Fees Management</h1>
                <p class="mt-1 text-sm text-gray-600">School fee categories, plans, monthly generation, payments, fines, discounts, ledgers, and unpaid accounts.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.payments.csv') }}" class="app-button app-button-light">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    Collection CSV
                </a>
                <a href="{{ route('reports.fee-defaulters.csv') }}" class="app-button app-button-primary">
                    <i data-lucide="circle-alert" class="h-4 w-4"></i>
                    Unpaid CSV
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $tabs = [
            ['Overview', 'overview', 'layout-dashboard'],
            ['Fee Categories', 'categories', 'tags'],
            ['Fee Plans', 'plans', 'clipboard-list'],
            ['Generate Monthly Fees', 'generate', 'calendar-plus'],
            ['Student Accounts', 'accounts', 'users'],
            ['Receive Payments', 'payments', 'badge-dollar-sign'],
            ['Fines', 'fines', 'badge-alert'],
            ['Discounts', 'discounts', 'badge-percent'],
            ['Unpaid / Defaulters', 'unpaid', 'circle-alert'],
            ['Reports', 'reports', 'bar-chart-3'],
        ];
        $statusClass = fn ($status) => match($status) {
            'paid' => 'bg-green-100 text-green-700',
            'partial' => 'bg-blue-100 text-blue-700',
            'overdue' => 'bg-red-100 text-red-700',
            'carried_forward' => 'bg-gray-100 text-gray-700',
            default => 'bg-amber-100 text-amber-700',
        };
        $statusLabel = fn ($status) => $status === 'carried_forward' ? 'Carried Forward' : ucfirst((string) $status);
    @endphp

    <div x-data="{ tab: '{{ request('tab', 'overview') }}' }" class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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

        <section x-show="tab === 'overview'" x-cloak class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Total Generated This Month', $overview['totalGeneratedThisMonth'], 'receipt', 'bg-blue-50 text-blue-700'],
                    ['Total Collected This Month', $overview['totalCollectedThisMonth'], 'badge-dollar-sign', 'bg-green-50 text-green-700'],
                    ['Pending Balance', $overview['pendingBalance'], 'wallet', 'bg-amber-50 text-amber-700'],
                    ['Overdue Balance', $overview['overdueBalance'], 'circle-alert', 'bg-red-50 text-red-700'],
                    ['Total Fines', $overview['totalFines'], 'badge-alert', 'bg-orange-50 text-orange-700'],
                    ['Total Discounts', $overview['totalDiscounts'], 'badge-percent', 'bg-sky-50 text-sky-700'],
                    ["Today's Collection", $overview['todaysCollection'], 'calendar-check', 'bg-emerald-50 text-emerald-700'],
                    ['Defaulter Students', $overview['defaulterStudents'], 'users', 'bg-gray-100 text-gray-700'],
                ] as [$label, $value, $icon, $color])
                    <div class="app-card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                            <div class="rounded-xl {{ $color }} p-2">
                                <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                            </div>
                        </div>
                        <div class="mt-3 text-2xl font-black text-[#0B1F3A]">
                            {{ is_numeric($value) && ! Str::contains($label, 'Students') ? 'PKR '.number_format($value, 2) : number_format((float) $value) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_.8fr]">
                <div class="app-card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                        <div>
                            <h2 class="font-semibold text-[#0B1F3A]">Recent Ledger Activity</h2>
                            <p class="mt-1 text-sm text-gray-500">Latest fee, payment, fine, discount, waiver, and adjustment entries.</p>
                        </div>
                        <a href="{{ route('reports.student-ledger.csv') }}" class="app-button app-button-light">
                            <i data-lucide="download" class="h-4 w-4"></i>
                            CSV
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="app-table min-w-[900px] divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Date</th>
                                    <th class="text-left">Student</th>
                                    <th class="text-left">Type</th>
                                    <th class="text-left">Description</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($ledgerEntries as $entry)
                                    <tr>
                                        <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                        <td>
                                            <div class="font-semibold">{{ $entry->student?->name ?: 'Unknown student' }}</div>
                                            <div class="text-gray-500">{{ $entry->student?->registration_number }}</div>
                                        </td>
                                        <td>{{ $ledgerTypes[$entry->type] ?? Str::headline($entry->type) }}</td>
                                        <td>{{ $entry->description }}</td>
                                        <td class="text-right">PKR {{ number_format((float) $entry->debit, 2) }}</td>
                                        <td class="text-right">PKR {{ number_format((float) $entry->credit, 2) }}</td>
                                        <td class="text-right font-bold">PKR {{ number_format((float) $entry->balance_after, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-10 text-center text-gray-500">No ledger entries yet. Generate fees or receive a payment to start the ledger.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h2 class="font-semibold text-[#0B1F3A]">What To Do Next</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @foreach ([
                            ['categories', 'Create Fee Categories', 'Add tuition, exam, transport, fine, and custom school charges.'],
                            ['plans', 'Set Fee Plans', 'Assign class, section, or student-wise monthly and one-time fee plans.'],
                            ['generate', 'Generate Monthly Fees', 'Create this month’s student accounts and carry forward previous unpaid balances.'],
                            ['payments', 'Receive Payments', 'Record cash, bank transfer, Easypaisa, JazzCash, or other manual payments.'],
                        ] as [$target, $title, $body])
                            <button type="button" @click="tab = '{{ $target }}'" class="flex w-full items-start gap-3 rounded-lg border border-gray-200 p-3 text-left transition hover:border-[#1DA1F2] hover:bg-[#F3FAFF]">
                                <span class="mt-0.5 rounded-lg bg-[#E8F4FE] p-2 text-[#1DA1F2]"><i data-lucide="chevron-right" class="h-4 w-4"></i></span>
                                <span>
                                    <span class="block font-bold text-[#0B1F3A]">{{ $title }}</span>
                                    <span class="mt-1 block text-gray-500">{{ $body }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section x-show="tab === 'categories'" x-cloak class="grid gap-6 lg:grid-cols-[.45fr_1fr]">
            <form method="POST" action="{{ route('fees.heads.store') }}" class="app-card grid gap-3 p-5">
                @csrf
                <div>
                    <h2 class="font-semibold text-[#0B1F3A]">Add Fee Category</h2>
                    <p class="mt-1 text-sm text-gray-500">Create normal school charges such as tuition, exam fee, transport fee, or late fine.</p>
                </div>
                <input name="name" placeholder="Monthly Tuition Fee" required>
                <input name="code" placeholder="Code optional, e.g. TUI">
                <select name="category_type" required>
                    @foreach ($categoryTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0" name="default_amount" placeholder="Default amount" required>
                <select name="frequency">
                    <option value="monthly">Monthly</option>
                    <option value="one_time">One-time</option>
                    <option value="annual">Annual</option>
                </select>
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Active category
                </label>
                <button class="app-button app-button-primary">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Fee Category
                </button>
            </form>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Fee Categories</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[760px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Name</th>
                                <th class="text-left">Code</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Frequency</th>
                                <th class="text-right">Default Amount</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($feeHeads as $head)
                                <tr>
                                    <td class="font-semibold">{{ $head->name }}</td>
                                    <td>{{ $head->code ?: '-' }}</td>
                                    <td>{{ $categoryTypes[$head->category_type] ?? Str::headline($head->category_type ?: 'monthly_fee') }}</td>
                                    <td>{{ Str::headline($head->frequency) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $head->default_amount, 2) }}</td>
                                    <td>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $head->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $head->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-gray-500">No fee categories yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section x-show="tab === 'plans'" x-cloak class="grid gap-6 xl:grid-cols-[.45fr_1fr]">
            <form method="POST" action="{{ route('fees.structures.store') }}" class="app-card grid gap-3 p-5">
                @csrf
                <div>
                    <h2 class="font-semibold text-[#0B1F3A]">Add Fee Plan</h2>
                    <p class="mt-1 text-sm text-gray-500">Assign a category class-wise, section-wise, or student-wise.</p>
                </div>
                <select name="fee_head_id" required>
                    <option value="">Fee category</option>
                    @foreach ($feeHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
                <select name="type" required>
                    <option value="class">Class-wise Fee Plan</option>
                    <option value="section">Section-wise Fee Plan</option>
                    <option value="student">Student-wise Fee Plan</option>
                </select>
                <select name="school_class_id">
                    <option value="">Class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <select name="section_id">
                    <option value="">Section</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->schoolClass?->name }} - {{ $section->name }}</option>
                    @endforeach
                </select>
                <select name="student_id">
                    <option value="">Student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->registration_number }})</option>
                    @endforeach
                </select>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" step="0.01" min="0" name="amount" placeholder="Amount" required>
                    <input type="number" step="0.01" min="0" name="discount" placeholder="Discount amount">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" min="1" max="28" name="due_day" placeholder="Due day">
                    <input type="date" name="due_date">
                </div>
                <select name="frequency" required>
                    <option value="monthly">Monthly</option>
                    <option value="one_time">One-time</option>
                    <option value="annual">Annual</option>
                </select>
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Active plan
                </label>
                <button class="app-button app-button-primary">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Save Fee Plan
                </button>
            </form>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Fee Plans</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[980px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Fee Category</th>
                                <th class="text-left">Apply To</th>
                                <th class="text-left">Class / Section / Student</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Discount</th>
                                <th class="text-right">Final Payable</th>
                                <th class="text-left">Frequency</th>
                                <th class="text-left">Due</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($structures as $plan)
                                <tr>
                                    <td class="font-semibold">{{ $plan->feeHead?->name ?: 'Fee Category' }}</td>
                                    <td>{{ Str::headline($plan->type) }}</td>
                                    <td>
                                        @if ($plan->type === 'student')
                                            {{ $plan->student?->name ?: 'Student' }}
                                        @elseif ($plan->type === 'section')
                                            {{ $plan->schoolClass?->name }} - {{ $plan->section?->name }}
                                        @else
                                            {{ $plan->schoolClass?->name ?: 'Class' }}
                                        @endif
                                    </td>
                                    <td class="text-right">PKR {{ number_format((float) $plan->amount, 2) }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $plan->discount, 2) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format(max(0, (float) $plan->amount - (float) $plan->discount), 2) }}</td>
                                    <td>{{ Str::headline($plan->frequency) }}</td>
                                    <td>{{ $plan->due_day ? 'Day '.$plan->due_day : (optional($plan->due_date)->format('d M Y') ?: '-') }}</td>
                                    <td>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-10 text-center text-gray-500">No fee plans yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section x-show="tab === 'generate'" x-cloak class="grid gap-6 lg:grid-cols-[.55fr_1fr]">
            @php
                $monthOptions = [
                    1 => 'January',
                    2 => 'February',
                    3 => 'March',
                    4 => 'April',
                    5 => 'May',
                    6 => 'June',
                    7 => 'July',
                    8 => 'August',
                    9 => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December',
                ];
                $generateFeeOptions = $structures
                    ->filter(fn ($plan) => $plan->is_active && $plan->feeHead?->is_active)
                    ->groupBy('fee_head_id')
                    ->map(fn ($plans) => $plans->first())
                    ->sortBy(fn ($plan) => $plan->feeHead?->name ?? '');
            @endphp
            <form method="POST" action="{{ route('fees.generate') }}" class="app-card grid gap-3 p-5">
                @csrf
                <div>
                    <h2 class="font-semibold text-[#0B1F3A]">Generate Monthly Fees</h2>
                    <p class="mt-1 text-sm text-gray-500">Choose the fee, month, students, and due date before creating fee accounts.</p>
                </div>
                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                <label class="grid gap-1 text-sm font-semibold text-gray-700">
                    <span>Fee to Generate</span>
                    <select name="fee_to_generate" required>
                        <option value="">Select fee</option>
                        <option value="all_monthly" @selected(old('fee_to_generate', 'all_monthly') === 'all_monthly')>All Monthly Fees</option>
                        @foreach ($generateFeeOptions as $plan)
                            <option value="fee_head:{{ $plan->fee_head_id }}" @selected(old('fee_to_generate') === 'fee_head:'.$plan->fee_head_id)>
                                {{ $plan->feeHead?->name ?: 'Fee Category' }} ({{ Str::headline($plan->frequency) }})
                            </option>
                        @endforeach
                    </select>
                    @error('fee_to_generate')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="grid gap-1 text-sm font-semibold text-gray-700">
                        <span>Fee Month</span>
                        <select name="month" required>
                            @foreach ($monthOptions as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('month', now()->month) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('month')
                            <span class="text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-gray-700">
                        <span>Fee Year</span>
                        <select name="year" required>
                            @for ($year = now()->year - 1; $year <= now()->year + 2; $year++)
                                <option value="{{ $year }}" @selected((int) old('year', now()->year) === $year)>{{ $year }}</option>
                            @endfor
                        </select>
                        @error('year')
                            <span class="text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="grid gap-1 text-sm font-semibold text-gray-700">
                        <span>Class</span>
                        <select name="school_class_id">
                            <option value="">All classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-gray-700">
                        <span>Section</span>
                        <select name="section_id">
                            <option value="">All sections</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>{{ $section->schoolClass?->name }} - {{ $section->name }}</option>
                            @endforeach
                        </select>
                        @error('section_id')
                            <span class="text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <label class="grid gap-1 text-sm font-semibold text-gray-700">
                    <span>Fee Due Date</span>
                    <input type="date" name="due_date" value="{{ old('due_date', now()->format('Y-m-d')) }}" required>
                    @error('due_date')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="hidden" name="active_only" value="0">
                    <input type="checkbox" name="active_only" value="1" @checked(old('active_only', '1') === '1')>
                    Include active students only
                </label>
                <button class="app-button app-button-dark">
                    <i data-lucide="calendar-plus" class="h-4 w-4"></i>
                    Generate Fees
                </button>
            </form>

            <div class="app-card p-5">
                <h2 class="font-semibold text-[#0B1F3A]">Monthly Balance Logic</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ([
                        ['Current Fee', 'Selected fee category or fee plan will be generated for matching students.'],
                        ['Previous Balance', 'Any unpaid balance from previous months will carry forward.'],
                        ['Unpaid Fines', 'Unpaid fines will remain visible and collectible.'],
                        ['Duplicate Safety', 'The same fee will not be generated twice for the same student, month, and year.'],
                    ] as [$title, $body])
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="font-bold text-[#0B1F3A]">{{ $title }}</div>
                            <p class="mt-1 text-sm text-gray-600">{{ $body }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section x-show="tab === 'accounts'" x-cloak class="space-y-6">
            <form class="app-card grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-7">
                <input type="hidden" name="tab" value="accounts">
                <input name="search" value="{{ request('search') }}" placeholder="Search name, registration, phone">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['unpaid', 'partial', 'paid', 'overdue', 'carried_forward'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabel($status) }}</option>
                    @endforeach
                </select>
                <input type="number" min="1" max="12" name="month" value="{{ request('month') }}" placeholder="Month">
                <input type="number" min="2020" max="2100" name="year" value="{{ request('year') }}" placeholder="Year">
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
                <button class="app-button app-button-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter Accounts
                </button>
            </form>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Student Fee Accounts</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[1600px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Guardian Phone</th>
                                <th class="text-left">Class / Section</th>
                                <th class="text-left">Month / Year</th>
                                <th class="text-left">Fee Category</th>
                                <th class="text-right">Monthly Fee</th>
                                <th class="text-right">Previous Balance</th>
                                <th class="text-right">Fines</th>
                                <th class="text-right">Discounts</th>
                                <th class="text-right">Total Payable</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Remaining</th>
                                <th class="text-left">Due Date</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($fees as $fee)
                                <tr class="align-top">
                                    <td>
                                        <div class="font-semibold">{{ $fee->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $fee->student?->registration_number }}</div>
                                    </td>
                                    <td>{{ $fee->student?->guardian_phone ?: '-' }}</td>
                                    <td>{{ $fee->student?->schoolClass?->name }} {{ $fee->student?->section?->name }}</td>
                                    <td>{{ $fee->month }}/{{ $fee->year }}</td>
                                    <td>{{ $fee->feeHead?->name ?: 'Fee' }}</td>
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
                                <tr><td colspan="15" class="py-10 text-center text-gray-500">No student fee accounts found for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-5 py-4">{{ $fees->links() }}</div>
            </div>
        </section>

        <section x-show="tab === 'payments'" x-cloak class="space-y-6">
            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Receive Payments</h2>
                    <p class="mt-1 text-sm text-gray-500">Record manual cash, bank, Easypaisa, JazzCash, or other payments. Partial payments are allowed.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[1180px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Fee Month</th>
                                <th class="text-right">Total Payable</th>
                                <th class="text-right">Already Paid</th>
                                <th class="text-right">Remaining</th>
                                <th class="text-left">Due</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($openFeeRecords as $record)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $record->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $record->student?->registration_number }} · {{ $record->student?->schoolClass?->name }} {{ $record->student?->section?->name }}</div>
                                    </td>
                                    <td>{{ $record->feeHead?->name ?: 'Fee' }} · {{ $record->month }}/{{ $record->year }}</td>
                                    <td class="text-right">PKR {{ number_format($record->payableAmount(), 2) }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $record->paid_amount, 2) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format($record->balance(), 2) }}</td>
                                    <td>{{ optional($record->due_date)->format('d M Y') ?: '-' }}</td>
                                    <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($record->status) }}">{{ $statusLabel($record->status) }}</span></td>
                                    <td class="text-right">@include('school.fees.partials.payment-modal', ['fee' => $record])</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-10 text-center text-gray-500">No unpaid fee accounts. Payments are fully up to date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Recent Payments</h2>
                    <a href="{{ route('reports.payments.csv') }}" class="app-button app-button-light"><i data-lucide="download" class="h-4 w-4"></i> CSV</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($payments as $payment)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm">
                            <div>
                                <div class="font-semibold">{{ $payment->receipt_number }}</div>
                                <div class="mt-1 text-gray-500">{{ $payment->student?->name }} · {{ optional($payment->paid_on)->format('d M Y') }} · {{ Str::headline($payment->method) }} · {{ $payment->receiver?->name ?: 'Unknown receiver' }}</div>
                            </div>
                            <div class="font-bold text-[#0B1F3A]">PKR {{ number_format((float) $payment->amount, 2) }}</div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-sm text-gray-500">No payments recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section x-show="tab === 'fines'" x-cloak class="grid gap-6 xl:grid-cols-[.42fr_1fr]">
            <form method="POST" action="{{ route('student-fines.store') }}" class="app-card grid gap-3 p-5">
                @csrf
                <div>
                    <h2 class="font-semibold text-[#0B1F3A]">Add Fine / Penalty</h2>
                    <p class="mt-1 text-sm text-gray-500">Fine increases the student outstanding balance and appears in the ledger.</p>
                </div>
                <select name="student_id" required>
                    <option value="">Student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->registration_number }})</option>
                    @endforeach
                </select>
                <select name="fine_type" required>
                    @foreach ($fineTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input name="title" required placeholder="Reason/title">
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="Amount">
                <div class="grid grid-cols-2 gap-3">
                    <input type="date" name="fine_date" value="{{ now()->format('Y-m-d') }}" required>
                    <input type="date" name="due_date">
                </div>
                <select name="student_fee_id">
                    <option value="">Not linked to fee</option>
                    @foreach ($openFeeRecords as $record)
                        <option value="{{ $record->id }}">{{ $record->student?->name }} - {{ $record->feeHead?->name ?: 'Fee' }} {{ $record->month }}/{{ $record->year }}</option>
                    @endforeach
                </select>
                <textarea name="note" rows="2" class="rounded-md border-gray-300" placeholder="Note optional"></textarea>
                <button class="app-button app-button-primary"><i data-lucide="badge-alert" class="h-4 w-4"></i> Save Fine</button>
            </form>

            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Fine Records</h2>
                    <a href="{{ route('reports.student-fines.csv') }}" class="app-button app-button-light"><i data-lucide="download" class="h-4 w-4"></i> CSV</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[920px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Fine</th>
                                <th class="text-right">Amount</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Linked Fee</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($fines as $fine)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $fine->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $fine->student?->schoolClass?->name }} {{ $fine->student?->section?->name }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $fine->title }}</div>
                                        <div class="text-gray-500">{{ $fineTypes[$fine->fine_type] ?? Str::headline($fine->fine_type) }}</div>
                                    </td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $fine->amount, 2) }}</td>
                                    <td>{{ optional($fine->fine_date)->format('d M Y') }}</td>
                                    <td>{{ $fine->studentFee?->feeHead?->name ? $fine->studentFee->feeHead->name.' '.$fine->studentFee->month.'/'.$fine->studentFee->year : '-' }}</td>
                                    <td>{{ ucfirst($fine->status) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-gray-500">No fines recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section x-show="tab === 'discounts'" x-cloak class="space-y-6">
            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Add Discount / Waiver</h2>
                    <p class="mt-1 text-sm text-gray-500">Use Student Accounts actions to apply a discount to the exact fee record. Every discount is logged below and in the student ledger.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[960px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Open Fee Account</th>
                                <th class="text-right">Remaining Balance</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($openFeeRecords as $record)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $record->student?->name }} · {{ $record->feeHead?->name ?: 'Fee' }} {{ $record->month }}/{{ $record->year }}</div>
                                        <div class="text-gray-500">{{ $record->student?->registration_number }} · {{ $record->student?->schoolClass?->name }} {{ $record->student?->section?->name }}</div>
                                    </td>
                                    <td class="text-right font-bold">PKR {{ number_format($record->balance(), 2) }}</td>
                                    <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($record->status) }}">{{ $statusLabel($record->status) }}</span></td>
                                    <td class="text-right">@include('school.fees.partials.discount-modal', ['fee' => $record, 'discountTypes' => $discountTypes])</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-10 text-center text-gray-500">No open fee accounts for discounts.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-[#0B1F3A]">Discount / Waiver Records</h2>
                    <a href="{{ route('reports.student-discounts.csv') }}" class="app-button app-button-light"><i data-lucide="download" class="h-4 w-4"></i> CSV</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[920px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Student</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Reason</th>
                                <th class="text-right">Amount</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Approved By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($discounts as $discount)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $discount->student?->name ?: 'Unknown student' }}</div>
                                        <div class="text-gray-500">{{ $discount->student?->registration_number }}</div>
                                    </td>
                                    <td>{{ $discountTypes[$discount->discount_type] ?? Str::headline($discount->discount_type) }}</td>
                                    <td>{{ $discount->reason }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $discount->amount, 2) }}</td>
                                    <td>{{ optional($discount->discount_date)->format('d M Y') }}</td>
                                    <td>{{ $discount->approved_by ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-gray-500">No discounts or waivers recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section x-show="tab === 'unpaid'" x-cloak class="app-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-[#0B1F3A]">Unpaid / Defaulters</h2>
                    <p class="mt-1 text-sm text-gray-500">Students whose due date has passed and balance is still pending.</p>
                </div>
                <a href="{{ route('reports.fee-defaulters.csv') }}" class="app-button app-button-light"><i data-lucide="download" class="h-4 w-4"></i> CSV</a>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[1380px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Student</th>
                            <th class="text-left">Class / Section</th>
                            <th class="text-left">Guardian</th>
                            <th class="text-left">Month / Year</th>
                            <th class="text-right">Total Due</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-left">Days Overdue</th>
                            <th class="text-left">Last Payment</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($defaulters as $fee)
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ $fee->student?->name ?: 'Unknown student' }}</div>
                                    <div class="text-gray-500">{{ $fee->student?->registration_number }}</div>
                                </td>
                                <td>{{ $fee->student?->schoolClass?->name }} {{ $fee->student?->section?->name }}</td>
                                <td>
                                    <div>{{ $fee->student?->guardian_name ?: '-' }}</div>
                                    <div class="text-gray-500">{{ $fee->student?->guardian_phone ?: '-' }}</div>
                                </td>
                                <td>{{ $fee->feeHead?->name ?: 'Fee' }} · {{ $fee->month }}/{{ $fee->year }}</td>
                                <td class="text-right">PKR {{ number_format($fee->payableAmount(), 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $fee->paid_amount, 2) }}</td>
                                <td class="text-right font-bold text-red-700">PKR {{ number_format($fee->balance(), 2) }}</td>
                                <td>{{ $fee->due_date ? $fee->due_date->diffInDays(today()) : '-' }}</td>
                                <td>{{ optional($fee->payments->sortByDesc('paid_on')->first()?->paid_on)->format('d M Y') ?: '-' }}</td>
                                <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($fee->status) }}">{{ $statusLabel($fee->status) }}</span></td>
                                <td class="text-right">@include('school.fees.partials.actions', ['fee' => $fee, 'fineTypes' => $fineTypes, 'discountTypes' => $discountTypes])</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="py-10 text-center text-gray-500">No overdue unpaid accounts right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'reports'" x-cloak class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['Daily Collection Report', 'Payments received by date range.', route('reports.payments.csv', ['date_from' => today()->toDateString(), 'date_to' => today()->toDateString()]), 'calendar-check'],
                ['Monthly Collection Report', 'Payments for the selected month and year.', route('reports.payments.csv', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->endOfMonth()->toDateString()]), 'badge-dollar-sign'],
                ['Student Ledger Report', 'Full debit and credit history by student.', route('reports.student-ledger.csv'), 'list'],
                ['Unpaid / Defaulters Report', 'Open balances and overdue accounts.', route('reports.fee-defaulters.csv'), 'circle-alert'],
                ['Fine Report', 'Student fine and penalty records.', route('reports.student-fines.csv'), 'badge-alert'],
                ['Discount / Waiver Report', 'Discount approvals and waiver records.', route('reports.student-discounts.csv'), 'badge-percent'],
                ['Class-wise Fee Report', 'Filter defaulters or collections by class.', route('reports.fee-defaulters.csv'), 'school'],
                ['Payment Method Report', 'Collections with cash, bank, Easypaisa, JazzCash, or other methods.', route('reports.payments.csv'), 'credit-card'],
                ['Partial Payment Report', 'Student accounts with partial status.', route('reports.fee-defaulters.csv', ['status' => 'partial']), 'pie-chart'],
                ['Overdue Fee Report', 'Fee accounts past due date.', route('reports.fee-defaulters.csv', ['status' => 'overdue']), 'alarm-clock'],
            ] as [$title, $body, $url, $icon])
                <a href="{{ $url }}" class="app-card flex items-start gap-4 p-5 transition hover:-translate-y-0.5 hover:border-[#1DA1F2] hover:bg-[#F3FAFF] hover:shadow-md">
                    <span class="rounded-xl border border-[#D7ECFD] bg-[#E8F4FE] p-3 text-[#1DA1F2]"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                    <span>
                        <span class="block font-bold text-[#0B1F3A]">{{ $title }}</span>
                        <span class="mt-1 block text-sm text-gray-500">{{ $body }}</span>
                        <span class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-[#1DA1F2]">Export CSV <i data-lucide="download" class="h-4 w-4"></i></span>
                    </span>
                </a>
            @endforeach
        </section>
    </div>
</x-app-layout>
