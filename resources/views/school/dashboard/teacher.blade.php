<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">Teacher Dashboard</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Assigned Classes</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($assignments as $assignment)
                    <div class="px-5 py-4">
                        <div class="font-medium">{{ $assignment->schoolClass?->name }} {{ $assignment->section?->name ? '- '.$assignment->section->name : '' }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ $assignment->subject?->name ?: 'Subject not assigned' }}</div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No classes assigned yet.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-[#0B1F3A]">Notices</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($notices as $notice)
                    <div class="px-5 py-4">
                        <div class="font-medium">{{ $notice->title }}</div>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $notice->body }}</p>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-gray-500">No notices for teachers.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
