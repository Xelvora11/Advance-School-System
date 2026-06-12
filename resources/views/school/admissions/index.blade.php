<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="clipboard-plus" class="h-4 w-4"></i>
                    Admission Workflow
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">Admissions</h1>
                <p class="mt-1 text-sm text-gray-600">Inquiry, notes, document checklist, approval, and enrollment.</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[.85fr_1.15fr] lg:px-8">
        <div class="app-card p-5">
            <h2 class="font-bold text-[#0B1F3A]">Add Inquiry</h2>
            <form method="POST" action="{{ route('admissions.store') }}" class="mt-4 grid gap-3">
                @csrf
                <input name="student_name" placeholder="Student name" required>
                <div class="grid gap-3 sm:grid-cols-2">
                    <select name="gender">
                        <option value="">Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="date" name="date_of_birth">
                </div>
                <select name="requested_class_id" required>
                    <option value="">Requested class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <input name="guardian_name" placeholder="Guardian name" required>
                <input name="guardian_phone" placeholder="Guardian phone" required>
                <textarea name="address" rows="2" placeholder="Address"></textarea>
                <textarea name="test_notes" rows="3" placeholder="Test / interview notes"></textarea>
                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    @foreach (['document_b_form' => 'B-form', 'document_photos' => 'Photos', 'document_previous_result' => 'Previous result', 'document_guardian_cnic' => 'Guardian CNIC'] as $field => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2">
                            <input type="checkbox" name="{{ $field }}" value="1">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <button class="app-button app-button-primary">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Inquiry
                </button>
            </form>
        </div>

        <div class="space-y-5">
            <form class="app-card grid gap-3 p-4 sm:grid-cols-3">
                <input name="search" value="{{ request('search') }}" placeholder="Search admissions" class="sm:col-span-2">
                <select name="status">
                    <option value="">All status</option>
                    @foreach (['inquiry', 'pending', 'approved', 'rejected', 'enrolled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="app-button app-button-dark sm:col-span-3">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Filter
                </button>
            </form>

            <div class="app-card overflow-hidden">
                <table class="app-table min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Student</th>
                            <th class="text-left">Class</th>
                            <th class="text-left">Guardian</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($admissions as $admission)
                            <tr class="hover:bg-gray-50">
                                <td>
                                    <div class="font-bold text-gray-900">{{ $admission->student_name }}</div>
                                    <div class="text-gray-500">{{ optional($admission->date_of_birth)->format('d M Y') ?: 'DOB not set' }}</div>
                                </td>
                                <td>{{ $admission->requestedClass?->name ?: '-' }}</td>
                                <td>{{ $admission->guardian_name ?: '-' }}<div class="text-gray-500">{{ $admission->guardian_phone }}</div></td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($admission->status) {
                                        'approved', 'enrolled' => 'bg-green-100 text-green-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ ucfirst($admission->status) }}</span>
                                </td>
                                <td class="text-right">
                                    <div x-data="{ open: false, convertOpen: false }" class="relative inline-block">
                                        <button @click="open = !open" type="button" class="rounded-lg border border-gray-200 p-2 hover:bg-gray-50" aria-label="Actions">
                                            <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                        </button>
                                        <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-2 w-52 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-lg">
                                            <a href="{{ route('admissions.form', $admission) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"><i data-lucide="file-text" class="h-4 w-4"></i> Admission Form</a>
                                            @if (! in_array($admission->status, ['approved', 'enrolled'], true))
                                                <form method="POST" action="{{ route('admissions.approve', $admission) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="flex w-full items-center gap-2 px-3 py-2 text-sm text-green-700 hover:bg-green-50"><i data-lucide="check" class="h-4 w-4"></i> Approve</button>
                                                </form>
                                            @endif
                                            @if ($admission->status !== 'rejected')
                                                <form method="POST" action="{{ route('admissions.reject', $admission) }}" onsubmit="return confirm('Reject this admission?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-700 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i> Reject</button>
                                                </form>
                                            @endif
                                            @if ($admission->status === 'approved' && ! $admission->converted_student_id)
                                                <button type="button" @click="convertOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-[#0B1F3A] hover:bg-gray-50"><i data-lucide="user-plus" class="h-4 w-4"></i> Convert to Student</button>
                                            @endif
                                        </div>

                                        @if ($admission->status === 'approved' && ! $admission->converted_student_id)
                                            <div x-show="convertOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                                <form method="POST" action="{{ route('admissions.convert', $admission) }}" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                    @csrf
                                                    <h3 class="text-lg font-bold text-[#0B1F3A]">Convert admission to student</h3>
                                                    <p class="mt-1 text-sm text-gray-500">{{ $admission->student_name }} · {{ $admission->requestedClass?->name }}</p>
                                                    <select name="section_id" class="mt-4 rounded-md border-gray-300">
                                                        <option value="">Section optional</option>
                                                        @foreach ($sections as $section)
                                                            <option value="{{ $section->id }}">{{ $section->schoolClass?->name }} - {{ $section->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="roll_number" class="mt-3 rounded-md border-gray-300" placeholder="Roll number optional">
                                                    <div class="mt-4 flex justify-end gap-2">
                                                        <button type="button" @click="convertOpen = false" class="app-button app-button-light">Cancel</button>
                                                        <button class="app-button app-button-primary">Convert</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center text-gray-500">No admission inquiries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $admissions->links() }}</div>
        </div>
    </div>
</x-app-layout>
