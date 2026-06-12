<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="messages-square" class="h-4 w-4"></i>
                    Internal Messaging
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Messages</h1>
                <p class="mt-1 text-sm text-gray-600">School communication inside the portal.</p>
            </div>
            <a href="{{ route('messages.create') }}" class="app-button app-button-primary">
                <i data-lucide="square-pen" class="h-4 w-4"></i>
                New Message
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search by name or message" class="rounded-md border-gray-300">
                <select name="role" class="rounded-md border-gray-300">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ Str::headline($role) }}</option>
                    @endforeach
                </select>
                <button class="app-button app-button-dark">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Filter
                </button>
            </div>
        </form>

        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="divide-y divide-gray-100">
                @forelse ($threads as $thread)
                    @php
                        $otherParticipants = $thread->participants->filter(fn ($participant) => $participant->user_id !== auth()->id());
                        $last = $thread->latestMessage;
                        $unread = $unreadCounts[$thread->id] ?? 0;
                    @endphp
                    <a href="{{ route('messages.show', $thread) }}" class="block px-5 py-4 hover:bg-gray-50">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-bold text-[#0B1F3A]">
                                        {{ $otherParticipants->pluck('user.name')->filter()->join(', ') ?: 'Conversation' }}
                                    </div>
                                    @foreach ($otherParticipants->take(2) as $participant)
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-[#1DA1F2]">{{ Str::headline($participant->user?->role) }}</span>
                                    @endforeach
                                    @if ($unread > 0)
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700">{{ $unread }} unread</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-700">{{ $thread->subject ?: 'No subject' }}</div>
                                <div class="mt-1 max-w-3xl truncate text-sm text-gray-500">{{ $last?->body ?: 'No messages yet.' }}</div>
                            </div>
                            <div class="shrink-0 text-sm text-gray-500">{{ optional($last?->created_at ?: $thread->updated_at)->diffForHumans() }}</div>
                        </div>
                    </a>
                @empty
                    <div class="py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-[#1DA1F2]"><i data-lucide="messages-square" class="h-5 w-5"></i></div>
                        <div class="mt-3 font-bold text-[#0B1F3A]">No messages yet.</div>
                        <p class="mt-1 text-sm text-gray-500">Start a school conversation when you need to coordinate.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div>{{ $threads->links() }}</div>
    </div>
</x-app-layout>
