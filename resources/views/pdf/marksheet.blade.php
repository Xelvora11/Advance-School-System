<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Marksheet</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    @php
        $total = $marks->sum('total_marks');
        $obtained = $marks->sum('marks_obtained');
        $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0;
        $failed = $marks->contains(fn ($mark) => $mark->status === 'fail');
        $showStamp = data_get($template->extra_settings, 'show_school_stamp', true);
        $showSignature = data_get($template->extra_settings, 'show_principal_signature', true);
    @endphp

    <div class="pdf-shell pdf-shell-wide">
        <div class="pdf-header pdf-header-accent" style="border-color: {{ $school->primary_color }}">
            <h1 class="pdf-title" style="color: {{ $school->primary_color }}">{{ $school->name }}</h1>
            <div class="pdf-muted">{{ $school->address }}</div>
            <h2 class="pdf-subtitle">{{ $template->header_text ?: 'Official Result Card' }}</h2>
        </div>

        <table class="pdf-kv pdf-kv-three">
            <tr>
                <td><span class="pdf-label">Student</span><span class="pdf-value">{{ $student->name }}</span></td>
                <td><span class="pdf-label">Registration</span><span class="pdf-value">{{ $student->registration_number }}</span></td>
                <td><span class="pdf-label">Class</span><span class="pdf-value">{{ $student->schoolClass?->name }} {{ $student->section?->name }}</span></td>
            </tr>
            <tr>
                <td><span class="pdf-label">Exam</span><span class="pdf-value">{{ $exam->name }}</span></td>
                <td><span class="pdf-label">Session</span><span class="pdf-value">{{ $exam->session ?: $school->academic_year }}</span></td>
                <td><span class="pdf-label">Result</span><span class="pdf-value {{ $failed ? 'pdf-danger' : 'pdf-success' }}">{{ $failed ? 'Fail' : 'Pass' }}</span></td>
            </tr>
        </table>

        <table class="pdf-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th class="pdf-right">Total</th>
                    <th class="pdf-right">Obtained</th>
                    <th class="pdf-center">Grade</th>
                    <th class="pdf-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($marks as $mark)
                    <tr>
                        <td>{{ $mark->subject?->name }}</td>
                        <td class="pdf-right">{{ $mark->total_marks }}</td>
                        <td class="pdf-right">{{ $mark->marks_obtained }}</td>
                        <td class="pdf-center">{{ $mark->grade }}</td>
                        <td class="pdf-center">{{ ucfirst($mark->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="pdf-right"><strong>{{ $total }}</strong></td>
                    <td class="pdf-right"><strong>{{ $obtained }}</strong></td>
                    <td class="pdf-center" colspan="2"><strong>{{ $percentage }}%</strong></td>
                </tr>
            </tfoot>
        </table>

        @if ($template->show_grading_table)
            <div class="pdf-note">Grades: A+ 90%+, A 80%+, B 70%+, C 60%+, D 50%+, F below 50%.</div>
        @endif

        @if ($template->show_attendance_summary)
            <div class="pdf-note">Attendance summary: Attendance summary appears here when attendance records are available.</div>
        @endif

        @if ($template->show_teacher_remarks || $template->show_principal_remarks)
            <table class="pdf-kv">
                <tr>
                    @if ($template->show_teacher_remarks)
                        <td><span class="pdf-label">Teacher remarks</span><span class="pdf-value">Keep improving.</span></td>
                    @endif
                    @if ($template->show_principal_remarks)
                        <td><span class="pdf-label">Principal remarks</span><span class="pdf-value">Promoted.</span></td>
                    @endif
                </tr>
            </table>
        @endif

        <table class="pdf-signatures">
            <tr>
                <td>
                    @if ($showStamp && $school->stamp_path)
                        <img src="{{ ! empty($pdf) ? public_path('storage/'.$school->stamp_path) : asset('storage/'.$school->stamp_path) }}" alt="" style="max-height: 60px;">
                    @endif
                    <div class="pdf-signature-line">School Stamp</div>
                </td>
                <td>
                    @if ($showSignature && $school->signature_path)
                        <img src="{{ ! empty($pdf) ? public_path('storage/'.$school->signature_path) : asset('storage/'.$school->signature_path) }}" alt="" style="max-height: 55px;">
                    @endif
                    <div class="pdf-signature-line pdf-signature-line-right">Principal</div>
                </td>
            </tr>
        </table>

        <div class="pdf-footer">{{ $template->footer_text }}</div>
    </div>

    @if (empty($pdf))
        <div class="pdf-actions" style="max-width: 960px;">
            <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}">Download PDF</a>
            <button onclick="window.print()">Print / Save PDF</button>
        </div>
    @endif
</body>
</html>
