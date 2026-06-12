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
        @endif
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8 bg-[#F5F8FA]">
            <div class="text-center">
                <a href="/">
                    <x-application-logo class="mx-auto h-16 w-16" />
                </a>
                <div class="mt-3 text-lg font-bold text-[#0B1F3A]">Advance School</div>
                <div class="mt-1 text-sm text-gray-500">School Management SaaS</div>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-5 bg-white shadow-sm border border-gray-200 overflow-hidden sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
