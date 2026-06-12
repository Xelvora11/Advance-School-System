<div x-data="{ payOpen: false }" class="inline-block text-left">
    <button type="button" @click="payOpen = true" class="app-button app-button-primary" @disabled($fee->balance() <= 0)>
        <i data-lucide="badge-dollar-sign" class="h-4 w-4"></i>
        Receive Payment
    </button>

    <div x-show="payOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.payments.store', $fee) }}" enctype="multipart/form-data" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Receive Payment</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $fee->student?->name ?: 'Unknown student' }} · {{ $fee->feeHead?->name ?: 'Fee' }} {{ $fee->month }}/{{ $fee->year }}</p>
                </div>
                <button type="button" @click="payOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-3 text-sm md:grid-cols-3">
                <div>
                    <div class="text-gray-500">Total payable</div>
                    <div class="font-bold text-[#0B1F3A]">PKR {{ number_format($fee->payableAmount(), 2) }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Already paid</div>
                    <div class="font-bold text-[#0B1F3A]">PKR {{ number_format((float) $fee->paid_amount, 2) }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Remaining</div>
                    <div class="font-bold text-red-700">PKR {{ number_format($fee->balance(), 2) }}</div>
                </div>
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
</div>
