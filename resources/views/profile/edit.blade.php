<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                <i data-lucide="user-cog" class="h-4 w-4"></i>
                Account Settings
            </div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Profile</h1>
            <p class="mt-1 text-sm text-gray-600">Manage your name, email, and password.</p>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[.8fr_1.2fr] lg:px-8">
        <div class="space-y-6">
            <div class="app-card p-6">
                <h2 class="text-lg font-bold text-[#0B1F3A]">Account Overview</h2>
                <dl class="mt-5 space-y-4">
                    <div>
                        <dt class="text-sm text-gray-500">Role</dt>
                        <dd class="mt-1 font-semibold">{{ Str::headline($user->role) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Last login</dt>
                        <dd class="mt-1 font-semibold">{{ optional($user->last_login_at)->format('d M Y, h:i A') ?: 'Not recorded yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">School</dt>
                        <dd class="mt-1 font-semibold">{{ $user->school?->name ?: 'Platform' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            <div class="app-card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="app-card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="app-card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
