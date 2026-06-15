@php
    $accountTabs = [
        ['label' => 'Dashboard', 'route' => 'accounts.index', 'match' => 'accounts.index', 'icon' => 'layout-dashboard'],
        ['label' => 'Income', 'route' => 'accounts.income', 'match' => 'accounts.income', 'icon' => 'trending-up'],
        ['label' => 'Expenses', 'route' => 'accounts.expenses', 'match' => 'accounts.expenses', 'icon' => 'trending-down'],
        ['label' => 'Ledger', 'route' => 'accounts.ledger', 'match' => 'accounts.ledger', 'icon' => 'book-open'],
        ['label' => 'Cash Book', 'route' => 'accounts.cash-book', 'match' => 'accounts.cash-book', 'icon' => 'wallet'],
        ['label' => 'Student Dues', 'route' => 'accounts.reports.student-dues', 'match' => 'accounts.reports.student-dues', 'icon' => 'receipt-text'],
        ['label' => 'Salary Report', 'route' => 'accounts.reports.salary', 'match' => 'accounts.reports.salary', 'icon' => 'wallet-cards'],
        ['label' => 'Summary', 'route' => 'accounts.reports.summary', 'match' => 'accounts.reports.summary', 'icon' => 'chart-column'],
    ];
@endphp

<div class="flex gap-2 overflow-x-auto rounded-lg border border-gray-200 bg-white p-2">
    @foreach ($accountTabs as $tab)
        @php($active = request()->routeIs($tab['match']))
        <a href="{{ route($tab['route']) }}"
            class="flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-[#0B1F3A] text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-[#0B1F3A]' }}">
            <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
