<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                <i data-lucide="history" class="h-4 w-4"></i>
                Audit Trail
            </div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Activity Logs</h1>
            <p class="mt-1 text-sm text-gray-600">Filter platform and school activity by school, user, role, action, and date.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_auto]">
            <select name="school_id" class="rounded-md border-gray-300">
                <option value="">All schools</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected(request('school_id') == $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
            <select name="user_id" class="rounded-md border-gray-300">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                @endforeach
            </select>
            <select name="role" class="rounded-md border-gray-300">
                <option value="">All roles</option>
                @foreach (['super_admin', 'school_admin', 'principal', 'teacher', 'parent'] as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ Str::headline($role) }}</option>
                @endforeach
            </select>
            <select name="action" class="rounded-md border-gray-300">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ Str::headline($action) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300" aria-label="Date from">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300" aria-label="Date to">
            <button class="app-button app-button-dark">
                <i data-lucide="filter" class="h-4 w-4"></i>
                Filter
            </button>
            </div>
        </form>

        <div class="app-card overflow-x-auto">
            <table class="app-table min-w-[1100px] divide-y divide-gray-200 text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Action</th>
                        <th class="text-left">School</th>
                        <th class="text-left">User</th>
                        <th class="text-left">Role</th>
                        <th class="text-left">IP address</th>
                        <th class="text-left">Description</th>
                        <th class="text-left">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr class="align-top hover:bg-gray-50">
                            <td><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">{{ Str::headline($log->action) }}</span></td>
                            <td>{{ $log->school?->name ?: 'Platform' }}</td>
                            <td>
                                <div class="font-semibold">{{ $log->user?->name ?: '-' }}</div>
                                <div class="text-gray-500">{{ $log->user?->email }}</div>
                            </td>
                            <td>{{ $log->role ? Str::headline($log->role) : ($log->user ? Str::headline($log->user->role) : '-') }}</td>
                            <td>{{ $log->ip_address ?: '-' }}</td>
                            <td>{{ $log->description ?: '-' }}</td>
                            <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-[#1DA1F2]"><i data-lucide="history" class="h-5 w-5"></i></div>
                                <div class="mt-3 font-bold text-[#0B1F3A]">No activity found</div>
                                <p class="mt-1 text-sm text-gray-500">Try changing the filters or perform an action first.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
