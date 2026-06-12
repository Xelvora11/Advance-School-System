<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="messages-square" class="h-4 w-4"></i>
                    Conversation
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $thread->subject ?: 'No subject' }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $thread->participants->pluck('user.name')->filter()->join(', ') }}
                </p>
            </div>
            <a href="{{ route('messages.index') }}" class="app-button app-button-light">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Inbox
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <div class="space-y-4">
                @forelse ($messages as $message)
                    @php($own = $message->sender_id === auth()->id())
                    <div class="flex {{ $own ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[82%] rounded-lg border px-4 py-3 {{ $own ? 'border-blue-100 bg-blue-50' : 'border-gray-200 bg-gray-50' }}">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span class="font-bold text-gray-700">{{ $message->sender?->name ?: 'Deleted user' }}</span>
                                <span>{{ $message->sender ? Str::headline($message->sender->role) : 'Unknown' }}</span>
                                <span>{{ $message->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="mt-2 whitespace-pre-wrap text-sm text-gray-900">{{ $message->body }}</div>
                            @if ($message->attachment_path)
                                <a href="{{ asset('storage/'.$message->attachment_path) }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-[#0B1F3A]">
                                    <i data-lucide="paperclip" class="h-4 w-4"></i>
                                    {{ $message->attachment_original_name ?: 'Attachment' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-500">No messages yet.</div>
                @endforelse
            </div>
        </div>

        <form method="POST" action="{{ route('messages.reply', $thread) }}" enctype="multipart/form-data" class="rounded-lg border border-gray-200 bg-white p-5">
            @csrf
            <label class="block text-sm font-medium">Reply
                <textarea name="body" rows="4" class="mt-1 w-full rounded-md border-gray-300" required placeholder="Write a reply"></textarea>
            </label>
            @if ($settings->allow_attachments_in_messages)
                <label class="mt-4 block text-sm font-medium">Attachment
                    <input type="file" name="attachment" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            @endif
            <div class="mt-4 flex justify-end">
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Send Reply</button>
            </div>
        </form>
    </div>
</x-app-layout>
