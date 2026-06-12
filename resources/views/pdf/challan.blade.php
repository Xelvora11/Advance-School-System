<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Challan</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    <div class="pdf-shell">
        <div class="pdf-header pdf-header-accent" style="border-color: {{ $school->primary_color }}">
            <h1 class="pdf-title" style="color: {{ $school->primary_color }}">{{ $school->name }}</h1>
            <div class="pdf-muted">{{ $school->address }}</div>
            <h2 class="pdf-subtitle">Fee Challan</h2>
        </div>

        <table class="pdf-kv">
            <tr>
                <td><span class="pdf-label">Student</span><span class="pdf-value">{{ $fee->student?->name }}</span></td>
                <td><span class="pdf-label">Registration</span><span class="pdf-value">{{ $fee->student?->registration_number }}</span></td>
            </tr>
            <tr>
                <td><span class="pdf-label">Class</span><span class="pdf-value">{{ $fee->student?->schoolClass?->name }} {{ $fee->student?->section?->name }}</span></td>
                <td><span class="pdf-label">Due date</span><span class="pdf-value">{{ optional($fee->due_date)->format('d M Y') ?: '-' }}</span></td>
            </tr>
        </table>

        <table class="pdf-table">
            <tr>
                <th>Head</th>
                <th class="pdf-right">Amount</th>
            </tr>
            <tr>
                <td>{{ $fee->feeHead?->name }}</td>
                <td class="pdf-right">PKR {{ number_format($fee->payableAmount(), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Balance</strong></td>
                <td class="pdf-right"><strong>PKR {{ number_format($fee->balance(), 2) }}</strong></td>
            </tr>
        </table>
    </div>

    @if (empty($pdf))
        <div class="pdf-actions">
            <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}">Download PDF</a>
            <button onclick="window.print()">Print / Save PDF</button>
        </div>
    @endif
</body>
</html>
