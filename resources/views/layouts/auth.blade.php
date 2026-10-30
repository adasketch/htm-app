<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101b2d">
    <script>
        (() => {
            const language = localStorage.getItem('hotel_task_language') || 'en';
            document.documentElement.lang = language === 'id' ? 'id' : 'en';
            document.documentElement.classList.toggle('dark', localStorage.getItem('hotel_task_theme') === 'dark');
        })();
    </script>
    <title>@yield('title', 'HotelTask') · HotelTask</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-slate-900 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.04fr_.96fr]">
        <section class="relative hidden overflow-hidden bg-[#101b2d] px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute -right-40 -top-32 h-[30rem] w-[30rem] rounded-full border border-white/10"></div>
            <div class="absolute -right-20 -top-12 h-[22rem] w-[22rem] rounded-full border border-white/10"></div>
            <div class="absolute -bottom-52 -left-24 h-[34rem] w-[34rem] rounded-full bg-[#a3d7c4]/10 blur-3xl"></div>
            <a href="/" class="relative z-10 flex items-center gap-3 text-lg font-semibold tracking-tight">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#a3d7c4] text-[#101b2d]">H</span>
                HotelTask
            </a>
            <div class="relative z-10 max-w-xl py-16">
                <p class="mb-5 text-xs font-semibold uppercase tracking-[.24em] text-[#a3d7c4]">Hospitality, in sync</p>
                <h1 class="text-4xl font-medium leading-tight tracking-tight xl:text-5xl">Every property.<br>Every team.<br><span class="text-[#a3d7c4]">Moving together.</span></h1>
                <p class="mt-6 max-w-md text-base leading-7 text-slate-300">A calmer way to coordinate daily work across your properties and departments.</p>
                <div class="mt-12 flex items-center gap-4 border-t border-white/10 pt-6 text-sm text-slate-300">
                    <span class="flex -space-x-2"><i class="h-8 w-8 rounded-full border-2 border-[#101b2d] bg-[#d9b59d]"></i><i class="h-8 w-8 rounded-full border-2 border-[#101b2d] bg-[#a3d7c4]"></i><i class="h-8 w-8 rounded-full border-2 border-[#101b2d] bg-[#d8d2a4]"></i></span>
                    Built for teams that keep things running.
                </div>
            </div>
            <p class="relative z-10 text-xs text-slate-400">© {{ date('Y') }} HotelTask Management</p>
        </section>
        <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <a href="/" class="mb-10 flex items-center gap-3 text-lg font-semibold tracking-tight lg:hidden">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#101b2d] text-[#a3d7c4]">H</span>HotelTask
                </a>
                @yield('content')
                <p class="mt-10 text-center text-xs text-slate-400">Secure workspace for your hospitality team</p>
            </div>
        </section>
    </main>
</body>
</html>
