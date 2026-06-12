<div x-data="{ paymentOpen: false }" class="inline-block text-left">
    <button type="button" @click="paymentOpen = true" class="{{ $buttonClass ?? 'app-button app-button-primary' }}">
        <i data-lucide="badge-dollar-sign" class="h-4 w-4"></i>
        Receive Payment
    </button>

    <div x-show="paymentOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" action="{{ route('fees.students.payments.store', $student) }}" enctype="multipart/form-data" class="w-full max-w-xl rounded-lg bg-white p-5 text-left shadow-xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Receive Manual Payment</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $student->name }} · {{ $student->registration_number }} · Balance PKR {{ number_format((float) $openFees->sum(fn ($fee) => $fee->balance()), 2) }}</p>
                </div>
                <button type="button" @click="paymentOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-3 text-sm md:grid-cols-3">
                <div>
                    <div class="text-gray-500">Student</div>
                    <div class="font-bold text-[#0B1F3A]">{{ $student->name }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Class / Section</div>
                    <div class="font-bold text-[#0B1F3A]">{{ $student->schoolClass?->name }} {{ $student->section?->name }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Outstanding</div>
                    <div class="font-bold text-red-700">PKR {{ number_format((float) $openFees->sum(fn ($fee) => $fee->balance()), 2) }}</div>
                </div>
            </div>

            @if ($openFees->isEmpty())
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    No pending balance found for this student.
                </div>
            @else
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <select name="student_fee_id">
                        <option value="">Apply to oldest pending balance</option>
                        @foreach ($openFees as $fee)
                            <option value="{{ $fee->id }}">{{ $fee->feeHead?->name ?: 'Fee' }} {{ $fee->month }}/{{ $fee->year }} - PKR {{ number_format($fee->balance(), 2) }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="1" max="{{ $openFees->sum(fn ($fee) => $fee->balance()) }}" name="amount" required placeholder="Amount received">
                    <select name="method" required>
                        @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="paid_on" value="{{ now()->format('Y-m-d') }}" required>
                    <input name="reference_number" placeholder="Reference number optional">
                    <input type="file" name="proof">
                    <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
                </div>
            @endif

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="paymentOpen = false" class="app-button app-button-light">Cancel</button>
                <button class="app-button app-button-primary" @disabled($openFees->isEmpty())>Record Payment</button>
            </div>
        </form>
    </div>
</div>
