<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="trending-up" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Income</h1>
                <p class="mt-1 text-sm text-gray-600">Record school income and review income transactions.</p>
            </div>
            <a href="{{ route('accounts.exports.income', request()->query()) }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                CSV
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('school.accounts._tabs')

        <div class="grid gap-6 xl:grid-cols-3">
            <form method="POST" action="{{ route('accounts.income.store') }}" class="app-card grid gap-3 p-5">
                @csrf
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Add Income</h2>
                    <p class="mt-1 text-sm text-gray-500">Manual income entries appear in the master ledger.</p>
                </div>
                <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                <select name="category" required>
                    <option value="">Income category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->name }}" @selected(old('category') === $category->name)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <input name="description" value="{{ old('description') }}" required placeholder="Description">
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="Amount">
                <textarea name="notes" rows="3" class="rounded-md border-gray-300" placeholder="Notes optional">{{ old('notes') }}</textarea>
                <button class="app-button app-button-primary">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Save Income
                </button>
            </form>

            <div class="space-y-4 xl:col-span-2">
                <form class="app-card grid gap-3 p-4 md:grid-cols-5">
                    <input name="search" value="{{ request('search') }}" placeholder="Search">
                    <select name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->name }}" @selected(request('category') === $category->name)>{{ $category->name }}</option>
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
                        <table class="app-table min-w-[900px] divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Date</th>
                                    <th class="text-left">Reference</th>
                                    <th class="text-left">Category</th>
                                    <th class="text-left">Description</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->transaction_date)->format('d M Y') }}</td>
                                        <td>{{ $transaction->reference_no }}</td>
                                        <td>{{ $transaction->category }}</td>
                                        <td>
                                            <div class="font-semibold">{{ $transaction->description }}</div>
                                            <div class="text-gray-500">{{ $transaction->source ?: 'Manual Entry' }}</div>
                                        </td>
                                        <td class="text-right font-bold text-green-700">PKR {{ number_format((float) $transaction->income_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No income transactions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 p-4">{{ $transactions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
