<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-[#0B1F3A]">My Profile</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</p>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="app-card p-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8F4FE] text-2xl font-black text-[#0B1F3A]">
                    {{ Str::of($student->name)->substr(0, 1) }}
                </div>
                <div>
                    <h2 class="text-xl font-black text-[#0B1F3A]">{{ $student->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ Str::headline($student->status) }} student</p>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['Registration', $student->registration_number],
                    ['Roll Number', $student->roll_number],
                    ['Class', trim(($student->schoolClass?->name ?: '').' '.($student->section?->name ?: ''))],
                    ['Gender', $student->gender ? Str::headline($student->gender) : null],
                    ['Date of Birth', optional($student->date_of_birth)->format('d M Y')],
                    ['Admission Date', optional($student->admission_date)->format('d M Y')],
                    ['B-form/CNIC', $student->b_form],
                    ['Father', $student->father_name],
                    ['Mother', $student->mother_name],
                ] as [$label, $value])
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-sm text-gray-500">{{ $label }}</dt>
                        <dd class="mt-1 font-bold text-[#0B1F3A]">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg bg-gray-50 p-4">
                    <div class="text-sm text-gray-500">Address</div>
                    <div class="mt-1 font-bold text-[#0B1F3A]">{{ $student->address ?: '-' }}</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <div class="text-sm text-gray-500">Guardian Contact</div>
                    <div class="mt-1 font-bold text-[#0B1F3A]">{{ $student->guardian_name ?: '-' }}</div>
                    <div class="mt-1 text-sm text-gray-600">{{ $student->guardian_phone ?: '-' }} · {{ $student->guardian_email ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
