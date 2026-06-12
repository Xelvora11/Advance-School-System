@php
    $paymentClass = match ($school->manual_payment_status) {
        'paid' => 'bg-green-100 text-green-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'overdue' => 'bg-red-100 text-red-700',
        default => 'bg-blue-100 text-blue-700',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl bg-[#0B1F3A] text-lg font-black text-white">
                    @if ($school->logo_path)
                        <img src="{{ asset('storage/'.$school->logo_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ Str::of($school->short_name ?: $school->name)->substr(0, 2)->upper() }}
                    @endif
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-[#0B1F3A]">{{ $school->name }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ $school->city ?: 'City not set' }} · {{ $school->province ?: 'Province not set' }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $school->isActive() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $school->isActive() ? 'Active' : 'Inactive' }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $paymentClass }}">{{ $paymentStatuses[$school->manual_payment_status] ?? Str::headline($school->manual_payment_status) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('super-admin.activity', ['school_id' => $school->id]) }}" class="app-button app-button-light">
                    <i data-lucide="history" class="h-4 w-4"></i>
                    Activity
                </a>
                <a href="{{ route('super-admin.schools.edit', $school) }}" class="app-button app-button-primary">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                    Edit School
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_.8fr] lg:px-8">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([['Students', $school->students_count, 'graduation-cap'], ['Teachers', $school->teachers_count, 'user-round-check'], ['Classes', $school->classes_count, 'layers-3']] as [$label, $value, $icon])
                    <div class="app-card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold text-gray-500">{{ $label }}</div>
                            <div class="rounded-lg bg-blue-50 p-2 text-[#1DA1F2]"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></div>
                        </div>
                        <div class="mt-3 text-3xl font-black text-[#0B1F3A]">{{ number_format($value) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="app-card p-6">
                <h2 class="text-lg font-bold text-[#0B1F3A]">School Profile</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ([['School ID', $school->id], ['Email', $school->email], ['Phone', $school->phone], ['Academic year', $school->academic_year], ['Short name', $school->short_name], ['Plan', $school->plan_name], ['Created', $school->created_at->format('d M Y')], ['Setup completed', $school->setup_completed ? 'Yes' : 'No']] as [$label, $value])
                        <div>
                            <dt class="text-sm text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 font-medium">{{ $value ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-5">
                    <dt class="text-sm text-gray-500">Address</dt>
                    <dd class="mt-1">{{ $school->address ?: '-' }}</dd>
                </div>
            </div>

            <div class="app-card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-[#0B1F3A]">Manual Plan & Payment Tracking</h2>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $paymentClass }}">{{ $paymentStatuses[$school->manual_payment_status] ?? Str::headline($school->manual_payment_status) }}</span>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ([['Account status', ucfirst($school->account_status)], ['School status', ucfirst($school->status)], ['Start date', optional($school->start_date)->format('d M Y')], ['End date', optional($school->end_date)->format('d M Y')]] as [$label, $value])
                        <div>
                            <dt class="text-sm text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 font-medium">{{ $value ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-sm font-semibold text-gray-500">Payment notes</div>
                        <pre class="mt-2 whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-sm text-gray-700">{{ $school->internal_payment_note ?: 'No payment notes yet.' }}</pre>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-500">Support notes</div>
                        <pre class="mt-2 whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-sm text-gray-700">{{ $school->internal_support_note ?: 'No support notes yet.' }}</pre>
                    </div>
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Internal Notes</h2>
                    <span class="text-xs font-semibold text-gray-500">{{ number_format($internalNotes->total()) }} total</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($internalNotes as $note)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold text-gray-900">{{ $note->user?->name ?: 'System' }}</div>
                                <span class="text-xs text-gray-500">{{ $note->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $note->note }}</div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-gray-500">No internal notes yet.</div>
                    @endforelse
                </div>
                @if ($internalNotes->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">{{ $internalNotes->links() }}</div>
                @endif
            </div>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Activity Timeline</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($activityLogs as $log)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">{{ Str::headline($log->action) }}</span>
                                <span class="text-xs text-gray-500">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="mt-2 text-sm text-gray-700">{{ $log->description ?: '-' }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $log->user?->name ?: 'System' }} · {{ $log->role ? Str::headline($log->role) : ($log->user ? Str::headline($log->user->role) : '-') }} · {{ $log->ip_address ?: 'No IP' }}</div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-gray-500">No activity recorded for this school yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div x-data="{ noteOpen: false, statusOpen: false }" class="app-card p-6">
                <h2 class="text-lg font-bold text-[#0B1F3A]">Quick Actions</h2>
                <div class="mt-4 grid gap-3">
                    <a href="{{ route('super-admin.schools.edit', $school) }}" class="app-button app-button-primary">
                        <i data-lucide="pencil" class="h-4 w-4"></i>
                        Edit school
                    </a>
                    <button type="button" @click="statusOpen = true" class="app-button w-full {{ $school->isActive() ? 'bg-red-600 text-white' : 'bg-green-600 text-white' }}">
                        <i data-lucide="{{ $school->isActive() ? 'ban' : 'badge-check' }}" class="h-4 w-4"></i>
                        {{ $school->isActive() ? 'Deactivate school' : 'Activate school' }}
                    </button>
                    <button type="button" @click="noteOpen = true" class="app-button app-button-light">
                        <i data-lucide="sticky-note" class="h-4 w-4"></i>
                        Add internal note
                    </button>
                </div>

                <div x-show="statusOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                    <form method="POST" action="{{ route('super-admin.schools.status', $school) }}" class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg {{ $school->isActive() ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }} p-2">
                                <i data-lucide="{{ $school->isActive() ? 'ban' : 'badge-check' }}" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#0B1F3A]">{{ $school->isActive() ? 'Deactivate' : 'Activate' }} school?</h3>
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
                    <form method="POST" action="{{ route('super-admin.schools.notes.store', $school) }}" class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                        @csrf
                        <h3 class="text-lg font-bold text-[#0B1F3A]">Add internal note</h3>
                        <textarea name="note" rows="4" class="mt-4 rounded-md border-gray-300" required placeholder="Write a note for internal use"></textarea>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" @click="noteOpen = false" class="app-button app-button-light">Cancel</button>
                            <button class="app-button app-button-primary">Save Note</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="app-card p-6">
                <h2 class="text-lg font-bold text-[#0B1F3A]">School Admins</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($admins as $admin)
                        <div class="rounded-lg border border-gray-200 p-4" x-data="{ resetOpen: false, resetAuto: false }">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-bold">{{ $admin->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $admin->email }}</div>
                                    <div class="text-sm text-gray-500">{{ $admin->phone ?: 'No phone' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Last login: {{ optional($admin->last_login_at)->diffForHumans() ?: 'Never' }}</div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $admin->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $admin->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                            <button type="button" @click="resetOpen = true" class="app-button app-button-dark mt-4 w-full">
                                <i data-lucide="key-round" class="h-4 w-4"></i>
                                Reset Password
                            </button>
                            <div x-show="resetOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                <form method="POST" action="{{ route('super-admin.schools.admins.password', [$school, $admin]) }}" class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
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
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No school admins found.</p>
                    @endforelse
                </div>
            </div>

            <div class="app-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-[#0B1F3A]">Recent Logins</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentLogins as $login)
                        <div class="px-5 py-4">
                            <div class="font-semibold">{{ $login->user?->name ?: 'Unknown user' }}</div>
                            <div class="text-sm text-gray-500">{{ $login->user?->email ?: 'No linked email' }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ $login->ip_address ?: 'No IP recorded' }} · {{ $login->logged_in_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-gray-500">No recent login activity.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
