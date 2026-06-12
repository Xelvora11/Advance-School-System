<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                    School Account
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Edit {{ $school->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">Update profile, status, and manual tracking fields.</p>
            </div>
            <a href="{{ route('super-admin.schools.show', $school) }}" class="app-button app-button-light">
                <i data-lucide="eye" class="h-4 w-4"></i>
                View School
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('super-admin.schools.update', $school) }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @method('PUT')
            @include('super-admin.schools.partials.form', ['school' => $school])

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('super-admin.schools.show', $school) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">Cancel</a>
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
            </div>
        </form>
    </div>
</x-app-layout>
