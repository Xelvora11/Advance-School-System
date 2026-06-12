<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Form</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    <div class="pdf-shell">
        <div class="pdf-header pdf-header-accent" style="border-color: {{ $school->primary_color }}">
            <h1 class="pdf-title" style="color: {{ $school->primary_color }}">{{ $school->name }}</h1>
            <div class="pdf-muted">{{ $school->address }}</div>
            <h2 class="pdf-subtitle">Admission Form</h2>
        </div>

        @foreach ([
            'Student Name' => $admission->student_name,
            'Gender' => ucfirst((string) $admission->gender),
            'Date of Birth' => optional($admission->date_of_birth)->format('d M Y'),
            'Requested Class' => $admission->requestedClass?->name,
            'Guardian Name' => $admission->guardian_name,
            'Guardian Phone' => $admission->guardian_phone,
            'Address' => $admission->address,
            'Status' => ucfirst($admission->status),
        ] as $label => $value)
            <div class="pdf-line">
                <span class="pdf-label">{{ $label }}</span>
                <span class="pdf-value">{{ $value ?: '-' }}</span>
            </div>
        @endforeach

        <h3 class="pdf-subtitle">Document Checklist</h3>
        @forelse (($admission->document_checklist ?? []) as $key => $checked)
            <div class="pdf-line">
                <span>{{ Str::headline($key) }}</span>
                <strong style="float: right;">{{ $checked ? 'Received' : 'Pending' }}</strong>
            </div>
        @empty
            <div class="pdf-note">No documents have been recorded yet.</div>
        @endforelse

        <table class="pdf-signatures">
            <tr>
                <td><div class="pdf-signature-line">Parent Signature</div></td>
                <td><div class="pdf-signature-line pdf-signature-line-right">Office Signature</div></td>
            </tr>
        </table>
    </div>

    @if (empty($pdf))
        <div class="pdf-actions">
            <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}">Download PDF</a>
            <button onclick="window.print()">Print</button>
        </div>
    @endif
</body>
</html>
