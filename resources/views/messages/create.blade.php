<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#0B1F3A]">New Message</h1>
                <p class="mt-1 text-sm text-gray-600">Send a school portal message to an allowed recipient.</p>
            </div>
            <a href="{{ route('messages.index') }}" class="app-button app-button-light">
                <i data-lucide="inbox" class="h-4 w-4"></i>
                Inbox
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <form method="GET" class="mb-5 grid gap-3 sm:grid-cols-[1fr_auto]">
                <select name="role" class="rounded-md border-gray-300">
                    <option value="">All recipient roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ Str::headline($role) }}</option>
                    @endforeach
                </select>
                <button class="app-button app-button-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>
            </form>

            <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <label class="block text-sm font-medium">Recipient
                    <select name="recipient_id" class="mt-1 w-full rounded-md border-gray-300" required>
                        <option value="">Select recipient</option>
                        @foreach ($recipients as $recipient)
                            <option value="{{ $recipient->id }}" @selected(old('recipient_id') == $recipient->id)>
                                {{ $recipient->name }} - {{ Str::headline($recipient->role) }} - {{ $recipient->email }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm font-medium">Subject
                    <input name="subject" value="{{ old('subject') }}" placeholder="Optional" class="mt-1 w-full rounded-md border-gray-300">
                </label>

                <label class="block text-sm font-medium">Message
                    <textarea name="body" rows="6" class="mt-1 w-full rounded-md border-gray-300" required placeholder="Write your message">{{ old('body') }}</textarea>
                </label>

                @if ($settings->allow_attachments_in_messages)
                    <label class="block text-sm font-medium">Attachment
                        <input type="file" name="attachment" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                        <span class="mt-1 block text-xs text-gray-500">Allowed: PDF, JPG, PNG, DOC, DOCX. Max {{ $settings->message_attachment_max_size }} KB.</span>
                    </label>
                @endif

                @if ($recipients->isEmpty())
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">No allowed recipients are available for your role right now.</div>
                @endif

                <div class="flex justify-end gap-3">
                    <a href="{{ route('messages.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">Cancel</a>
                    <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white" @disabled($recipients->isEmpty())>Send Message</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
