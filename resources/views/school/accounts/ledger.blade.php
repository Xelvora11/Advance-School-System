<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="book-open" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Master Ledger</h1>
                <p class="mt-1 text-sm text-gray-600">Every income and expense transaction with running balance.</p>
            </div>
            <a href="{{ route('accounts.exports.ledger', request()->query()) }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                CSV
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('school.accounts._tabs')

        <form class="app-card grid gap-3 p-4 md:grid-cols-3 xl:grid-cols-6">
            <input name="search" value="{{ request('search') }}" placeholder="Reference or description">
            <select name="type">
                <option value="">All types</option>
                <option value="income" @selected(request('type') === 'income')>Income</option>
                <option value="expense" @selected(request('type') === 'expense')>Expense</option>
            </select>
            <select name="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}">
            <button class="app-button app-button-dark">
                <i data-lucide="filter" class="h-4 w-4"></i>
                Apply
            </button>
        </form>

        <div class="app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table min-w-[1080px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Reference No</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Category</th>
                            <th class="text-left">Description</th>
                            <th class="text-right">Income</th>
                            <th class="text-right">Expense</th>
                            <th class="text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ optional($transaction->transaction_date)->format('d M Y') }}</td>
                                <td>{{ $transaction->reference_no }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $transaction->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td>{{ $transaction->category }}</td>
                                <td>
                                    <div class="font-semibold">{{ $transaction->description }}</div>
                                    <div class="text-gray-500">{{ $transaction->source ?: '-' }}</div>
                                </td>
                                <td class="text-right text-green-700">PKR {{ number_format((float) $transaction->income_amount, 2) }}</td>
                                <td class="text-right text-red-700">PKR {{ number_format((float) $transaction->expense_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $transaction->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-8 text-center text-gray-500">No ledger transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4">{{ $transactions->links() }}</div>
        </div>
    </div>
</x-app-layout>
