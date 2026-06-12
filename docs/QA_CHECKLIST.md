# QA Checklist

## Authentication

- Login works for Super Admin, School Admin, Teacher, and Parent.
- Public registration returns 404.
- Forgot password page loads after SMTP setup.
- Inactive users cannot log in.
- Inactive schools cannot log in.

## School Admin

- Setup wizard saves profile, colors, logo, stamp, and signature.
- Classes cannot be deleted when students exist.
- Students can be searched and filtered.
- Parent login can be linked from student form.
- Multiple guardians without email create separate records when phone/CNIC differs.
- New parent logins require an explicit password of at least 8 characters.
- Student documents upload and delete correctly.
- Teacher login can be created.
- Teacher assignments are visible.
- Attendance duplicate date/student data updates cleanly.
- Attendance edits require a reason.
- Fee generation creates correct monthly records.
- Partial payment updates balance.
- Receipts and challans can be downloaded as PDF.
- Receipts, challans, admission forms, and marksheets retain styling in generated PDFs.
- Results cannot accept marks greater than total marks.
- Published marksheets are visible to parents.

## Parent

- Parent dashboard is mobile friendly.
- Parent can see linked child profile, fees, attendance, notices, and results.
- Parent cannot access another child by changing URL.

## Reports

- Student CSV downloads.
- Defaulter report reflects unpaid and partial records.
- Payment report shows latest payments.

## Deployment

- `php artisan test` passes.
- `npm run build` passes.
- `./scripts/healthcheck.sh` passes.
- `php artisan view:cache` passes.
- `php artisan route:list` passes.
- Built app layout does not require CDN Alpine/Lucide scripts.
- HTTPS redirects are active.
- Backups are configured and restorable.
