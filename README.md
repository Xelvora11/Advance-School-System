# Advance School Management SaaS

Advance School is a Laravel SaaS school-management system for Pakistani small and medium schools. It covers the day-to-day operating workflows a school needs before launch: schools, users, students, teachers, classes, attendance, fees, exams/results, notices, reports, documents, PDFs, and parent access.

## Included Modules

- Manual SaaS onboarding by Super Admin with school status controls, plan/payment tracking, admin password reset, internal note history, and activity audit trail.
- Role-based access for Super Admin, School Admin, Principal, Teacher, and Parent.
- School data isolation through `school_id` on every school-owned table.
- School setup with profile, branding, fee due day, and marksheet template settings.
- Classes, sections, subjects, class teacher assignment.
- Student records with guardian details and optional parent login linking.
- Teacher records with optional teacher login and class/subject assignment.
- Manual daily attendance with duplicate prevention and edit history.
- Fee heads, fee structures, monthly fee generation, manual payment marking, receipt and challan print views.
- Exams, exam subjects, marks entry, grade/pass-fail calculation, publish/unpublish, marksheet print view.
- Notices for parents/classes/sections/teachers.
- Lightweight internal Messages module using database-backed threads, unread counts, role-safe recipients, and optional school-controlled attachments.
- Basic reports and student CSV export.
- Activity logs with school, user, role, IP address, and login history.
- Admission inquiry, approval/rejection, conversion to student, and admission form PDF.
- Student document upload and protected deletion.
- Production security headers and setup-completion guard.
- Local Vite-built UI assets, local hero artwork, and print/PDF-safe document templates.
- Deployment, backup, QA, and healthcheck scripts.

## Optional Future Extensions

Public self-registration, online payment gateway, homework, assignments, payroll, inventory, library, transport GPS, biometric/RFID/QR attendance, real-time WebSocket chat, AI assistant, student portal, and mobile app.

## Default Login

After seeding:

- URL: `/login`
- Email: `admin@advanceschool.test`
- Password: `password123`
- Role: Super Admin

The Super Admin creates each school and the first School Admin account from the Super Admin panel.

## Demo School Login

The local database also has a demo school seeded for UI review:

- School: `Iqra Model School`
- School Admin: `admin@demo-school.test`
- Teacher: `teacher@demo-school.test`
- Password for both: `password123`

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan db:seed --class=DemoSchoolSeeder
php artisan storage:link
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

The current workspace already has dependencies installed, migrations run, storage linked, and the local server running at:

```text
http://127.0.0.1:8000
```

## MySQL Setup

For production or VPS hosting, update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=advance_school
DB_USERNAME=your_user
DB_PASSWORD=your_password
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_SUPPORT_EMAIL=support@your-domain.com
APP_SUPPORT_PHONE="+92 300 0000000"
```

Then run:

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
```

## Production Deployment

Use `.env.production.example` as the base for production environment values.

```bash
cp .env.production.example .env
php artisan key:generate
./scripts/deploy.sh
```

Extra deployment references:

- `deployment/nginx.conf.example`
- `docs/PRODUCTION_CHECKLIST.md`
- `docs/QA_CHECKLIST.md`
- `scripts/backup.sh`
- `scripts/healthcheck.sh`

The Blade layouts use compiled Vite assets when `public/build/manifest.json` exists. CDN fallbacks are only for emergency/local use before assets are built.

## Testing

```bash
./scripts/healthcheck.sh
```

The healthcheck clears config, validates routes, caches views, builds Vite assets, runs the Laravel test suite, and clears optimization artifacts afterward. Security-focused coverage includes role login redirects, inactive school blocking, cross-school student isolation, parent child isolation, admission conversion, and student guardian behavior.

## Deployment Checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use MySQL with regular backups.
- Run `php artisan migrate --force --seed`.
- Run `php artisan storage:link`.
- Configure mail for password resets.
- Point the web root to `/public`.
- Ensure `storage/` and `bootstrap/cache/` are writable.
- Use HTTPS.
- Run `./scripts/healthcheck.sh` before deployment.
- Run and restore-test `./scripts/backup.sh` after MySQL credentials are configured.
- Create the Super Admin, then create school accounts manually after payment/discussion.

## Demo Workflow

1. Log in as Super Admin.
2. Create a school and first School Admin.
3. Log in as School Admin.
4. Complete school setup.
5. Add classes, sections, and subjects.
6. Add students and guardians.
7. Add teachers and assignments.
8. Mark attendance.
9. Create fee heads and structures, generate monthly fees, mark payments.
10. Create exams, add subjects, enter marks, publish results, and print marksheets.
