@php($editing = filled($student))
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">{{ $editing ? 'Edit Student' : 'Add Student' }}</h1>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $editing ? route('students.update', $student) : route('students.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div>
                <h2 class="text-lg font-semibold text-[#0B1F3A]">Basic Information</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <label class="block text-sm font-medium">Registration number
                        <input name="registration_number" value="{{ old('registration_number', $student?->registration_number) }}" placeholder="Auto if blank" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium md:col-span-2">Student name
                        <input name="name" value="{{ old('name', $student?->name) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                    </label>
                    <label class="block text-sm font-medium">B-form/CNIC
                        <input name="b_form" value="{{ old('b_form', $student?->b_form) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Date of birth
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($student?->date_of_birth)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Gender
                        <select name="gender" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="">Select</option>
                            @foreach (['male', 'female', 'other'] as $gender)
                                <option value="{{ $gender }}" @selected(old('gender', $student?->gender) === $gender)>{{ ucfirst($gender) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Class
                        <select name="school_class_id" class="mt-1 w-full rounded-md border-gray-300" required>
                            <option value="">Select class</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id', $student?->school_class_id) == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Section
                        <select name="section_id" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="">Select section</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(old('section_id', $student?->section_id) == $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Roll number
                        <input name="roll_number" value="{{ old('roll_number', $student?->roll_number) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Admission date
                        <input type="date" name="admission_date" value="{{ old('admission_date', optional($student?->admission_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Previous school
                        <input name="previous_school" value="{{ old('previous_school', $student?->previous_school) }}" class="mt-1 w-full rounded-md border-gray-300">
                    </label>
                    <label class="block text-sm font-medium">Status
                        <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                            @foreach (['active', 'inactive', 'left', 'transferred', 'graduated'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $student?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Photo
                        <input type="file" name="photo" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    </label>
                </div>
                <label class="mt-4 block text-sm font-medium">Address
                    <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('address', $student?->address) }}</textarea>
                </label>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-[#0B1F3A]">Guardian Information</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach (['father_name' => 'Father name', 'mother_name' => 'Mother name', 'guardian_name' => 'Guardian name', 'guardian_cnic' => 'Guardian CNIC', 'guardian_phone' => 'Phone', 'guardian_whatsapp' => 'WhatsApp', 'guardian_email' => 'Email'] as $field => $label)
                        <label class="block text-sm font-medium">{{ $label }}
                            <input name="{{ $field }}" value="{{ old($field, $student?->{$field}) }}" class="mt-1 w-full rounded-md border-gray-300">
                        </label>
                    @endforeach
                    <label class="block text-sm font-medium">Parent login password
                        <input type="password" name="parent_password" placeholder="Required for a new parent login" class="mt-1 w-full rounded-md border-gray-300" autocomplete="new-password">
                    </label>
                </div>
                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input type="checkbox" name="create_parent_login" value="1" class="rounded border-gray-300 text-[#1DA1F2]" @checked(old('create_parent_login'))>
                    Create or link parent login using guardian email
                </label>
                <label class="mt-4 block text-sm font-medium">Guardian address
                    <textarea name="guardian_address" rows="2" class="mt-1 w-full rounded-md border-gray-300">{{ old('guardian_address', $student?->guardian_address) }}</textarea>
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('students.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">Cancel</a>
                <button class="rounded-md bg-[#1DA1F2] px-4 py-2 text-sm font-semibold text-white">{{ $editing ? 'Save Student' : 'Add Student' }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
