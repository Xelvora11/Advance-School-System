<div x-data="{ discountOpen: false }" class="inline-block text-left">
    <button type="button" @click="discountOpen = true" class="{{ $buttonClass ?? 'app-button app-button-light' }}">
        <i data-lucide="badge-percent" class="h-4 w-4"></i>
        Add Discount
    </button>

    <div x-show="discountOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.students.discounts.store', $student) }}" class="w-full max-w-xl rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Add Discount / Waiver</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $student->name }} · select the fee account to reduce.</p>
                </div>
                <button type="button" @click="discountOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            @if ($openFees->isEmpty())
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    No pending balance found for this student.
                </div>
            @else
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <select name="student_fee_id" required>
                        <option value="">Select fee account</option>
                        @foreach ($openFees as $fee)
                            <option value="{{ $fee->id }}">{{ $fee->feeHead?->name ?: 'Fee' }} {{ $fee->month }}/{{ $fee->year }} - Balance PKR {{ number_format($fee->balance(), 2) }}</option>
                        @endforeach
                    </select>
                    <select name="discount_type" required>
                        @foreach ($discountTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0.01" name="discount" required placeholder="Discount amount">
                    <input type="date" name="discount_date" value="{{ now()->format('Y-m-d') }}" required>
                    <input name="reason" required placeholder="Reason">
                    <input name="approved_by" value="{{ auth()->user()->name }}" placeholder="Approved by">
                    <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
                </div>
            @endif

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="discountOpen = false" class="app-button app-button-light">Cancel</button>
                <button class="app-button app-button-dark" @disabled($openFees->isEmpty())>Save Discount</button>
            </div>
        </form>
    </div>
</div>
