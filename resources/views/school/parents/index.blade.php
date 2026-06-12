<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    Parent Management
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Parents</h1>
                <p class="mt-1 text-sm text-gray-600">Create school-controlled parent accounts and link children.</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[.45fr_1fr] lg:px-8">
        <form method="POST" action="{{ route('parents.store') }}" class="app-card grid gap-3 p-5">
            @csrf
            <h2 class="font-bold text-[#0B1F3A]">Add Parent</h2>
            <input name="name" value="{{ old('name') }}" placeholder="Parent / guardian name" required>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email for login">
            <input name="phone" value="{{ old('phone') }}" placeholder="Phone">
            <input name="whatsapp" value="{{ old('whatsapp') }}" placeholder="WhatsApp number">
            <input name="cnic" value="{{ old('cnic') }}" placeholder="CNIC optional">
            <textarea name="address" rows="2" class="rounded-md border-gray-300" placeholder="Address optional">{{ old('address') }}</textarea>
            <select name="relationship_type">
                @foreach (['father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Guardian', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="student_ids[]" multiple size="6">
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} · {{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                <input type="checkbox" name="create_login" value="1" checked>
                Create login now
            </label>
            <input name="password" placeholder="Temporary password optional">
            <button class="app-button app-button-primary">
                <i data-lucide="user-plus" class="h-4 w-4"></i>
                Save Parent
            </button>
        </form>

        <div class="space-y-4">
            <form class="app-card grid gap-3 p-4 md:grid-cols-[1fr_auto_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search parent, phone, email, or student">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['active', 'inactive'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="app-button app-button-dark">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Filter
                </button>
            </form>

            <div class="app-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[980px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Parent</th>
                                <th class="text-left">Phone / WhatsApp</th>
                                <th class="text-left">Linked Students</th>
                                <th class="text-left">Login</th>
                                <th class="text-left">Last Login</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($parents as $parent)
                                <tr>
                                    <td>
                                        <div class="font-bold text-[#0B1F3A]">{{ $parent->name }}</div>
                                        <div class="text-gray-500">{{ $parent->email ?: 'No email' }}</div>
                                    </td>
                                    <td>{{ $parent->phone ?: '-' }}<div class="text-gray-500">{{ $parent->whatsapp ?: '-' }}</div></td>
                                    <td>
                                        @forelse ($parent->students as $student)
                                            <div>{{ $student->name }} <span class="text-gray-500">{{ $student->schoolClass?->name }} {{ $student->section?->name }}</span></div>
                                        @empty
                                            <span class="text-gray-500">No linked students</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $parent->user ? 'Enabled' : 'Not created' }}</td>
                                    <td>{{ optional($parent->user?->last_login_at)->format('d M Y h:i A') ?: '-' }}</td>
                                    <td>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $parent->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($parent->status) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('parents.show', $parent) }}" class="app-button app-button-light">View Parent</a>
                                            @if ($parent->user_id)
                                                <form method="POST" action="{{ route('parents.message', $parent) }}">
                                                    @csrf
                                                    <button class="app-button app-button-light">Send Message</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-10 text-center text-gray-500">No parents found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-5 py-4">{{ $parents->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
