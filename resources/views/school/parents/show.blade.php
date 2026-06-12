<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    Parent Account
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $parent->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $parent->email ?: $parent->user?->email ?: 'No email' }} · {{ $parent->phone ?: 'No phone' }}</p>
            </div>
            <a href="{{ route('parents.index') }}" class="app-button app-button-light">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Parents
            </a>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_.8fr] lg:px-8">
        <div class="app-card p-6">
            <h2 class="font-bold text-[#0B1F3A]">Profile & Linked Students</h2>
            <form method="POST" action="{{ route('parents.update', $parent) }}" class="mt-5 grid gap-4">
                @csrf
                @method('PUT')
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium text-gray-700">Name
                        <input name="name" value="{{ old('name', $parent->name) }}" required class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="text-sm font-medium text-gray-700">Email
                        <input name="email" type="email" value="{{ old('email', $parent->email) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="text-sm font-medium text-gray-700">Phone
                        <input name="phone" value="{{ old('phone', $parent->phone) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="text-sm font-medium text-gray-700">WhatsApp
                        <input name="whatsapp" value="{{ old('whatsapp', $parent->whatsapp) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="text-sm font-medium text-gray-700">CNIC
                        <input name="cnic" value="{{ old('cnic', $parent->cnic) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="text-sm font-medium text-gray-700">Status
                        <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="active" @selected(old('status', $parent->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $parent->status) === 'inactive')>Inactive</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700 md:col-span-2">Address
                        <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('address', $parent->address) }}</textarea>
                    </label>
                    <label class="text-sm font-medium text-gray-700">Relationship for selected students
                        <select name="relationship_type" class="mt-1 w-full rounded-md border-gray-300">
                            @php($currentRelationship = $parent->students->first()?->pivot?->relationship_type ?: 'guardian')
                            @foreach (['father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Guardian', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('relationship_type', $currentRelationship) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700 md:col-span-2">Linked students
                        <select name="student_ids[]" multiple size="8" class="mt-1 w-full rounded-md border-gray-300">
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(in_array($student->id, old('student_ids', $parent->students->pluck('id')->all()), true))>
                                    {{ $student->name }} - {{ $student->registration_number }} - {{ $student->schoolClass?->name }} {{ $student->section?->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="mt-1 block text-xs text-gray-500">Hold Ctrl or Command to select multiple students.</span>
                    </label>
                </div>
                <div class="flex justify-end">
                    <button class="app-button app-button-primary">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Save Parent
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="app-card p-6">
                <h2 class="font-bold text-[#0B1F3A]">Portal Login</h2>
                <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm">
                    <div class="font-bold text-[#0B1F3A]">{{ $parent->user ? 'Login account ready' : 'No login account yet' }}</div>
                    <div class="mt-1 text-gray-500">
                        {{ $parent->user?->email ?: 'Create a login when this parent should access the portal.' }}
                    </div>
                    @if ($parent->user)
                        <div class="mt-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $parent->user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">{{ $parent->user->is_active ? 'Active' : 'Disabled' }}</span>
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('parents.login', $parent) }}" class="mt-4 grid gap-3">
                    @csrf
                    @method('PATCH')
                    <input name="password" type="password" minlength="8" placeholder="New password optional" class="rounded-md border-gray-300">
                    <button class="app-button app-button-primary justify-center">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                        {{ $parent->user ? 'Reset Login' : 'Create Login' }}
                    </button>
                </form>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <form method="POST" action="{{ route('parents.toggle', $parent) }}">
                        @csrf
                        @method('PATCH')
                        <button class="w-full justify-center app-button app-button-light">
                            <i data-lucide="{{ $parent->status === 'active' ? 'lock' : 'unlock' }}" class="h-4 w-4"></i>
                            {{ $parent->status === 'active' ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                    @if ($parent->user)
                        <form method="POST" action="{{ route('parents.message', $parent) }}">
                            @csrf
                            <button class="w-full justify-center app-button app-button-light">
                                <i data-lucide="message-square" class="h-4 w-4"></i>
                                Message
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="app-card p-6">
                <h2 class="font-bold text-[#0B1F3A]">Linked Children</h2>
                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($parent->students as $student)
                        <div class="flex items-center justify-between gap-3 py-3 text-sm">
                            <div>
                                <div class="font-bold text-[#0B1F3A]">{{ $student->name }}</div>
                                <div class="text-gray-500">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</div>
                            </div>
                            <a href="{{ route('students.show', $student) }}#access" class="font-bold text-[#1DA1F2]">Open</a>
                        </div>
                    @empty
                        <div class="py-6 text-sm text-gray-500">No children linked to this parent.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
