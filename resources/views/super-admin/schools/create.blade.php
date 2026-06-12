<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                <i data-lucide="circle-plus" class="h-4 w-4"></i>
                Manual Onboarding
            </div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Create School Account</h1>
            <p class="mt-1 text-sm text-gray-600">Create the school profile and first School Admin account.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('super-admin.schools.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6" x-data="{ password: @js(old('admin_password', 'password123')) }">
            @csrf
            @include('super-admin.schools.partials.form', ['school' => null])

            <section class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-[#0B1F3A]">First Admin Account</h2>
                <p class="mt-1 text-sm text-gray-500">This user manages the school after onboarding.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium">Admin name
                        <input name="admin_name" value="{{ old('admin_name') }}" class="mt-1 w-full rounded-md border-gray-300" required>
                    </label>
                    <label class="block text-sm font-medium">Admin email
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" class="mt-1 w-full rounded-md border-gray-300" required>
                    </label>
                    <label class="block text-sm font-medium">Admin phone
                        <input name="admin_phone" value="{{ old('admin_phone') }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Initial password
                        <div class="mt-1 flex gap-2">
                            <input type="text" name="admin_password" x-model="password" class="min-w-0 flex-1 rounded-md border-gray-300" required>
                            <button type="button" class="app-button app-button-light shrink-0" @click="password = Math.random().toString(36).slice(-6) + Math.random().toString(36).slice(-6) + 'A1'">Generate</button>
                        </div>
                    </label>
                </div>
            </section>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('super-admin.schools.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">Cancel</a>
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Create School</button>
            </div>
        </form>
    </div>
</x-app-layout>
