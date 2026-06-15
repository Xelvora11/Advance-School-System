<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="wallet" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Cash Book</h1>
                <p class="mt-1 text-sm text-gray-600">Opening balance plus income minus expenses equals closing balance.</p>
            </div>
            <a href="{{ route('accounts.exports.ledger', request()->query()) }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                Ledger CSV
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('school.accounts._tabs')

        <form class="app-card grid gap-3 p-4 md:grid-cols-5">
            <select name="period">
                <option value="daily" @selected($period === 'daily')>Daily</option>
                <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                <option value="yearly" @selected($period === 'yearly')>Yearly</option>
            </select>
            <input type="date" name="date" value="{{ $period === 'daily' ? $periodValue : now()->toDateString() }}">
            <input type="month" name="month" value="{{ $period === 'monthly' ? $periodValue : now()->format('Y-m') }}">
            <input type="number" min="2020" max="2100" name="year" value="{{ $period === 'yearly' ? $periodValue : now()->year }}">
            <button class="app-button app-button-dark">
                <i data-lucide="filter" class="h-4 w-4"></i>
                Apply
            </button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Opening Balance', $openingBalance, 'log-in', 'bg-slate-50', 'text-slate-700'],
                ['Total Income', $totalIncome, 'trending-up', 'bg-green-50', 'text-green-700'],
                ['Total Expenses', $totalExpenses, 'trending-down', 'bg-red-50', 'text-red-700'],
                ['Closing Balance', $closingBalance, 'wallet', $closingBalance >= 0 ? 'bg-emerald-50' : 'bg-rose-50', $closingBalance >= 0 ? 'text-emerald-700' : 'text-rose-700'],
            ] as [$label, $value, $icon, $bg, $color])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                            <div class="mt-1 text-xs text-gray-400">{{ $periodLabel }}</div>
                        </div>
                        <div class="rounded-lg {{ $bg }} p-2 {{ $color }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">PKR {{ number_format((float) $value, 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="app-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-bold text-[#0B1F3A]">{{ $periodLabel }} Transactions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[960px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Reference</th>
                            <th class="text-left">Description</th>
                            <th class="text-right">Income</th>
                            <th class="text-right">Expense</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ optional($transaction->transaction_date)->format('d M Y') }}</td>
                                <td>{{ $transaction->reference_no }}</td>
                                <td>
                                    <div class="font-semibold">{{ $transaction->description }}</div>
                                    <div class="text-gray-500">{{ ucfirst($transaction->type) }} · {{ $transaction->category }}</div>
                                </td>
                                <td class="text-right text-green-700">PKR {{ number_format((float) $transaction->income_amount, 2) }}</td>
                                <td class="text-right text-red-700">PKR {{ number_format((float) $transaction->expense_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $transaction->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-gray-500">No cash book transactions for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4">{{ $transactions->links() }}</div>
        </div>
    </div>
</x-app-layout>
