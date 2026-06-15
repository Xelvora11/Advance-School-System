@php
    $user = Auth::user();
    $roleLabel = Str::headline($user->role);
    $unreadMessages = class_exists(\App\Models\MessageThreadParticipant::class)
        ? \App\Models\MessageThreadParticipant::unreadCountForUser($user->id)
        : 0;

    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'layout-dashboard'],
    ];

    if ($user->role === 'super_admin') {
        $items = [
            ['label' => 'Dashboard', 'route' => 'super-admin.dashboard', 'match' => 'super-admin.dashboard', 'icon' => 'layout-dashboard'],
            ['label' => 'Schools', 'route' => 'super-admin.schools.index', 'match' => 'super-admin.schools.*', 'icon' => 'building-2'],
            ['label' => 'Create School', 'route' => 'super-admin.schools.create', 'match' => 'super-admin.schools.create', 'icon' => 'circle-plus'],
            ['label' => 'Activity Logs', 'route' => 'super-admin.activity', 'match' => 'super-admin.activity', 'icon' => 'history'],
        ];
    }

    if ($user->role === 'school_admin') {
        $items = [
            ['label' => 'Dashboard', 'route' => 'school.dashboard', 'match' => 'school.dashboard', 'icon' => 'layout-dashboard'],
            ['label' => 'School Setup', 'route' => 'school.setup.edit', 'match' => 'school.setup.*', 'icon' => 'settings'],
            ['label' => 'Classes & Subjects', 'route' => 'academics.index', 'match' => 'academics.*', 'icon' => 'layers-3'],
            ['label' => 'Admissions', 'route' => 'admissions.index', 'match' => 'admissions.*', 'icon' => 'clipboard-plus'],
            ['label' => 'Students', 'route' => 'students.index', 'match' => 'students.*', 'icon' => 'graduation-cap'],
            ['label' => 'Parents', 'route' => 'parents.index', 'match' => 'parents.*', 'icon' => 'users'],
            ['label' => 'Teachers', 'route' => 'teachers.index', 'match' => 'teachers.*', 'icon' => 'user-round-check'],
            ['label' => 'Attendance', 'route' => 'attendance.index', 'match' => 'attendance.*', 'icon' => 'calendar-check'],
            ['label' => 'Fees', 'route' => 'fees.index', 'match' => 'fees.*', 'icon' => 'receipt'],
            ['label' => 'Salaries', 'route' => 'salaries.index', 'match' => 'salaries.*', 'icon' => 'wallet-cards'],
            ['label' => 'Accounts', 'route' => 'accounts.index', 'match' => 'accounts.*', 'icon' => 'landmark'],
            ['label' => 'Exams & Results', 'route' => 'exams.index', 'match' => 'exams.*', 'icon' => 'clipboard-list'],
            ['label' => 'Notices', 'route' => 'notices.index', 'match' => 'notices.*', 'icon' => 'megaphone'],
            ['label' => 'Messages', 'route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'messages-square', 'badge' => $unreadMessages],
            ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'chart-column'],
        ];
    }

    if (in_array($user->role, ['teacher', 'principal'], true)) {
        $items = [
            ['label' => 'Dashboard', 'route' => $user->role === 'teacher' ? 'teacher.dashboard' : 'principal.dashboard', 'match' => $user->role === 'teacher' ? 'teacher.dashboard' : 'principal.dashboard', 'icon' => 'layout-dashboard'],
            ['label' => 'Attendance', 'route' => 'attendance.index', 'match' => 'attendance.*', 'icon' => 'calendar-check'],
            ['label' => 'Exams & Marks', 'route' => 'exams.index', 'match' => 'exams.*', 'icon' => 'clipboard-list'],
            ['label' => 'Messages', 'route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'messages-square', 'badge' => $unreadMessages],
            ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'chart-column'],
        ];
    }

    if ($user->role === 'parent') {
        $items = [
            ['label' => 'Dashboard', 'route' => 'parent.dashboard', 'match' => 'parent.dashboard', 'icon' => 'home'],
            ['label' => 'My Children', 'route' => 'parent.dashboard', 'match' => 'parent.students.*', 'icon' => 'graduation-cap'],
            ['label' => 'Messages', 'route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'messages-square', 'badge' => $unreadMessages],
            ['label' => 'Profile', 'route' => 'profile.edit', 'match' => 'profile.*', 'icon' => 'user'],
        ];
    }

    if ($user->role === 'student') {
        $items = [
            ['label' => 'Dashboard', 'route' => 'student.dashboard', 'match' => 'student.dashboard', 'icon' => 'home'],
            ['label' => 'Profile', 'route' => 'student.profile', 'match' => 'student.profile', 'icon' => 'user'],
            ['label' => 'Attendance', 'route' => 'student.attendance', 'match' => 'student.attendance', 'icon' => 'calendar-check'],
            ['label' => 'Fees', 'route' => 'student.fees', 'match' => 'student.fees', 'icon' => 'receipt'],
            ['label' => 'Results', 'route' => 'student.results', 'match' => 'student.results', 'icon' => 'clipboard-list'],
            ['label' => 'Notices', 'route' => 'student.notices', 'match' => 'student.notices', 'icon' => 'megaphone'],
            ['label' => 'Messages', 'route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'messages-square', 'badge' => $unreadMessages],
        ];
    }
@endphp

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/40 lg:hidden" @click="sidebarOpen = false"></div>

<aside x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-gray-200 bg-[#0B1F3A] text-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-sm font-black text-[#0B1F3A]">AS</div>
            <div>
                <div class="text-sm font-bold leading-5">Advance School</div>
                <div class="text-xs text-blue-100">{{ $roleLabel }}</div>
            </div>
        </a>
        <button class="rounded-md p-2 text-blue-100 lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($items as $item)
            @php($active = request()->routeIs($item['match']))
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-white text-[#0B1F3A]' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0"></i>
                <span>{{ $item['label'] }}</span>
                @if (($item['badge'] ?? 0) > 0)
                    <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <div class="rounded-lg bg-white/10 p-3">
            <div class="truncate text-sm font-semibold">{{ $user->name }}</div>
            <div class="truncate text-xs text-blue-100">{{ $user->email }}</div>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <a href="{{ route('profile.edit') }}" class="app-button border border-white/20 bg-white/10 text-xs text-white hover:bg-white/15">
                <i data-lucide="user" class="h-4 w-4"></i>
                Profile
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="app-button w-full border border-white/20 bg-white/10 text-xs text-white hover:bg-white/15">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
    <button class="rounded-lg border border-gray-200 p-2" @click="sidebarOpen = true" aria-label="Open menu">
        <i data-lucide="menu" class="h-5 w-5"></i>
    </button>
    <div class="text-sm font-bold text-[#0B1F3A]">Advance School</div>
    <a href="{{ route('profile.edit') }}" class="rounded-lg border border-gray-200 p-2" aria-label="Profile">
        <i data-lucide="user" class="h-5 w-5"></i>
    </a>
</div>
