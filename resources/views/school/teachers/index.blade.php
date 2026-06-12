<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="user-round-check" class="h-4 w-4"></i>
                    Staff Records
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Teachers</h1>
                <p class="mt-1 text-sm text-gray-600">Teacher profiles, login status, and assigned classes.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('salaries.index') }}" class="app-button app-button-light">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Salary Records
                </a>
                <a href="{{ route('teachers.create') }}" class="app-button app-button-primary">
                    <i data-lucide="user-round-plus" class="h-4 w-4"></i>
                    Add Teacher
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="app-card overflow-hidden">
            <table class="app-table min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Teacher</th>
                        <th class="text-left">Contact</th>
                        <th class="text-left">Assignments</th>
                        <th class="text-left">Salary</th>
                        <th class="text-left">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($teachers as $teacher)
                        <tr class="hover:bg-gray-50">
                            <td>
                                <div class="font-bold text-gray-900">{{ $teacher->name }}</div>
                                <div class="text-gray-500">{{ $teacher->qualification ?: 'Qualification not set' }}</div>
                            </td>
                            <td>{{ $teacher->phone ?: '-' }}<div class="text-gray-500">{{ $teacher->email ?: 'No email' }}</div></td>
                            <td>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $teacher->assignments->count() }} assigned</span>
                            </td>
                            <td>
                                <div class="font-semibold text-[#0B1F3A]">{{ $teacher->basic_salary ? 'PKR '.number_format((float) $teacher->basic_salary, 2) : '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $teacher->salaryPayments->count() }} salary record{{ $teacher->salaryPayments->count() === 1 ? '' : 's' }}</div>
                            </td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $teacher->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($teacher->status) }}</span>
                            </td>
                            <td class="text-right">
                                <div x-data="{ open: false, resetOpen: false }" class="relative inline-block">
                                    <button @click="open = !open" type="button" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Actions">
                                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-2 w-56 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-lg">
                                        <a href="{{ route('teachers.show', $teacher) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="eye" class="h-4 w-4"></i> View Profile</a>
                                        <a href="{{ route('teachers.edit', $teacher) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="pencil" class="h-4 w-4"></i> Edit</a>
                                        <a href="{{ route('teachers.edit', $teacher) }}#assignment" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="layers-3" class="h-4 w-4"></i> Assign Class/Subject</a>
                                        <a href="{{ route('salaries.index', ['teacher_id' => $teacher->id]) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="wallet-cards" class="h-4 w-4"></i> Salary Records</a>
                                        @if ($teacher->user)
                                            <button type="button" @click="resetOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="key-round" class="h-4 w-4"></i> Reset Login Password</button>
                                        @endif
                                        <form method="POST" action="{{ route('teachers.status', $teacher) }}" onsubmit="return confirm('{{ $teacher->status === 'active' ? 'Deactivate' : 'Activate' }} this teacher?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="flex w-full items-center gap-2 px-3 py-2 text-sm {{ $teacher->status === 'active' ? 'text-red-700 hover:bg-red-50' : 'text-green-700 hover:bg-green-50' }}">
                                                <i data-lucide="{{ $teacher->status === 'active' ? 'user-x' : 'user-check' }}" class="h-4 w-4"></i>
                                                {{ $teacher->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                    @if ($teacher->user)
                                        <div x-show="resetOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                            <form method="POST" action="{{ route('teachers.password', $teacher) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                @csrf
                                                @method('PATCH')
                                                <h3 class="text-lg font-bold text-[#0B1F3A]">Reset teacher password</h3>
                                                <p class="mt-1 text-sm text-gray-500">{{ $teacher->email }}</p>
                                                <input type="text" name="password" class="mt-4 rounded-md border-gray-300" minlength="8" required placeholder="New password">
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
                        <tr><td colspan="6" class="py-12 text-center text-gray-500">No teachers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $teachers->links() }}</div>
    </div>
</x-app-layout>
