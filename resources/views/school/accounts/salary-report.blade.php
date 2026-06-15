<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Salary Report</h1>
                <p class="mt-1 text-sm text-gray-600">Assigned salaries, paid amounts, remaining balances, and payment status.</p>
            </div>
            <a href="{{ route('accounts.exports.salary-report', request()->query()) }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                CSV
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('school.accounts._tabs')

        <form class="app-card grid gap-3 p-4 md:grid-cols-5">
            <select name="teacher_id">
                <option value="">All teachers</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All statuses</option>
                @foreach (['unpaid', 'partial', 'paid'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input type="number" min="1" max="12" name="month" value="{{ request('month') }}" placeholder="Month">
            <input type="number" min="2020" max="2100" name="year" value="{{ request('year') }}" placeholder="Year">
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
                            <th class="text-left">Teacher</th>
                            <th class="text-left">Month</th>
                            <th class="text-right">Assigned Salary</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Remaining</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($salaryRecords as $salary)
                            <tr>
                                <td class="font-semibold">{{ $salary->teacher?->name ?: 'Unknown teacher' }}</td>
                                <td>{{ DateTime::createFromFormat('!m', (string) $salary->salary_month)->format('F') }} {{ $salary->salary_year }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->gross_salary, 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $salary->paid_amount, 2) }}</td>
                                <td class="text-right font-bold">PKR {{ number_format((float) $salary->balance, 2) }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($salary->payment_status) {
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ ucfirst($salary->payment_status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-gray-500">No salary records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4">{{ $salaryRecords->links() }}</div>
        </div>
    </div>
</x-app-layout>
