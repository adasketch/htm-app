@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="mb-8">
    <p class="mb-3 text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Selamat datang kembali</p>
    <h2 class="text-3xl font-semibold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan email dan password yang terdaftar.</p>
</div>

<form id="login-form" class="space-y-5" novalidate>
    <div>
        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="nama@perusahaan.com">
    </div>
    <div>
        <div class="mb-2 flex items-center justify-between"><label for="password" class="text-sm font-medium text-slate-700">Password</label></div>
        <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="Masukkan password">
    </div>
    <div id="login-message" role="status" aria-live="polite" class="hidden rounded-xl px-4 py-3 text-sm"></div>
    <button id="login-submit" type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101b2d] px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-400/30 disabled:cursor-wait disabled:opacity-60">
        <span>Masuk</span><span aria-hidden="true">→</span>
    </button>
</form>

<p class="mt-7 text-center text-sm text-slate-500">Belum punya akun? <a href="/register" class="font-semibold text-emerald-800 hover:text-emerald-950">Daftar di sini</a></p>

<script>
    const loginForm = document.getElementById('login-form');
    const loginMessage = document.getElementById('login-message');
    const loginSubmit = document.getElementById('login-submit');
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        loginMessage.className = 'hidden rounded-xl px-4 py-3 text-sm';
        loginSubmit.disabled = true;
        loginSubmit.querySelector('span').textContent = 'Memeriksa akun…';
        const values = new FormData(loginForm);
        try {
            const response = await fetch('/api/v1/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email: values.get('email'), password: values.get('password') })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Email atau password tidak sesuai.');
            sessionStorage.setItem('hotel_task_access_token', data.access_token);
            sessionStorage.setItem('hotel_task_user', JSON.stringify(data.user));
            window.location.assign('/dashboard');
        } catch (error) {
            loginMessage.textContent = error.message || 'Tidak dapat terhubung ke server.';
            loginMessage.className = 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
        } finally {
            loginSubmit.disabled = false;
            loginSubmit.querySelector('span').textContent = 'Masuk';
        }
    });
</script>
@endsection
