<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="landmark" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Accounts Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">Income, expenses, fee collections, salaries, and cash position.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('accounts.income') }}" class="app-button app-button-primary">
                    <i data-lucide="circle-plus" class="h-4 w-4"></i>
                    Income
                </a>
                <a href="{{ route('accounts.expenses') }}" class="app-button app-button-light">
                    <i data-lucide="minus-circle" class="h-4 w-4"></i>
                    Expense
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('school.accounts._tabs')

        <form class="app-card flex flex-wrap items-end gap-3 p-4">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Month</label>
                <input type="month" name="month" value="{{ $monthValue }}" class="rounded-md border-gray-300">
            </div>
            <button class="app-button app-button-dark">
                <i data-lucide="filter" class="h-4 w-4"></i>
                Apply
            </button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Total Income This Month', $summary['totalIncome'], 'trending-up', 'bg-green-50', 'text-green-700'],
                ['Total Expenses This Month', $summary['totalExpenses'], 'trending-down', 'bg-red-50', 'text-red-700'],
                ['Fee Collection This Month', $summary['feeCollection'], 'receipt', 'bg-blue-50', 'text-blue-700'],
                ['Salary Expenses This Month', $summary['salaryExpenses'], 'wallet-cards', 'bg-amber-50', 'text-amber-700'],
                ['Current Cash Balance', $summary['cashBalance'], 'wallet', 'bg-slate-50', 'text-slate-700'],
                ['Outstanding Student Fees', $summary['outstandingFees'], 'receipt-text', 'bg-orange-50', 'text-orange-700'],
                ['Net Income', $summary['netIncome'], 'scale', $summary['netIncome'] >= 0 ? 'bg-emerald-50' : 'bg-rose-50', $summary['netIncome'] >= 0 ? 'text-emerald-700' : 'text-rose-700'],
            ] as [$label, $value, $icon, $bg, $color])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-lg {{ $bg }} p-2 {{ $color }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">PKR {{ number_format((float) $value, 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="app-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Recent Transactions</h2>
                    <p class="mt-1 text-sm text-gray-500">Latest income and expense entries across the ledger.</p>
                </div>
                <a href="{{ route('accounts.ledger') }}" class="app-button app-button-light">
                    <i data-lucide="book-open" class="h-4 w-4"></i>
                    Ledger
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[900px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Direction</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentTransactions as $transaction)
                            <tr>
                                <td>{{ optional($transaction->transaction_date)->format('d M Y') }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $transaction->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-semibold">{{ $transaction->description }}</div>
                                    <div class="text-gray-500">{{ $transaction->reference_no }} · {{ $transaction->category }}</div>
                                </td>
                                <td class="text-right font-bold">PKR {{ number_format((float) ($transaction->type === 'income' ? $transaction->income_amount : $transaction->expense_amount), 2) }}</td>
                                <td>{{ $transaction->direction() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-gray-500">No account transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
