@php($editing = filled($teacher))
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">{{ $editing ? 'Edit Teacher' : 'Add Teacher' }}</h1>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $editing ? route('teachers.update', $teacher) : route('teachers.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="grid gap-4 md:grid-cols-3">
                <label class="block text-sm font-medium md:col-span-2">Teacher name
                    <input name="name" value="{{ old('name', $teacher?->name) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                </label>
                <label class="block text-sm font-medium">Status
                    <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach (['active', 'inactive'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $teacher?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Email
                    <input type="email" name="email" value="{{ old('email', $teacher?->email) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Phone
                    <input name="phone" value="{{ old('phone', $teacher?->phone) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">CNIC
                    <input name="cnic" value="{{ old('cnic', $teacher?->cnic) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Qualification
                    <input name="qualification" value="{{ old('qualification', $teacher?->qualification) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Joining date
                    <input type="date" name="joining_date" value="{{ old('joining_date', optional($teacher?->joining_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium">Basic salary
                    <input type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', $teacher?->basic_salary) }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="Optional">
                </label>
                <label class="block text-sm font-medium">Salary payment method
                    <select name="salary_payment_method" class="mt-1 w-full rounded-md border-gray-300">
                        <option value="">Select method</option>
                        @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('salary_payment_method', $teacher?->salary_payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Login password
                    <input name="login_password" placeholder="For new login" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-medium md:col-span-3">Bank/account note
                    <textarea name="bank_account_note" rows="2" class="mt-1 w-full rounded-md border-gray-300" placeholder="Manual salary payment account details">{{ old('bank_account_note', $teacher?->bank_account_note) }}</textarea>
                </label>
            </div>

            @unless ($editing)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="create_login" value="1" class="rounded border-gray-300 text-[#1DA1F2]">
                    Create teacher login
                </label>
            @endunless

            <div id="assignment" class="scroll-mt-24 border-t border-gray-200 pt-6">
                <h2 class="font-semibold text-[#0B1F3A]">Add Assignment</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <select name="school_class_id" class="rounded-md border-gray-300">
                        <option value="">Class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <select name="section_id" class="rounded-md border-gray-300">
                        <option value="">Section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                    <select name="subject_id" class="rounded-md border-gray-300">
                        <option value="">Subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('teachers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">Cancel</a>
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">Save Teacher</button>
            </div>
        </form>
    </div>
</x-app-layout>
