<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Advance School') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="bg-[#F5F8FA] text-gray-900 antialiased">
        @php($supportEmail = config('app.support_email', 'hello@example.com'))
        <main>
            <section class="relative min-h-[86vh] overflow-hidden bg-[#0B1F3A] text-white">
                <img src="{{ asset('images/school-saas-hero.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
                <div class="absolute inset-0 bg-gradient-to-r from-[#07172d] via-[#07172d]/82 to-[#07172d]/20"></div>
                <div class="relative mx-6 flex min-h-[86vh] w-auto max-w-[21rem] flex-col justify-center py-16 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-base font-black text-[#0B1F3A]">AS</div>
                        <div>
                            <div class="text-sm font-bold uppercase tracking-[0.18em] text-blue-100">Advance School</div>
                            <div class="text-sm text-blue-100/80">School Management SaaS</div>
                        </div>
                    </div>

                    <h1 class="max-w-3xl text-3xl font-black leading-tight sm:text-6xl">Advance School Management</h1>
                    <p class="mt-6 max-w-2xl text-base leading-8 text-blue-50 sm:text-lg">A complete operating system for schools to manage admissions, students, teachers, attendance, fees, exams, notices, reports, and parent access from one secure dashboard.</p>

                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-[#1DA1F2] px-5 text-sm font-bold text-white shadow-sm">School Login</a>
                        <a href="mailto:{{ $supportEmail }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-white/30 px-5 text-sm font-bold text-white hover:bg-white/10">Contact Company</a>
                    </div>
                </div>
            </section>

            <section class="-mt-14 relative z-10 mx-auto max-w-7xl px-6 pb-14">
                <div class="grid max-w-[21rem] gap-4 md:max-w-none md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold text-[#0B1F3A]">Manual SaaS onboarding</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Create each school account from the Super Admin panel after payment, verification, and setup discussion.</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold text-[#0B1F3A]">Role-based operations</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Separate dashboards for Super Admin, School Admin, Principal, Teacher, and Parent keep daily work focused.</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold text-[#0B1F3A]">Production-ready records</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">School data isolation, activity logs, protected uploads, PDF receipts, challans, admission forms, and marksheets are built in.</p>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
