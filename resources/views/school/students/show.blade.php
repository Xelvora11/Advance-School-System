<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                    Student Profile
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $student->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $student->registration_number }} · {{ $student->schoolClass?->name }} {{ $student->section?->name }}</p>
            </div>
            <a href="{{ route('students.edit', $student) }}" class="app-button app-button-primary">
                <i data-lucide="pencil" class="h-4 w-4"></i>
                Edit Student
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="app-card flex flex-wrap gap-2 p-3">
            @foreach ([['Basic Info', 'basic-info'], ['Guardian Info', 'guardian-info'], ['Portal Access', 'access'], ['Attendance', 'attendance'], ['Fees', 'fees'], ['Results', 'results'], ['Documents', 'documents']] as [$label, $target])
                <a href="#{{ $target }}" class="rounded-md px-3 py-2 text-sm font-bold text-gray-700 hover:bg-blue-50 hover:text-[#0B1F3A]">{{ $label }}</a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        <div id="basic-info" class="app-card scroll-mt-24 p-6">
            <h2 class="font-bold text-[#0B1F3A]">Basic Info</h2>
            <dl class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ([['Status', ucfirst($student->status)], ['Roll number', $student->roll_number], ['Gender', ucfirst((string) $student->gender)], ['DOB', optional($student->date_of_birth)->format('d M Y')], ['Admission', optional($student->admission_date)->format('d M Y')], ['B-form/CNIC', $student->b_form]] as [$label, $value])
                    <div class="rounded-lg bg-gray-50 p-3">
                        <dt class="text-sm text-gray-500">{{ $label }}</dt>
                        <dd class="mt-1 font-bold">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-5 rounded-lg bg-gray-50 p-3">
                <dt class="text-sm text-gray-500">Address</dt>
                <dd class="mt-1">{{ $student->address ?: '-' }}</dd>
            </div>
        </div>

        <div id="guardian-info" class="app-card scroll-mt-24 p-6">
            <h2 class="font-bold text-[#0B1F3A]">Guardian Info</h2>
            <dl class="mt-4 space-y-3 text-sm">
                @foreach ([['Father', $student->father_name], ['Mother', $student->mother_name], ['Guardian', $student->guardian_name], ['Phone', $student->guardian_phone], ['WhatsApp', $student->guardian_whatsapp], ['Email', $student->guardian_email]] as [$label, $value])
                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="font-bold text-right">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div id="access" class="app-card scroll-mt-24 p-6 lg:col-span-2">
            @php
                $linkedParents = $student->guardians;
                $studentLoginEmail = old('email', $student->user?->email ?: Str::slug($student->registration_number ?: $student->name).'-student@school.local');
            @endphp
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Portal Access</h2>
                    <p class="mt-1 text-sm text-gray-500">Create and manage controlled parent and student logins. Public signup stays disabled.</p>
                </div>
                <a href="{{ route('parents.index') }}" class="app-button app-button-light">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    Parent Management
                </a>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-bold text-[#0B1F3A]">Parent Accounts</h3>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#1DA1F2]">{{ $linkedParents->count() }} linked</span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($linkedParents as $parent)
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-[#0B1F3A]">{{ $parent->name }}</div>
                                        <div class="mt-1 text-sm text-gray-500">{{ $parent->email ?: $parent->user?->email ?: 'No email' }} · {{ $parent->phone ?: 'No phone' }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $parent->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">{{ ucfirst($parent->status) }}</span>
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $parent->user?->is_active ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">{{ $parent->user ? ($parent->user->is_active ? 'Login active' : 'Login disabled') : 'No login' }}</span>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-gray-600">{{ Str::headline($parent->pivot->relationship_type ?: 'guardian') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('students.parent-login', $student) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $parent->id }}">
                                        <input type="hidden" name="relationship_type" value="{{ $parent->pivot->relationship_type ?: 'guardian' }}">
                                        <input name="password" type="password" minlength="8" placeholder="New password optional" class="w-48 rounded-md border-gray-300 text-sm">
                                        <button class="inline-flex items-center gap-1 rounded-lg border border-[#1DA1F2] px-3 py-2 text-xs font-bold text-[#0B1F3A] hover:bg-blue-50">
                                            <i data-lucide="key-round" class="h-4 w-4"></i>
                                            {{ $parent->user ? 'Reset Login' : 'Create Login' }}
                                        </button>
                                    </form>
                                    @if ($parent->user)
                                        <form method="POST" action="{{ route('parents.message', $parent) }}">
                                            @csrf
                                            <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-[#0B1F3A] hover:bg-gray-50">
                                                <i data-lucide="message-square" class="h-4 w-4"></i>
                                                Message
                                            </button>
                                        </form>
                                        @if ($parent->user->is_active)
                                            <form method="POST" action="{{ route('students.parent-access.disable', [$student, $parent]) }}" onsubmit="return confirm('Disable this parent login?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
                                                    <i data-lucide="lock" class="h-4 w-4"></i>
                                                    Disable
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-sm text-gray-500">No parent account is linked yet.</div>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('students.parent-login', $student) }}" class="mt-4 grid gap-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                        @csrf
                        <div class="font-bold text-sm text-[#0B1F3A]">Create parent login from guardian info</div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <select name="relationship_type" class="rounded-md border-gray-300 text-sm">
                                @foreach (['father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Guardian', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('relationship_type', 'guardian') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="password" type="password" minlength="8" placeholder="Password optional" class="rounded-md border-gray-300 text-sm">
                        </div>
                        <button class="app-button app-button-primary justify-center">
                            <i data-lucide="user-plus" class="h-4 w-4"></i>
                            Create Parent Login
                        </button>
                    </form>

                    <form method="POST" action="{{ route('students.link-parent', $student) }}" class="mt-3 grid gap-3 rounded-lg border border-gray-100 bg-white p-3">
                        @csrf
                        <div class="font-bold text-sm text-[#0B1F3A]">Link existing parent</div>
                        <select name="parent_id" class="rounded-md border-gray-300 text-sm" required>
                            <option value="">Select parent</option>
                            @foreach ($parentOptions as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }} - {{ $parent->email ?: $parent->phone ?: 'No contact' }}</option>
                            @endforeach
                        </select>
                        <select name="relationship_type" class="rounded-md border-gray-300 text-sm">
                            @foreach (['father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Guardian', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="app-button app-button-light justify-center">
                            <i data-lucide="link" class="h-4 w-4"></i>
                            Link Parent
                        </button>
                    </form>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-bold text-[#0B1F3A]">Student Account</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $student->user?->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $student->user ? ($student->user->is_active ? 'Login active' : 'Login disabled') : 'No login' }}</span>
                    </div>

                    @if ($student->user)
                        <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
                            <div class="font-bold text-[#0B1F3A]">{{ $student->user->name }}</div>
                            <div class="mt-1 text-gray-500">{{ $student->user->email }} · {{ $student->user->last_login_at?->diffForHumans() ?: 'Never logged in' }}</div>
                        </div>
                        <form method="POST" action="{{ route('students.student-password', $student) }}" class="mt-4 grid gap-3">
                            @csrf
                            @method('PATCH')
                            <input name="password" type="password" minlength="8" placeholder="New password optional" class="rounded-md border-gray-300">
                            <button class="app-button app-button-primary justify-center">
                                <i data-lucide="key-round" class="h-4 w-4"></i>
                                Reset Student Password
                            </button>
                        </form>
                        @if ($student->user->is_active)
                            <form method="POST" action="{{ route('students.student-access.disable', $student) }}" class="mt-3" onsubmit="return confirm('Disable this student login?')">
                                @csrf
                                @method('PATCH')
                                <button class="w-full justify-center app-button border border-red-200 bg-white text-red-700 hover:bg-red-50">
                                    <i data-lucide="lock" class="h-4 w-4"></i>
                                    Disable Student Login
                                </button>
                            </form>
                        @endif
                    @else
                        <form method="POST" action="{{ route('students.student-login', $student) }}" class="mt-4 grid gap-3">
                            @csrf
                            <label class="text-sm font-medium text-gray-700">
                                Student email
                                <input name="email" type="email" value="{{ $studentLoginEmail }}" required class="mt-1 w-full rounded-md border-gray-300">
                            </label>
                            <label class="text-sm font-medium text-gray-700">
                                Password
                                <input name="password" type="password" minlength="8" placeholder="Password optional" class="mt-1 w-full rounded-md border-gray-300">
                            </label>
                            <button class="app-button app-button-primary justify-center">
                                <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                                Create Student Login
                            </button>
                        </form>
                    @endif

                    <div class="mt-4 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Share temporary passwords directly with the verified parent or student. They can change passwords from their profile after login.
                    </div>
                </div>
            </div>
        </div>

        <div id="attendance" class="app-card scroll-mt-24 p-6">
            <h2 class="font-bold text-[#0B1F3A]">Attendance</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->attendance->sortByDesc('attendance_date')->take(10) as $record)
                            <tr>
                                <td class="py-3 font-semibold">{{ $record->attendance_date->format('d M Y') }}</td>
                                <td class="py-3">{{ $record->schoolClass?->name }} {{ $record->section?->name }}</td>
                                <td class="py-3 text-right">{{ ucfirst($record->status) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-6 text-gray-500">No attendance records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="documents" class="app-card scroll-mt-24 p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-[#0B1F3A]">Documents</h2>
                <i data-lucide="paperclip" class="h-5 w-5 text-[#1DA1F2]"></i>
            </div>
            <form method="POST" action="{{ route('students.documents.store', $student) }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
                @csrf
                <input name="title" placeholder="Document title" required>
                <input type="file" name="document" required>
                <button class="app-button app-button-dark">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Upload Document
                </button>
            </form>
            <div class="mt-5 divide-y divide-gray-100">
                @forelse ($student->documents as $document)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="font-bold text-[#1DA1F2]">{{ $document->title }}</a>
                        <form method="POST" action="{{ route('students.documents.destroy', $document) }}" onsubmit="return confirm('Delete this document?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-lg border border-red-200 p-2 text-red-700 hover:bg-red-50" aria-label="Delete document">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="py-6 text-sm text-gray-500">No documents uploaded.</div>
                @endforelse
            </div>
        </div>

        <div id="fees" x-data="{ fineOpen: false }" class="app-card scroll-mt-24 p-6 lg:col-span-2">
            @php($profileOpenFees = $student->fees->filter(fn ($fee) => in_array($fee->status, ['unpaid', 'partial', 'overdue'], true) && $fee->balance() > 0)->values())
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#0B1F3A]">Fees Ledger</h2>
                    <p class="mt-1 text-sm text-gray-500">Assigned fees, payments, discounts, fines, and current balance.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @include('school.fees.partials.student-payment-modal', ['student' => $student, 'openFees' => $profileOpenFees, 'buttonClass' => 'app-button app-button-primary'])
                    <button type="button" @click="fineOpen = true" class="app-button app-button-primary">
                        <i data-lucide="circle-plus" class="h-4 w-4"></i>
                        Add Fine
                    </button>
                    @include('school.fees.partials.student-discount-modal', ['student' => $student, 'openFees' => $profileOpenFees, 'discountTypes' => $discountTypes, 'buttonClass' => 'app-button app-button-light'])
                    <a href="{{ route('fees.students.account', $student) }}" class="app-button app-button-light">
                        <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                        View Full Fee Account
                    </a>
                    <a href="{{ route('fees.index', ['class_id' => $student->school_class_id]) }}" class="app-button app-button-light">
                        <i data-lucide="receipt" class="h-4 w-4"></i>
                        Open Finance
                    </a>
                </div>
            </div>

            <div x-show="fineOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <form method="POST" action="{{ route('students.fines.store', $student) }}" class="w-full max-w-lg rounded-lg bg-white p-5 shadow-xl">
                    @csrf
                    <h3 class="text-lg font-bold text-[#0B1F3A]">Add Student Fine</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <select name="fine_type" required>
                            @foreach ($fineTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="Amount">
                        <input name="title" required placeholder="Fine reason/title">
                        <input type="date" name="fine_date" value="{{ now()->format('Y-m-d') }}" required>
                        <input type="date" name="due_date" placeholder="Due date">
                        <select name="student_fee_id">
                            <option value="">Not linked to fee</option>
                            @foreach ($student->fees as $fee)
                                <option value="{{ $fee->id }}">{{ $fee->feeHead?->name ?: 'Fee' }} - {{ $fee->month }}/{{ $fee->year }}</option>
                            @endforeach
                        </select>
                        <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300" placeholder="Note optional"></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" @click="fineOpen = false" class="app-button app-button-light">Cancel</button>
                        <button class="app-button app-button-primary">Save Fine</button>
                    </div>
                </form>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                @foreach ([
                    ['Assigned', $feeSummary['assigned'], 'receipt'],
                    ['Paid', $feeSummary['paid'], 'badge-dollar-sign'],
                    ['Balance', $feeSummary['balance'], 'wallet'],
                    ['Unpaid Fines', $feeSummary['unpaidFines'], 'circle-alert'],
                    ['Outstanding', $feeSummary['outstanding'], 'receipt'],
                    ['Open Records', $feeSummary['open'], 'circle-alert'],
                ] as [$label, $value, $icon])
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-center justify-between gap-3 text-sm text-gray-500">
                            <span>{{ $label }}</span>
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-[#1DA1F2]"></i>
                        </div>
                        <div class="mt-2 text-xl font-black text-[#0B1F3A]">
                            {{ is_numeric($value) && $label !== 'Open Records' ? 'PKR '.number_format($value, 2) : number_format($value) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ([
                    ['Discounts', $feeSummary['discount'], 'badge-percent'],
                    ['Fines', $feeSummary['fine'], 'circle-plus'],
                    ['Arrears', $feeSummary['arrears'], 'history'],
                ] as [$label, $value, $icon])
                    <div class="rounded-lg border border-gray-100 bg-white p-3 text-sm">
                        <div class="flex items-center justify-between text-gray-500">
                            <span>{{ $label }}</span>
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <div class="mt-1 font-bold text-[#0B1F3A]">PKR {{ number_format($value, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="app-table min-w-[1180px] divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Fee</th>
                            <th class="text-left">Month</th>
                            <th class="text-right">Total Payable</th>
                            <th class="text-right">Previous Balance</th>
                            <th class="text-right">Fine</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-left">Due</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->fees as $fee)
                            <tr>
                                <td class="py-3 font-semibold">{{ $fee->feeHead?->name ?: 'Fee' }}</td>
                                <td class="py-3">{{ $fee->month }}/{{ $fee->year }}</td>
                                <td class="py-3 text-right">PKR {{ number_format($fee->payableAmount(), 2) }}</td>
                                <td class="py-3 text-right">PKR {{ number_format((float) $fee->arrears, 2) }}</td>
                                <td class="py-3 text-right">PKR {{ number_format((float) $fee->fine, 2) }}</td>
                                <td class="py-3 text-right">PKR {{ number_format((float) $fee->discount, 2) }}</td>
                                <td class="py-3 text-right">PKR {{ number_format((float) $fee->paid_amount, 2) }}</td>
                                <td class="py-3 text-right font-bold">PKR {{ number_format($fee->balance(), 2) }}</td>
                                <td class="py-3">{{ optional($fee->due_date)->format('d M Y') ?: '-' }}</td>
                                <td class="py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($fee->status) {
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        'overdue' => 'bg-red-100 text-red-700',
                                        'carried_forward' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    } }}">{{ $fee->status === 'carried_forward' ? 'Carried Forward' : ucfirst($fee->status) }}</span>
                                </td>
                                <td class="py-3 text-right">
                                    @include('school.fees.partials.actions', ['fee' => $fee, 'fineTypes' => $fineTypes, 'discountTypes' => $discountTypes])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="py-8 text-center text-gray-500">No fee records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="font-bold text-[#0B1F3A]">Fine Records</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[980px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Date</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Reason</th>
                                <th class="text-right">Amount</th>
                                <th class="text-left">Due</th>
                                <th class="text-left">Linked Fee</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($student->fines as $fine)
                                <tr>
                                    <td>{{ optional($fine->fine_date)->format('d M Y') }}</td>
                                    <td>{{ $fineTypes[$fine->fine_type] ?? Str::headline($fine->fine_type) }}</td>
                                    <td>
                                        <div class="font-semibold">{{ $fine->title }}</div>
                                        <div class="text-gray-500">{{ $fine->note ?: 'No note' }}</div>
                                    </td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $fine->amount, 2) }}</td>
                                    <td>{{ optional($fine->due_date)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ $fine->studentFee?->feeHead?->name ? $fine->studentFee->feeHead->name.' '.$fine->studentFee->month.'/'.$fine->studentFee->year : '-' }}</td>
                                    <td>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ match($fine->status) {
                                            'paid' => 'bg-green-100 text-green-700',
                                            'waived' => 'bg-gray-100 text-gray-600',
                                            default => 'bg-amber-100 text-amber-700',
                                        } }}">{{ ucfirst($fine->status) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div
                                            x-data="{
                                                open: false,
                                                viewOpen: false,
                                                editOpen: false,
                                                confirmOpen: false,
                                                menuStyle: '',
                                                confirmAction: '',
                                                confirmMethod: 'PATCH',
                                                confirmTitle: '',
                                                confirmText: '',
                                                confirmButton: 'Confirm',
                                                confirmClass: 'app-button app-button-dark',
                                                toggle($refs) {
                                                    if (this.open) {
                                                        this.open = false;
                                                        return;
                                                    }

                                                    const rect = $refs.button.getBoundingClientRect();
                                                    const width = 224;
                                                    const height = 240;
                                                    const gap = 8;
                                                    const top = rect.bottom + height + gap > window.innerHeight
                                                        ? Math.max(gap, rect.top - height - gap)
                                                        : rect.bottom + gap;
                                                    const left = Math.min(window.innerWidth - width - gap, Math.max(gap, rect.right - width));

                                                    this.menuStyle = `position: fixed; top: ${top}px; left: ${left}px; width: ${width}px;`;
                                                    this.open = true;
                                                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                                                },
                                                openConfirm(action, method, title, text, button, buttonClass) {
                                                    this.confirmAction = action;
                                                    this.confirmMethod = method;
                                                    this.confirmTitle = title;
                                                    this.confirmText = text;
                                                    this.confirmButton = button;
                                                    this.confirmClass = buttonClass;
                                                    this.open = false;
                                                    this.confirmOpen = true;
                                                },
                                                submitConfirm() {
                                                    this.$refs.confirmForm.submit();
                                                }
                                            }"
                                            @keydown.escape.window="open = false; viewOpen = false; editOpen = false; confirmOpen = false"
                                            @scroll.window="open = false"
                                            @resize.window="open = false"
                                            class="inline-block text-left"
                                        >
                                            <button x-ref="button" type="button" @click="toggle($refs)" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-[#0B1F3A] shadow-sm hover:border-[#1DA1F2] hover:bg-[#F3FAFF]">
                                                Actions
                                                <i data-lucide="chevron-down" class="h-4 w-4"></i>
                                            </button>

                                            <div x-show="open" x-cloak @click.outside="open = false" :style="menuStyle" class="z-[9999] rounded-lg border border-gray-200 bg-white py-1 text-left shadow-xl">
                                                <button type="button" @click="viewOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50">
                                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                                    View Details
                                                </button>
                                                @if ($fine->status === 'unpaid')
                                                    <button type="button" @click="openConfirm('{{ route('student-fines.paid', $fine) }}', 'PATCH', 'Mark fine as paid?', 'Are you sure you want to mark this fine as paid?', 'Mark Paid', 'app-button bg-[#16A34A] text-white')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-green-700 hover:bg-green-50">
                                                        <i data-lucide="check" class="h-4 w-4"></i>
                                                        Mark Paid
                                                    </button>
                                                    <button type="button" @click="openConfirm('{{ route('student-fines.waive', $fine) }}', 'PATCH', 'Waive this fine?', 'Are you sure you want to waive this fine?', 'Waive Fine', 'app-button bg-[#F59E0B] text-white')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-amber-700 hover:bg-amber-50">
                                                        <i data-lucide="badge-x" class="h-4 w-4"></i>
                                                        Waive Fine
                                                    </button>
                                                    <button type="button" @click="editOpen = true; open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50">
                                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                                        Edit Fine
                                                    </button>
                                                    <button type="button" @click="openConfirm('{{ route('student-fines.destroy', $fine) }}', 'DELETE', 'Delete this fine?', 'Are you sure you want to delete this fine?', 'Delete Fine', 'app-button bg-[#DC2626] text-white')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-700 hover:bg-red-50">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                        Delete Fine
                                                    </button>
                                                @elseif ($fine->status === 'waived')
                                                    <button type="button" @click="openConfirm('{{ route('student-fines.destroy', $fine) }}', 'DELETE', 'Delete this waived fine?', 'Are you sure you want to delete this fine?', 'Delete Fine', 'app-button bg-[#DC2626] text-white')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-700 hover:bg-red-50">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                        Delete Fine
                                                    </button>
                                                @endif
                                            </div>

                                            <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                                <div class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <h3 class="text-lg font-bold text-[#0B1F3A]">Fine Details</h3>
                                                            <p class="mt-1 text-sm text-gray-500">{{ $fine->title }}</p>
                                                        </div>
                                                        <button type="button" @click="viewOpen = false" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50" aria-label="Close">
                                                            <i data-lucide="x" class="h-4 w-4"></i>
                                                        </button>
                                                    </div>
                                                    <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                                                        <div class="rounded-lg bg-gray-50 p-3">
                                                            <dt class="text-gray-500">Status</dt>
                                                            <dd class="mt-1 font-bold text-[#0B1F3A]">{{ ucfirst($fine->status) }}</dd>
                                                        </div>
                                                        <div class="rounded-lg bg-gray-50 p-3">
                                                            <dt class="text-gray-500">Amount</dt>
                                                            <dd class="mt-1 font-bold text-[#0B1F3A]">PKR {{ number_format((float) $fine->amount, 2) }}</dd>
                                                        </div>
                                                        <div class="rounded-lg bg-gray-50 p-3">
                                                            <dt class="text-gray-500">Fine Date</dt>
                                                            <dd class="mt-1 font-bold text-[#0B1F3A]">{{ optional($fine->fine_date)->format('d M Y') ?: '-' }}</dd>
                                                        </div>
                                                        <div class="rounded-lg bg-gray-50 p-3">
                                                            <dt class="text-gray-500">Due Date</dt>
                                                            <dd class="mt-1 font-bold text-[#0B1F3A]">{{ optional($fine->due_date)->format('d M Y') ?: '-' }}</dd>
                                                        </div>
                                                        <div class="rounded-lg bg-gray-50 p-3 md:col-span-2">
                                                            <dt class="text-gray-500">Note</dt>
                                                            <dd class="mt-1 font-bold text-[#0B1F3A]">{{ $fine->note ?: 'No note' }}</dd>
                                                        </div>
                                                    </dl>
                                                    <div class="mt-4 flex justify-end">
                                                        <button type="button" @click="viewOpen = false" class="app-button app-button-light">Close</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                                <form method="POST" action="{{ route('student-fines.update', $fine) }}" class="w-full max-w-lg rounded-lg bg-white p-5 text-left shadow-xl">
                                                    @csrf
                                                    @method('PATCH')
                                                    <h3 class="text-lg font-bold text-[#0B1F3A]">Edit Fine</h3>
                                                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                                                        <select name="fine_type" required>
                                                            @foreach ($fineTypes as $value => $label)
                                                                <option value="{{ $value }}" @selected($fine->fine_type === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ $fine->amount }}" required>
                                                        <input name="title" value="{{ $fine->title }}" required>
                                                        <input type="date" name="fine_date" value="{{ optional($fine->fine_date)->format('Y-m-d') }}" required>
                                                        <input type="date" name="due_date" value="{{ optional($fine->due_date)->format('Y-m-d') }}">
                                                        <select name="student_fee_id">
                                                            <option value="">Not linked to fee</option>
                                                            @foreach ($student->fees as $fee)
                                                                <option value="{{ $fee->id }}" @selected($fine->student_fee_id === $fee->id)>{{ $fee->feeHead?->name ?: 'Fee' }} - {{ $fee->month }}/{{ $fee->year }}</option>
                                                            @endforeach
                                                        </select>
                                                        <textarea name="note" rows="2" class="md:col-span-2 rounded-md border-gray-300">{{ $fine->note }}</textarea>
                                                    </div>
                                                    <div class="mt-4 flex justify-end gap-2">
                                                        <button type="button" @click="editOpen = false" class="app-button app-button-light">Cancel</button>
                                                        <button class="app-button app-button-dark">Save Fine</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                                <form x-ref="confirmForm" method="POST" :action="confirmAction" class="w-full max-w-md rounded-lg bg-white p-5 text-left shadow-xl">
                                                    @csrf
                                                    <input type="hidden" name="_method" :value="confirmMethod">
                                                    <h3 class="text-lg font-bold text-[#0B1F3A]" x-text="confirmTitle"></h3>
                                                    <p class="mt-2 text-sm text-gray-600" x-text="confirmText"></p>
                                                    <div class="mt-5 flex justify-end gap-2">
                                                        <button type="button" @click="confirmOpen = false" class="app-button app-button-light">Cancel</button>
                                                        <button type="button" @click="submitConfirm()" :class="confirmClass" x-text="confirmButton"></button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-8 text-center text-gray-500">No fines recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="font-bold text-[#0B1F3A]">Payment History</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($feePayments->take(10) as $payment)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div>
                                <div class="font-semibold">{{ $payment->receipt_number }}</div>
                                <div class="mt-1 text-gray-500">{{ optional($payment->paid_on)->format('d M Y') }} · {{ Str::headline($payment->method) }} · {{ $payment->receiver?->name ?: 'Unknown receiver' }}</div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <div class="font-bold text-[#0B1F3A]">PKR {{ number_format((float) $payment->amount, 2) }}</div>
                                <a href="{{ route('fees.receipt', $payment) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-[#0B1F3A] hover:border-[#1DA1F2] hover:bg-blue-50">
                                    <i data-lucide="receipt" class="h-4 w-4"></i>
                                    View Receipt
                                </a>
                                <a href="{{ route('fees.receipt', ['payment' => $payment, 'download' => 1]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-gray-700 hover:border-[#1DA1F2] hover:bg-blue-50">
                                    <i data-lucide="download" class="h-4 w-4"></i>
                                    Download Receipt
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-sm text-gray-500">No payment history yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="font-bold text-[#0B1F3A]">Discount / Waiver Records</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[820px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Date</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Reason</th>
                                <th class="text-left">Linked Fee</th>
                                <th class="text-left">Approved By</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($student->discounts as $discount)
                                <tr>
                                    <td>{{ optional($discount->discount_date)->format('d M Y') }}</td>
                                    <td>{{ $discountTypes[$discount->discount_type] ?? Str::headline($discount->discount_type) }}</td>
                                    <td>
                                        <div class="font-semibold">{{ $discount->reason }}</div>
                                        <div class="text-gray-500">{{ $discount->note ?: 'No note' }}</div>
                                    </td>
                                    <td>{{ $discount->studentFee?->feeHead?->name ? $discount->studentFee->feeHead->name.' '.$discount->studentFee->month.'/'.$discount->studentFee->year : '-' }}</td>
                                    <td>{{ $discount->approved_by ?: '-' }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $discount->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-gray-500">No discounts or waivers recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="ledger-history" class="mt-6 rounded-lg border border-gray-200 bg-white scroll-mt-24">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="font-bold text-[#0B1F3A]">Full Ledger History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[900px] divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Date</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th class="text-right">Balance</th>
                                <th class="text-left">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($student->feeLedgerEntries as $entry)
                                <tr>
                                    <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                    <td>{{ $ledgerTypes[$entry->type] ?? Str::headline($entry->type) }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $entry->debit, 2) }}</td>
                                    <td class="text-right">PKR {{ number_format((float) $entry->credit, 2) }}</td>
                                    <td class="text-right font-bold">PKR {{ number_format((float) $entry->balance_after, 2) }}</td>
                                    <td>{{ $entry->creator?->name ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-gray-500">No ledger entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="results" class="app-card scroll-mt-24 p-6">
            <h2 class="font-bold text-[#0B1F3A]">Results</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->marks->groupBy('exam_id')->take(8) as $examMarks)
                            @php($firstMark = $examMarks->first())
                            <tr>
                                <td class="py-3 font-semibold">{{ $firstMark?->exam?->name ?: 'Exam' }}</td>
                                <td class="py-3">{{ $examMarks->sum('marks_obtained') }} / {{ $examMarks->sum('total_marks') }}</td>
                                <td class="py-3 text-right">{{ $firstMark?->exam?->is_published ? 'Published' : 'Draft' }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-6 text-gray-500">No result records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</x-app-layout>
