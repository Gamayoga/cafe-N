<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Northern Cafe') }} — Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Outfit', sans-serif; }
        </style>
    </head>
    <body class="antialiased min-h-screen relative flex items-center justify-center p-4 sm:p-8">
        
        <!-- Blurred Background Image -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/auth-bg.png') }}" class="w-full h-full object-cover" alt="Background">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-md"></div>
        </div>

        <!-- Floating Card Container -->
        <div class="flex w-full max-w-[1100px] min-h-[650px] bg-[#1A1A1A] rounded-[2.5rem] overflow-hidden shadow-2xl relative z-10">

            <!-- Left Panel: Premium Coffee Image -->
            <div class="hidden lg:block lg:w-1/2 relative bg-teal-900/20">
                <img src="{{ asset('images/auth-bg.png') }}" alt="Coffee Background" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/10"></div>
            </div>

            <!-- Right Panel: Login Form -->
            <div class="w-full lg:w-1/2 bg-[#222222] flex items-center justify-center p-8 relative">
                <div class="w-full max-w-sm py-2 relative z-10">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
