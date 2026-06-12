<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: #f3f4f6;
        color: #111827;
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        padding: 24px;
    }
    .pdf-shell {
        max-width: 840px;
        margin: 0 auto;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        padding: 32px;
    }
    .pdf-shell-wide { max-width: 960px; }
    .pdf-header {
        border-bottom: 1px solid #d1d5db;
        margin-bottom: 24px;
        padding-bottom: 18px;
    }
    .pdf-header-accent {
        border-bottom-width: 4px;
        text-align: center;
    }
    .pdf-header-table,
    .pdf-kv,
    .pdf-table {
        width: 100%;
        border-collapse: collapse;
    }
    .pdf-header-table td { vertical-align: top; }
    .pdf-title {
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0;
    }
    .pdf-subtitle {
        font-size: 18px;
        font-weight: 800;
        margin: 14px 0 0;
    }
    .pdf-muted { color: #6b7280; }
    .pdf-label {
        color: #6b7280;
        display: block;
        font-size: 12px;
        margin-bottom: 3px;
    }
    .pdf-value { font-weight: 700; }
    .pdf-kv { margin-top: 18px; }
    .pdf-kv td {
        padding: 8px 16px 8px 0;
        vertical-align: top;
        width: 50%;
    }
    .pdf-kv-three td { width: 33.333%; }
    .pdf-panel {
        background: #f9fafb;
        border-radius: 8px;
        margin-top: 28px;
        padding: 20px;
        text-align: right;
    }
    .pdf-panel-total {
        font-size: 30px;
        font-weight: 800;
        line-height: 1.2;
    }
    .pdf-table { margin-top: 28px; }
    .pdf-table th,
    .pdf-table td {
        border: 1px solid #d1d5db;
        padding: 9px 10px;
        vertical-align: top;
    }
    .pdf-table th {
        background: #f9fafb;
        color: #374151;
        font-weight: 800;
        text-align: left;
    }
    .pdf-right { text-align: right; }
    .pdf-center { text-align: center; }
    .pdf-success { color: #047857; }
    .pdf-danger { color: #b91c1c; }
    .pdf-note {
        background: #f9fafb;
        border-radius: 8px;
        color: #374151;
        margin-top: 22px;
        padding: 14px;
    }
    .pdf-signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 44px;
    }
    .pdf-signatures td {
        padding-top: 10px;
        vertical-align: top;
        width: 50%;
    }
    .pdf-signature-line {
        border-top: 1px solid #6b7280;
        padding-top: 8px;
        width: 190px;
    }
    .pdf-signature-line-right {
        margin-left: auto;
        text-align: right;
    }
    .pdf-footer {
        color: #6b7280;
        font-size: 12px;
        margin-top: 28px;
        text-align: center;
    }
    .pdf-line {
        border-bottom: 1px solid #d1d5db;
        padding: 10px 0;
    }
    .pdf-actions {
        margin: 16px auto 0;
        max-width: 840px;
        text-align: right;
    }
    .pdf-actions a,
    .pdf-actions button {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        display: inline-block;
        font: inherit;
        font-weight: 700;
        margin-left: 8px;
        padding: 9px 14px;
        text-decoration: none;
    }
    .pdf-actions button,
    .pdf-action-primary {
        background: #1DA1F2;
        border-color: #1DA1F2;
        color: #ffffff;
    }
    @media print {
        body { background: #ffffff; padding: 0; }
        .pdf-shell { border: 0; border-radius: 0; max-width: none; }
        .pdf-actions { display: none; }
    }
</style>
