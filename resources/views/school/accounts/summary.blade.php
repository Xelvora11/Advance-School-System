<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="chart-column" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Income vs Expense</h1>
                <p class="mt-1 text-sm text-gray-600">Monthly income, expenses, net income, and category breakdown.</p>
            </div>
            <a href="{{ route('accounts.exports.summary', request()->query()) }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                CSV
            </a>
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

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ([
                ['Income', $incomeTotal, 'trending-up', 'bg-green-50', 'text-green-700'],
                ['Expenses', $expenseTotal, 'trending-down', 'bg-red-50', 'text-red-700'],
                ['Net Income', $netIncome, 'scale', $netIncome >= 0 ? 'bg-emerald-50' : 'bg-rose-50', $netIncome >= 0 ? 'text-emerald-700' : 'text-rose-700'],
            ] as [$label, $value, $icon, $bg, $color])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                            <div class="mt-1 text-xs text-gray-400">{{ $monthLabel }}</div>
                        </div>
                        <div class="rounded-lg {{ $bg }} p-2 {{ $color }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">PKR {{ number_format((float) $value, 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Income By Category</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[520px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Category</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($incomeByCategory as $row)
                                <tr>
                                    <td>{{ $row->category }}</td>
                                    <td class="text-right font-bold text-green-700">PKR {{ number_format((float) $row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-gray-500">No income for this month.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Expenses By Category</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[520px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Category</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenseByCategory as $row)
                                <tr>
                                    <td>{{ $row->category }}</td>
                                    <td class="text-right font-bold text-red-700">PKR {{ number_format((float) $row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-gray-500">No expenses for this month.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
