@php
    $title = match ($type) {
        'challan' => 'Fee Challan Preview',
        'receipt' => 'Fee Receipt Preview',
        default => 'Marksheet Preview',
    };
    $showStamp = data_get($template->extra_settings, 'show_school_stamp', true);
    $showSignature = data_get($template->extra_settings, 'show_principal_signature', true);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-[#1DA1F2]">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                    Template Preview
                </div>
                <h1 class="mt-1 text-2xl font-bold text-[#0B1F3A]">{{ $title }}</h1>
                <p class="mt-1 text-sm text-gray-600">Sample preview using current school setup values.</p>
            </div>
            <button type="button" onclick="window.print()" class="app-button app-button-primary">
                <i data-lucide="printer" class="h-4 w-4"></i>
                Print Preview
            </button>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
            <div class="border-b-4 pb-5" style="border-color: {{ $school->primary_color }}">
                <div class="flex items-start gap-4">
                    @if ($school->logo_path)
                        <img src="{{ asset('storage/'.$school->logo_path) }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-[#E8F4FE] text-xl font-black text-[#0B1F3A]">
                            {{ Str::of($school->short_name ?: $school->name)->substr(0, 2)->upper() }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-2xl font-black" style="color: {{ $school->primary_color }}">{{ $school->name }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $school->address ?: 'School address will appear here' }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $school->phone }} {{ $school->email }}</p>
                    </div>
                </div>
            </div>

            @if ($type === 'marksheet')
                <div class="mt-6 text-center">
                    <h3 class="text-xl font-black text-[#0B1F3A]">{{ $template->header_text ?: 'Official Result Card' }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $school->academic_year ?: 'Academic Year' }}</p>
                </div>
                <div class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                    <div><span class="text-gray-500">Student</span><div class="font-bold">Sample Student</div></div>
                    <div><span class="text-gray-500">Registration</span><div class="font-bold">REG-{{ now()->year }}-0001</div></div>
                    <div><span class="text-gray-500">Class</span><div class="font-bold">Class 8 - A</div></div>
                </div>
                <table class="mt-6 w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="border border-gray-200 px-3 py-2">Subject</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">Total</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">Obtained</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([['English', 100, 82, 'A'], ['Mathematics', 100, 76, 'B'], ['Science', 100, 88, 'A']] as [$subject, $total, $obtained, $grade])
                            <tr>
                                <td class="border border-gray-200 px-3 py-2">{{ $subject }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-right">{{ $total }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-right">{{ $obtained }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-center">{{ $grade }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($template->show_attendance_summary)
                    <div class="mt-5 rounded-lg bg-gray-50 p-4 text-sm text-gray-700">Attendance summary: 92% present in this term.</div>
                @endif
                @if ($template->show_grading_table)
                    <div class="mt-3 text-xs text-gray-500">Grades: A+ 90%+, A 80%+, B 70%+, C 60%+, D 50%+, F below 50%.</div>
                @endif
            @elseif ($type === 'challan')
                <div class="mt-6 text-center">
                    <h3 class="text-xl font-black text-[#0B1F3A]">Fee Challan</h3>
                    <p class="mt-1 text-sm text-gray-500">Sample monthly fee voucher</p>
                </div>
                <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    <div><span class="text-gray-500">Student</span><div class="font-bold">Sample Student</div></div>
                    <div><span class="text-gray-500">Due Date</span><div class="font-bold">{{ now()->addDays($school->default_fee_due_day)->format('d M Y') }}</div></div>
                    <div><span class="text-gray-500">Class</span><div class="font-bold">Class 8 - A</div></div>
                    <div><span class="text-gray-500">Month</span><div class="font-bold">{{ now()->format('F Y') }}</div></div>
                </div>
                <div class="mt-6 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between text-sm"><span>Tuition Fee</span><strong>PKR 5,000</strong></div>
                    <div class="mt-2 flex justify-between border-t border-gray-100 pt-2 text-sm"><span>Balance</span><strong>PKR 5,000</strong></div>
                </div>
            @else
                <div class="mt-6 text-center">
                    <h3 class="text-xl font-black text-[#0B1F3A]">Fee Receipt</h3>
                    <p class="mt-1 text-sm text-gray-500">Receipt No. RCPT-{{ now()->format('Ymd') }}-00001</p>
                </div>
                <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    <div><span class="text-gray-500">Student</span><div class="font-bold">Sample Student</div></div>
                    <div><span class="text-gray-500">Paid On</span><div class="font-bold">{{ now()->format('d M Y') }}</div></div>
                    <div><span class="text-gray-500">Method</span><div class="font-bold">Cash</div></div>
                    <div><span class="text-gray-500">Amount</span><div class="font-bold">PKR 5,000</div></div>
                </div>
            @endif

            <div class="mt-10 grid grid-cols-2 items-end gap-6">
                <div>
                    @if ($showStamp && $school->stamp_path)
                        <img src="{{ asset('storage/'.$school->stamp_path) }}" alt="" class="h-20 object-contain">
                    @endif
                    <div class="mt-3 border-t border-gray-300 pt-2 text-sm font-semibold text-gray-700">School Stamp</div>
                </div>
                <div class="text-right">
                    @if ($showSignature && $school->signature_path)
                        <img src="{{ asset('storage/'.$school->signature_path) }}" alt="" class="ml-auto h-16 object-contain">
                    @endif
                    <div class="mt-3 border-t border-gray-300 pt-2 text-sm font-semibold text-gray-700">Principal Signature</div>
                </div>
            </div>

            @if ($type === 'marksheet' && ($template->show_teacher_remarks || $template->show_principal_remarks))
                <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    @if ($template->show_teacher_remarks)
                        <div class="rounded-lg bg-gray-50 p-3"><strong>Teacher remarks:</strong> Keep improving.</div>
                    @endif
                    @if ($template->show_principal_remarks)
                        <div class="rounded-lg bg-gray-50 p-3"><strong>Principal remarks:</strong> Promoted.</div>
                    @endif
                </div>
            @endif

            <div class="mt-8 text-center text-xs text-gray-500">{{ $template->footer_text ?: 'This is a sample preview. Actual documents use real student and fee data.' }}</div>
        </div>
    </div>
</x-app-layout>
