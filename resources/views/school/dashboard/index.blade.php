<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="school" class="h-4 w-4"></i>
                    School Admin
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $school->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $school->academic_year ?: 'Academic year not set' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('students.create') }}" class="app-button app-button-primary">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Add Student
                </a>
                <a href="{{ route('admissions.index') }}" class="app-button app-button-light">
                    <i data-lucide="clipboard-plus" class="h-4 w-4"></i>
                    Admission Inquiry
                </a>
                <a href="{{ route('fees.index') }}" class="app-button app-button-light">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Generate Fees
                </a>
                <a href="{{ route('notices.index') }}" class="app-button app-button-light">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    Create Notice
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @unless ($school->setup_completed)
            <div class="app-card flex flex-wrap items-center justify-between gap-4 border-amber-200 bg-amber-50 p-5">
                <div>
                    <div class="font-bold text-amber-900">School setup is pending</div>
                    <div class="mt-1 text-sm text-amber-800">Complete profile, academic year, colors, logo, signature, and document settings.</div>
                </div>
                <a href="{{ route('school.setup.edit') }}" class="app-button bg-amber-500 text-white">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                    Complete Setup
                </a>
            </div>
        @endunless

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Total Students', number_format($students), null, 'graduation-cap', 'text-blue-700', 'bg-blue-50'],
                ['Total Teachers', number_format($teachers), null, 'user-round-check', 'text-emerald-700', 'bg-emerald-50'],
                ['Attendance Today', $attendanceMarkedClasses ? $attendanceMarkedClasses.' / '.$attendanceTotalClasses.' classes' : 'No attendance marked today', $attendanceMarkedClasses ? null : '0 classes marked today', 'calendar-check', 'text-violet-700', 'bg-violet-50'],
                ['Pending Fees', 'PKR '.number_format($pendingFees), $feeDefaulters ? $feeDefaulters.' fee defaulter'.($feeDefaulters === 1 ? '' : 's') : 'No overdue defaulters', 'receipt', 'text-amber-700', 'bg-amber-50'],
                ['Collected This Month', 'PKR '.number_format($collectedThisMonth), 'Received in '.now()->format('F'), 'wallet-cards', 'text-green-700', 'bg-green-50'],
                ["Today's Collection", 'PKR '.number_format($todaysCollection), 'Cash and manual payments', 'badge-dollar-sign', 'text-sky-700', 'bg-sky-50'],
                ['Due This Week', 'PKR '.number_format($feesDueThisWeek), 'Open fees due in 7 days', 'calendar-clock', 'text-orange-700', 'bg-orange-50'],
                ['Overdue Balance', 'PKR '.number_format($overdueFees), $feeDefaulters ? 'Needs follow-up today' : 'No overdue balance', 'circle-alert', 'text-red-700', 'bg-red-50'],
                ['Unpaid Fines', 'PKR '.number_format($unpaidFines), $unpaidFineCount ? $unpaidFineCount.' open fine'.($unpaidFineCount === 1 ? '' : 's') : 'No unpaid fines', 'badge-alert', 'text-red-700', 'bg-red-50'],
                ['Teacher Salaries Due', 'PKR '.number_format($teacherSalariesDue), $teacherSalaryDueCount ? $teacherSalaryDueCount.' current salary record'.($teacherSalaryDueCount === 1 ? '' : 's') : 'No current salary dues', 'wallet-cards', 'text-indigo-700', 'bg-indigo-50'],
                ['Salary Paid This Month', 'PKR '.number_format($salaryPaidThisMonth), 'Manual teacher salary payments', 'badge-dollar-sign', 'text-green-700', 'bg-green-50'],
            ] as [$label, $value, $helper, $icon, $color, $bg])
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                        <div class="rounded-lg {{ $bg }} p-2 {{ $color }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-[#0B1F3A]">{{ $value }}</div>
                    @if ($helper)
                        <div class="mt-1 text-xs font-semibold text-gray-500">{{ $helper }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="app-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Needs Attention Today</h2>
                    <p class="mt-1 text-sm text-gray-500">A quick admin view of items that may need follow-up.</p>
                </div>
                <a href="{{ route('attendance.index') }}" class="text-sm font-bold text-[#1DA1F2]">View attendance</a>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                @foreach ([
                    ['Pending admissions', $pendingAdmissions, 'admissions.index', 'clipboard-plus', 'text-blue-700'],
                    ['Fee defaulters', $feeDefaulters, 'fees.index', 'receipt', 'text-amber-700'],
                    ['Unpaid fines', $unpaidFineCount, 'fees.index', 'badge-alert', 'text-red-700'],
                    ['Salary due', $teacherSalaryDueCount, 'salaries.index', 'wallet-cards', 'text-indigo-700'],
                    ['Missing attendance', $missingAttendance->count(), 'attendance.index', 'calendar-x', 'text-red-700'],
                    ['Unpublished exams', $unpublishedExams, 'exams.index', 'clipboard-list', 'text-violet-700'],
                ] as [$label, $value, $route, $icon, $color])
                    <a href="{{ route($route) }}" class="rounded-lg border border-gray-200 bg-white p-4 transition hover:border-[#1DA1F2] hover:bg-[#F3FAFF] hover:shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold text-gray-600">{{ $label }}</div>
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 {{ $color }}"></i>
                        </div>
                        <div class="mt-2 text-2xl font-black text-[#0B1F3A]">{{ number_format($value) }}</div>
                    </a>
                @endforeach
            </div>
            @if ($missingAttendance->isNotEmpty())
                <div class="mt-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3">
                    <div class="text-sm font-bold text-red-800">Attendance not marked</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($missingAttendance as $section)
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-red-700">
                                {{ $section->schoolClass?->name }}-{{ $section->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mt-4 rounded-lg border border-green-100 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">No missing attendance for active sections today.</div>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['Academics', 'Classes, sections and subjects', 'academics.index', 'academics'],
                ['Admissions', 'Inquiry to enrollment workflow', 'admissions.index', 'admissions'],
                ['Students', 'Admissions and guardian records', 'students.index', 'students'],
                ['Teachers', 'Staff profile and assignments', 'teachers.index', 'teachers'],
                ['Attendance', 'Daily class attendance', 'attendance.index', 'attendance'],
                ['Fees', 'Structures, challans and payments', 'fees.index', 'fees'],
                ['Exams & Results', 'Marksheets and result publishing', 'exams.index', 'exams'],
                ['Notices', 'Parent and teacher notices', 'notices.index', 'notices'],
                ['Reports', 'Export and operational summaries', 'reports.index', 'reports'],
                ['School Setup', 'Branding and PDF template', 'school.setup.edit', 'setup'],
            ] as [$title, $subtitle, $route, $icon])
                <a href="{{ route($route) }}" class="app-card group flex cursor-pointer items-center gap-4 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[#1DA1F2] hover:bg-[#F3FAFF] hover:shadow-md">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[14px] border border-[#D7ECFD] bg-[#E8F4FE] text-[#0B1F3A] transition duration-200 group-hover:text-[#1DA1F2]">
                        @switch($icon)
                            @case('academics')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 7v14"></path>
                                    <path d="M3 18a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4v13a4 4 0 0 0-4-4H3Z"></path>
                                    <path d="M21 18h-5a4 4 0 0 0-4 3V8a4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1Z"></path>
                                </svg>
                                @break

                            @case('admissions')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 5h6"></path>
                                    <path d="M9 3h6v4H9z"></path>
                                    <path d="M5 5h2"></path>
                                    <path d="M17 5h2a2 2 0 0 1 2 2v4"></path>
                                    <path d="M5 5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"></path>
                                    <path d="M7 12h5"></path>
                                    <path d="M7 16h3"></path>
                                    <path d="M18 14v6"></path>
                                    <path d="M21 17h-6"></path>
                                </svg>
                                @break

                            @case('students')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                @break

                            @case('teachers')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="m16 11 2 2 4-4"></path>
                                    <path d="M17 21h4"></path>
                                    <path d="M19 17v4"></path>
                                </svg>
                                @break

                            @case('attendance')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M8 2v4"></path>
                                    <path d="M16 2v4"></path>
                                    <path d="M3 10h18"></path>
                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    <path d="m9 16 2 2 4-4"></path>
                                </svg>
                                @break

                            @case('fees')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 7V6a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h15"></path>
                                    <path d="M5 10a3 3 0 0 0-3 3v5a3 3 0 0 0 3 3h15a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2"></path>
                                    <path d="M16 15h.01"></path>
                                    <path d="M7 15h5"></path>
                                </svg>
                                @break

                            @case('exams')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <path d="M14 2v6h6"></path>
                                    <path d="M8 13h5"></path>
                                    <path d="M8 17h3"></path>
                                    <path d="m14 17 2 2 4-4"></path>
                                </svg>
                                @break

                            @case('notices')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m3 11 18-5v12L3 13z"></path>
                                    <path d="M11 14v5a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-6"></path>
                                    <path d="M21 9v6"></path>
                                </svg>
                                @break

                            @case('reports')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3v18h18"></path>
                                    <path d="M7 16v-5"></path>
                                    <path d="M12 16V8"></path>
                                    <path d="M17 16v-3"></path>
                                </svg>
                                @break

                            @case('setup')
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path>
                                    <path d="M3 21h18"></path>
                                    <path d="M8 7h4"></path>
                                    <path d="M8 11h4"></path>
                                    <circle cx="18" cy="14" r="3"></circle>
                                    <path d="M18 10.5v1"></path>
                                    <path d="M18 16.5v1"></path>
                                    <path d="M21.03 12.25l-.87.5"></path>
                                    <path d="M15.84 15.25l-.87.5"></path>
                                    <path d="M21.03 15.75l-.87-.5"></path>
                                    <path d="M15.84 12.75l-.87-.5"></path>
                                </svg>
                                @break
                        @endswitch
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-[#0B1F3A]">{{ $title }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ $subtitle }}</div>
                    </div>
                    <i data-lucide="chevron-right" class="h-5 w-5 text-gray-300 group-hover:text-[#1DA1F2]"></i>
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
            <div class="app-card">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Quick Actions</h2>
                    <i data-lucide="zap" class="h-5 w-5 text-[#1DA1F2]"></i>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-2">
                    @foreach ([
                        ['New Student', 'students.create', 'user-plus'],
                        ['Admission Inquiry', 'admissions.index', 'clipboard-plus'],
                        ['New Teacher', 'teachers.create', 'user-round-plus'],
                        ['Attendance Monitor', 'attendance.index', 'calendar-check'],
                        ['Generate Fee', 'fees.index', 'wallet-cards'],
                        ['Create Exam', 'exams.index', 'clipboard-plus'],
                        ['Publish Notice', 'notices.index', 'send'],
                    ] as [$label, $route, $icon])
                        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-800 hover:border-[#1DA1F2] hover:text-[#0B1F3A]">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-[#1DA1F2]"></i>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="app-card">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Latest Notices</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($notices as $notice)
                        <div class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $notice->title }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $notice->audience_type)) }}</div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $notice->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $notice->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-gray-500">No notices yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
