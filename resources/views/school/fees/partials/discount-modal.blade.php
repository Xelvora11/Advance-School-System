<div x-data="{ discountOpen: false }" class="inline-block text-left">
    <button type="button" @click="discountOpen = true" class="app-button app-button-light" @disabled($fee->balance() <= 0)>
        <i data-lucide="badge-percent" class="h-4 w-4"></i>
        Add Discount
    </button>

    <div x-show="discountOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.discount', $fee) }}" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Add Discount / Waiver</h3>
                    <p class="mt-1 text-sm text-gray-500">Remaining balance: PKR {{ number_format($fee->balance(), 2) }}</p>
                </div>
                <button type="button" @click="discountOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
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
