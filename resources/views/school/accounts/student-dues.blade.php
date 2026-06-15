<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="receipt-text" class="h-4 w-4"></i>
                    Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Student Dues Report</h1>
                <p class="mt-1 text-sm text-gray-600">Assigned fees, paid amount, balance, fines, and outstanding dues.</p>
            </div>
            <a href="{{ route('accounts.exports.student-dues', request()->query()) }}" class="app-button app-button-light">
                <i data-lucide="download" class="h-4 w-4"></i>
                CSV
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('school.accounts._tabs')

        <form class="app-card grid gap-3 p-4 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Student, reg no, phone">
            <select name="class_id">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <select name="section_id">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->schoolClass?->name }} - {{ $section->name }}</option>
                @endforeach
            </select>
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
                            <th class="text-left">Student</th>
                            <th class="text-left">Class</th>
                            <th class="text-right">Total Assigned</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-right">Fine</th>
                            <th class="text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $row)
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ $row['student'] }}</div>
                                    <div class="text-gray-500">{{ $row['registration'] ?: 'No registration' }}</div>
                                </td>
                                <td>{{ $row['class'] }} {{ $row['section'] }}</td>
                                <td class="text-right">PKR {{ number_format((float) $row['total_assigned'], 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $row['paid'], 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $row['balance'], 2) }}</td>
                                <td class="text-right">PKR {{ number_format((float) $row['fine'], 2) }}</td>
                                <td class="text-right font-bold text-red-700">PKR {{ number_format((float) $row['outstanding'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-gray-500">No student dues found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4">{{ $students->links() }}</div>
        </div>
    </div>
</x-app-layout>
