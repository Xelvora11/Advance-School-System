# Production Checklist

## Information Needed From Client

- Final domain name.
- Hosting type: shared hosting, VPS, or managed server.
- VPS SSH access, if VPS.
- MySQL database name, username, password, host, and port.
- Mail SMTP host, port, username, password, encryption, and sender email.
- Company support phone, WhatsApp, email, and address.
- Production values for `APP_SUPPORT_EMAIL` and `APP_SUPPORT_PHONE`.
- Final logo, favicon, and brand colors.
- Super Admin name, email, phone, and final password.
- First real school details for onboarding.
- Backup preference: daily server backup, database-only backup, or both.

## Before Launch

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set final `APP_URL`.
- Use MySQL, not SQLite.
- Generate a fresh `APP_KEY`.
- Configure SMTP mail.
- Set `APP_SUPPORT_EMAIL` and `APP_SUPPORT_PHONE`.
- Run `php artisan migrate --force --seed`.
- Run `php artisan storage:link`.
- Run `php artisan optimize`.
- Run `./scripts/healthcheck.sh` before the final deployment.
- Confirm `/public` is the web root.
- Confirm HTTPS is enabled.
- Confirm file upload limit is at least 10MB.
- Confirm backups are scheduled.
- Run `./scripts/backup.sh` once and restore-test the generated SQL file.
- Change default seeded passwords.

## Smoke Test

- Super Admin can log in.
- Super Admin can create a school and school admin.
- Inactive school cannot log in.
- School Admin completes setup.
- Class, section, and subject can be created.
- Student can be created with guardian.
- Teacher can be created with login and assignment.
- Attendance can be marked.
- Fee head, fee structure, fee generation, and payment work.
- Receipt/challan PDF downloads work.
- Exam, marks entry, publish, and marksheet PDF work.
- Parent sees only linked children.
- Activity logs are recorded.
- PDF downloads are styled correctly without external CDN scripts.
- Public landing page uses the final support email and built Vite assets.
