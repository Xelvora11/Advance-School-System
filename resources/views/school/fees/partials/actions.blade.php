@php
    $actionStudent = $fee->relationLoaded('student') ? $fee->student : ($student ?? null);
    $latestPayment = $fee->payments->sortByDesc('paid_on')->first();
@endphp

<div
    x-data="{
        open: false,
        payOpen: false,
        editOpen: false,
        fineOpen: false,
        discountOpen: false,
        menuStyle: '',
        toggle($refs) {
            if (this.open) {
                this.open = false;
                return;
            }

            const rect = $refs.button.getBoundingClientRect();
            const width = 240;
            const height = 340;
            const gap = 8;
            const top = rect.bottom + height + gap > window.innerHeight
                ? Math.max(gap, rect.top - height - gap)
                : rect.bottom + gap;
            const left = Math.min(window.innerWidth - width - gap, Math.max(gap, rect.right - width));

            this.menuStyle = `position: fixed; top: ${top}px; left: ${left}px; width: ${width}px;`;
            this.open = true;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        }
    }"
    @keydown.escape.window="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
    class="relative inline-block text-left"
>
    <button x-ref="button" @click="toggle($refs)" type="button" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-[#0B1F3A] shadow-sm hover:border-[#1DA1F2] hover:bg-[#F3FAFF]">
        Actions
        <i data-lucide="chevron-down" class="h-4 w-4"></i>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" :style="menuStyle" class="z-[9999] rounded-lg border border-gray-200 bg-white py-1 text-left shadow-xl">
        @if ($actionStudent)
            <a href="{{ route('fees.students.account', $actionStudent) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="wallet-cards" class="h-4 w-4"></i> View Full Fee Account</a>
        @endif
        @if ($fee->balance() > 0)
            <button type="button" @click="payOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="badge-dollar-sign" class="h-4 w-4"></i> Receive Payment</button>
        @endif
        @if ($actionStudent)
            <a href="{{ route('students.show', $actionStudent) }}#ledger-history" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="list" class="h-4 w-4"></i> View Ledger</a>
        @endif
        <a href="{{ route('fees.challan', ['studentFee' => $fee, 'download' => 1]) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="file-text" class="h-4 w-4"></i> Download Challan</a>
        @if ($latestPayment)
            <a href="{{ route('fees.receipt', ['payment' => $latestPayment, 'download' => 1]) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="receipt" class="h-4 w-4"></i> Download Receipt</a>
        @endif
        <button type="button" @click="editOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="pencil" class="h-4 w-4"></i> Edit Fee</button>
        <button type="button" @click="fineOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="badge-alert" class="h-4 w-4"></i> Add Fine</button>
        @if ($fee->balance() > 0)
            <button type="button" @click="discountOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="badge-percent" class="h-4 w-4"></i> Add Discount</button>
        @endif
    </div>

    <div x-show="payOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.payments.store', $fee) }}" enctype="multipart/form-data" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Receive Payment</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $actionStudent?->name ?: 'Unknown student' }} · Balance PKR {{ number_format($fee->balance(), 2) }}</p>
                </div>
                <button type="button" @click="payOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <input type="number" step="0.01" min="1" max="{{ $fee->balance() }}" name="amount" required placeholder="Amount received">
                <select name="method" required>
                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="paid_on" value="{{ now()->format('Y-m-d') }}" required>
                <input name="reference_number" placeholder="Reference number optional">
                <input type="file" name="proof" class="md:col-span-2">
                <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="payOpen = false" class="app-button app-button-light">Cancel</button>
                <button class="app-button app-button-primary">Save Payment</button>
            </div>
        </form>
    </div>

    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.update', $fee) }}" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            @method('PATCH')
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Edit Fee Account</h3>
                    <p class="mt-1 text-sm text-gray-500">Manual changes are written as ledger adjustments.</p>
                </div>
                <button type="button" @click="editOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <input type="number" step="0.01" min="0" name="amount" value="{{ $fee->amount }}" required placeholder="Monthly fee">
                <input type="number" step="0.01" min="0" name="arrears" value="{{ $fee->arrears }}" placeholder="Previous balance">
                <input type="number" step="0.01" min="0" name="fine" value="{{ $fee->fine }}" placeholder="Fine">
                <input type="number" step="0.01" min="0" name="discount" value="{{ $fee->discount }}" placeholder="Discount">
                <input type="date" name="due_date" value="{{ optional($fee->due_date)->format('Y-m-d') }}">
                <textarea name="notes" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note">{{ $fee->notes }}</textarea>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="editOpen = false" class="app-button app-button-light">Cancel</button>
                <button class="app-button app-button-dark">Save Fee</button>
            </div>
        </form>
    </div>

    <div x-show="fineOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.fine', $fee) }}" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Add Fine / Penalty</h3>
                    <p class="mt-1 text-sm text-gray-500">This increases the account balance and appears in the ledger.</p>
                </div>
                <button type="button" @click="fineOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <select name="fine_type" required>
                    @foreach ($fineTypes as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'late_fee')>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="Fine amount">
                <input name="title" required placeholder="Fine reason/title">
                <input type="date" name="fine_date" value="{{ now()->format('Y-m-d') }}" required>
                <input type="date" name="due_date" value="{{ optional($fee->due_date)->format('Y-m-d') }}">
                <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="fineOpen = false" class="app-button app-button-light">Cancel</button>
                <button class="app-button app-button-dark">Add Fine</button>
            </div>
        </form>
    </div>

    <div x-show="discountOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.discount', $fee) }}" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Add Discount / Waiver</h3>
                    <p class="mt-1 text-sm text-gray-500">Remaining balance: PKR {{ number_format($fee->balance(), 2) }}</p>
                </div>
                <button type="button" @click="discountOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <select name="discount_type" required>
                    @foreach ($discountTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0.01" max="{{ $fee->balance() }}" name="discount" required placeholder="Discount amount">
                <input name="reason" required placeholder="Reason">
                <input name="approved_by" value="{{ auth()->user()->name }}" placeholder="Approved by">
                <input type="date" name="discount_date" value="{{ now()->format('Y-m-d') }}" required>
                <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="discountOpen = false" class="app-button app-button-light">Cancel</button>
                <button class="app-button app-button-dark">Save Discount</button>
            </div>
        </form>
    </div>
</div>
