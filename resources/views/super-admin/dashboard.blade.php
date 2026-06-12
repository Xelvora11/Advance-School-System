<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                    Platform Owner
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Super Admin Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">Manual onboarding, schools, users, and platform activity.</p>
            </div>
            <a href="{{ route('super-admin.schools.create') }}" class="app-button app-button-primary">
                <i data-lucide="building-2" class="h-4 w-4"></i>
                Create School
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-5">
            @foreach ([
                ['Total Schools', $totalSchools, 'building-2', 'bg-blue-50', 'text-blue-700'],
                ['Active Schools', $activeSchools, 'badge-check', 'bg-green-50', 'text-green-700'],
                ['Inactive', $inactiveSchools, 'octagon-alert', 'bg-red-50', 'text-red-700'],
                ['Students', $totalStudents, 'graduation-cap', 'bg-violet-50', 'text-violet-700'],
                ['Teachers', $totalTeachers, 'user-round-check', 'bg-amber-50', 'text-amber-700'],
            ] as [$label, $value, $icon, $bg, $color])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-lg {{ $bg }} p-2 {{ $color }}"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></div>
                    </div>
                    <div class="mt-3 text-3xl font-black text-[#0B1F3A]">{{ number_format($value) }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <a href="{{ route('super-admin.schools.create') }}" class="app-card group flex cursor-pointer items-center gap-4 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[#1DA1F2] hover:bg-[#F3FAFF] hover:shadow-md">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[14px] border border-[#D7ECFD] bg-[#E8F4FE] text-[#0B1F3A] transition duration-200 group-hover:text-[#1DA1F2]">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16"></path>
                        <path d="M4 21h16"></path>
                        <path d="M10 7h3"></path>
                        <path d="M10 11h3"></path>
                        <path d="M10 15h1"></path>
                        <path d="M19 8v6"></path>
                        <path d="M22 11h-6"></path>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-[#0B1F3A]">Create School Account</div>
                    <div class="mt-1 text-sm text-gray-500">Add school and first admin</div>
                </div>
            </a>
            <a href="{{ route('super-admin.schools.index') }}" class="app-card group flex cursor-pointer items-center gap-4 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[#1DA1F2] hover:bg-[#F3FAFF] hover:shadow-md">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[14px] border border-[#D7ECFD] bg-[#E8F4FE] text-[#0B1F3A] transition duration-200 group-hover:text-[#1DA1F2]">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 21h18"></path>
                        <path d="M5 21V7l8-4 6 4v14"></path>
                        <path d="M9 21v-6h6v6"></path>
                        <path d="M9 9h.01"></path>
                        <path d="M13 9h.01"></path>
                        <path d="M17 9h.01"></path>
                        <path d="M9 12h.01"></path>
                        <path d="M13 12h.01"></path>
                        <path d="M17 12h.01"></path>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-[#0B1F3A]">Manage Schools</div>
                    <div class="mt-1 text-sm text-gray-500">Status, plans and admins</div>
                </div>
            </a>
            <a href="{{ route('profile.edit') }}" class="app-card group flex cursor-pointer items-center gap-4 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[#1DA1F2] hover:bg-[#F3FAFF] hover:shadow-md">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[14px] border border-[#D7ECFD] bg-[#E8F4FE] text-[#0B1F3A] transition duration-200 group-hover:text-[#1DA1F2]">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <circle cx="18" cy="16" r="3"></circle>
                        <path d="M18 12.5v1"></path>
                        <path d="M18 18.5v1"></path>
                        <path d="M21.03 14.25l-.87.5"></path>
                        <path d="M15.84 17.25l-.87.5"></path>
                        <path d="M21.03 17.75l-.87-.5"></path>
                        <path d="M15.84 14.75l-.87-.5"></path>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-[#0B1F3A]">Account Settings</div>
                    <div class="mt-1 text-sm text-gray-500">Profile and password</div>
                </div>
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Recent School Accounts</h2>
                    <a href="{{ route('super-admin.schools.index') }}" class="text-sm font-bold text-[#1DA1F2]">View all</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentSchools as $school)
                        <a href="{{ route('super-admin.schools.show', $school) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50">
                            <div>
                                <div class="font-semibold">{{ $school->name }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $school->city ?: 'City not set' }} · {{ $school->academic_year ?: 'No session' }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $school->isActive() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($school->account_status) }}
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-10 text-sm text-gray-500">No schools created yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Recent Login Activity</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentLogins as $login)
                        <div class="flex items-center gap-3 px-5 py-4">
                            <div class="rounded-lg bg-gray-100 p-2 text-gray-600"><i data-lucide="log-in" class="h-4 w-4"></i></div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold">{{ $login->school?->name ?: 'Platform user' }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $login->ip_address }} · {{ $login->logged_in_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-gray-500">No login activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
