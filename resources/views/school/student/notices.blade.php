<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">My Notices</h1>
        <p class="mt-1 text-sm text-gray-600">Published notices visible to your account.</p>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
        @forelse ($notices as $notice)
            <div class="app-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-[#0B1F3A]">{{ $notice->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ optional($notice->published_at)->format('d M Y') ?: optional($notice->created_at)->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#1DA1F2]">{{ Str::headline($notice->audience_type) }}</span>
                </div>
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $notice->body }}</p>
                @if ($notice->attachment_path)
                    <a href="{{ Storage::url($notice->attachment_path) }}" target="_blank" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-[#1DA1F2]">
                        <i data-lucide="paperclip" class="h-4 w-4"></i>
                        View attachment
                    </a>
                @endif
            </div>
        @empty
            <div class="app-card px-5 py-12 text-center text-gray-500">No notices yet.</div>
        @endforelse

        <div>{{ $notices->links() }}</div>
    </div>
</x-app-layout>
