<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Advance School') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                school: {
                                    navy: '#0B1F3A',
                                    blue: '#1DA1F2',
                                    bg: '#F5F8FA',
                                }
                            }
                        }
                    }
                }
            </script>
            <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
            <script src="https://unpkg.com/lucide@latest"></script>
        @endif
        <style>
            [x-cloak] { display: none !important; }
            input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="date"], input[type="file"], select, textarea {
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                padding: 0.6rem 0.75rem;
                background: #fff;
                outline: none;
                width: 100%;
            }
            input:focus, select:focus, textarea:focus {
                border-color: #1DA1F2;
                box-shadow: 0 0 0 3px rgba(29, 161, 242, .14);
            }
            input[type="checkbox"], input[type="radio"] {
                width: 1rem;
                height: 1rem;
                accent-color: #1DA1F2;
            }
            .app-card {
                border: 1px solid #e5e7eb;
                background: #fff;
                border-radius: 0.75rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            }
            .app-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: .5rem;
                border-radius: .5rem;
                padding: .6rem .9rem;
                font-size: .875rem;
                font-weight: 700;
                min-height: 2.5rem;
            }
            .app-button-primary { background: #1DA1F2; color: #fff; }
            .app-button-dark { background: #0B1F3A; color: #fff; }
            .app-button-light { border: 1px solid #d1d5db; background: #fff; color: #111827; }
            .app-table th {
                background: #f9fafb;
                color: #6b7280;
                font-size: .75rem;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
            }
            .app-table th, .app-table td { padding: .9rem 1rem; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-[#F5F8FA] text-gray-900">
            @include('layouts.navigation')

            <div class="lg:pl-72">
                @isset($header)
                    <header class="border-b border-gray-200 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="py-6">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        @if (session('status'))
                            <div class="mb-5 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                                <i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4"></i>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if (session('generated_password'))
                            <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                <div class="flex items-center gap-2 font-semibold">
                                    <i data-lucide="key-round" class="h-4 w-4"></i>
                                    Generated password shown once
                                </div>
                                <div class="mt-2 font-mono text-base font-bold">{{ session('generated_password') }}</div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                <div class="flex items-center gap-2 font-semibold">
                                    <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                                    Please check the form.
                                </div>
                                <ul class="mt-2 list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{ $slot }}
                </main>
            </div>
        </div>
        <script>
            window.addEventListener('load', () => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
