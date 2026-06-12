@php
    $statusClass = fn ($active) => $active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
    $paymentClass = fn ($status) => match ($status) {
        'paid' => 'bg-green-100 text-green-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'overdue' => 'bg-red-100 text-red-700',
        default => 'bg-blue-100 text-blue-700',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="building-2" class="h-4 w-4"></i>
                    School Accounts
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Schools</h1>
                <p class="mt-1 text-sm text-gray-600">Manual onboarding, status, plans, admin accounts, and internal notes.</p>
            </div>
            <a href="{{ route('super-admin.schools.create') }}" class="app-button app-button-primary">
                <i data-lucide="circle-plus" class="h-4 w-4"></i>
                Create School
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="grid gap-3 lg:grid-cols-[1.5fr_repeat(4,1fr)_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Search school, admin, email, city" class="rounded-md border-gray-300">
            <select name="status" class="rounded-md border-gray-300">
                <option value="">School status</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="account_status" class="rounded-md border-gray-300">
                <option value="">Account status</option>
                @foreach ($accountStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('account_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="plan_name" class="rounded-md border-gray-300">
                <option value="">Plan</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan }}" @selected(request('plan_name') === $plan)>{{ $plan }}</option>
                @endforeach
            </select>
            <select name="manual_payment_status" class="rounded-md border-gray-300">
                <option value="">Payment</option>
                @foreach ($paymentStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('manual_payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="app-button app-button-dark">
                <i data-lucide="search" class="h-4 w-4"></i>
                Filter
            </button>
            </div>
        </form>

        <div class="app-card overflow-x-auto">
            <table class="app-table min-w-[1280px] divide-y divide-gray-200 text-sm">
                <thead>
                    <tr>
                        <th class="text-left">School</th>
                        <th class="text-left">Location</th>
                        <th class="text-left">Admin</th>
                        <th class="text-left">Counts</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Plan / Payment</th>
                        <th class="text-left">Created</th>
                        <th class="text-left">Last login</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($schools as $school)
                        @php($admin = $school->admins->first())
                        <tr class="align-top hover:bg-gray-50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-lg bg-[#0B1F3A] text-sm font-black text-white">
                                        @if ($school->logo_path)
                                            <img src="{{ asset('storage/'.$school->logo_path) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ Str::of($school->short_name ?: $school->name)->substr(0, 2)->upper() }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900">{{ $school->name }}</div>
                                        <div class="truncate text-gray-500">{{ $school->email ?: 'No school email' }}</div>
                                        <div class="text-gray-500">{{ $school->phone ?: 'No phone' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $school->city ?: '-' }}</div>
                                <div class="text-gray-500">{{ $school->province ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold">{{ $admin?->name ?: 'No admin' }}</div>
                                <div class="text-gray-500">{{ $admin?->email ?: '-' }}</div>
                                <div class="text-gray-500">{{ $admin?->phone ?: '-' }}</div>
                            </td>
                            <td>
                                <div>{{ number_format($school->students_count) }} students</div>
                                <div class="text-gray-500">{{ number_format($school->teachers_count) }} teachers</div>
                            </td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($school->isActive()) }}">{{ $school->isActive() ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>
                                <div class="font-semibold">{{ $school->plan_name ?: '-' }}</div>
                                <span class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $paymentClass($school->manual_payment_status) }}">
                                    {{ $paymentStatuses[$school->manual_payment_status] ?? Str::headline($school->manual_payment_status) }}
                                </span>
                            </td>
                            <td>{{ $school->created_at->format('d M Y') }}</td>
                            <td>
                                @if ($school->login_histories_max_logged_in_at)
                                    {{ \Illuminate\Support\Carbon::parse($school->login_histories_max_logged_in_at)->diffForHumans() }}
                                @else
                                    <span class="text-gray-500">Never</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div x-data="{ open: false, noteOpen: false, resetOpen: false, statusOpen: false, resetAuto: false }" class="relative inline-block">
                                    <button @click="open = !open" type="button" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Actions">
                                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-2 w-56 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-lg">
                                        <a href="{{ route('super-admin.schools.show', $school) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="eye" class="h-4 w-4"></i> View school</a>
                                        <a href="{{ route('super-admin.schools.edit', $school) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="pencil" class="h-4 w-4"></i> Edit school</a>
                                        <button type="button" @click="statusOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 {{ $school->isActive() ? 'text-red-700' : 'text-green-700' }}"><i data-lucide="{{ $school->isActive() ? 'ban' : 'badge-check' }}" class="h-4 w-4"></i> {{ $school->isActive() ? 'Deactivate' : 'Activate' }} school</button>
                                        @if ($admin)
                                            <button type="button" @click="resetOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="key-round" class="h-4 w-4"></i> Reset admin password</button>
                                        @endif
                                        <a href="{{ route('super-admin.activity', ['school_id' => $school->id]) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="history" class="h-4 w-4"></i> View activity</a>
                                        <button type="button" @click="noteOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="sticky-note" class="h-4 w-4"></i> Add internal note</button>
                                    </div>

                                    <div x-show="statusOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                        <form method="POST" action="{{ route('super-admin.schools.status', $school) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                            @csrf
                                            @method('PATCH')
                                            <div class="flex items-start gap-3">
                                                <div class="rounded-lg {{ $school->isActive() ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }} p-2">
                                                    <i data-lucide="{{ $school->isActive() ? 'ban' : 'badge-check' }}" class="h-5 w-5"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-[#0B1F3A]">{{ $school->isActive() ? 'Deactivate' : 'Activate' }} {{ $school->name }}?</h3>
                                                    <p class="mt-1 text-sm text-gray-600">{{ $school->isActive() ? 'Users from this school will not be able to log in after deactivation.' : 'Users from this school can log in again after activation.' }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" @click="statusOpen = false" class="app-button app-button-light">Cancel</button>
                                                <button class="app-button {{ $school->isActive() ? 'bg-red-600 text-white' : 'bg-green-600 text-white' }}">{{ $school->isActive() ? 'Deactivate' : 'Activate' }}</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div x-show="noteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                        <form method="POST" action="{{ route('super-admin.schools.notes.store', $school) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                            @csrf
                                            <h3 class="text-lg font-bold text-[#0B1F3A]">Add note for {{ $school->name }}</h3>
                                            <textarea name="note" rows="4" class="mt-4 rounded-md border-gray-300" required placeholder="Write an internal note"></textarea>
                                            <div class="mt-4 flex justify-end gap-2">
                                                <button type="button" @click="noteOpen = false" class="app-button app-button-light">Cancel</button>
                                                <button class="app-button app-button-primary">Save Note</button>
                                            </div>
                                        </form>
                                    </div>

                                    @if ($admin)
                                        <div x-show="resetOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                            <form method="POST" action="{{ route('super-admin.schools.admins.password', [$school, $admin]) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                @csrf
                                                @method('PATCH')
                                                <h3 class="text-lg font-bold text-[#0B1F3A]">Reset admin password</h3>
                                                <p class="mt-1 text-sm text-gray-500">{{ $admin->email }}</p>
                                                <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-gray-700">
                                                    <input type="checkbox" name="auto_generate" value="1" x-model="resetAuto">
                                                    Auto-generate password
                                                </label>
                                                <input type="text" name="password" class="mt-3 rounded-md border-gray-300" minlength="8" :required="!resetAuto" :disabled="resetAuto" placeholder="New password">
                                                <p class="mt-2 text-xs text-gray-500">Generated passwords are shown once after reset.</p>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" @click="resetOpen = false" class="app-button app-button-light">Cancel</button>
                                                    <button class="app-button app-button-dark">Reset Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-[#1DA1F2]"><i data-lucide="building-2" class="h-5 w-5"></i></div>
                                <div class="mt-3 font-bold text-[#0B1F3A]">No schools found</div>
                                <p class="mt-1 text-sm text-gray-500">Create the first school account or adjust your filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $schools->links() }}</div>
    </div>
</x-app-layout>
