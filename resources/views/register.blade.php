@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
<div class="mb-7">
    <p class="mb-3 text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Mulai bersama tim</p>
    <h2 class="text-3xl font-semibold tracking-tight text-slate-900">Buat akun Anda</h2>
    <p class="mt-2 text-sm leading-6 text-slate-500">Daftar untuk meminta akses ke workspace HotelTask.</p>
</div>

<div id="registration-success" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-5" role="status" aria-live="polite">
    <div class="mb-3 grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-lg text-emerald-800">✓</div>
    <h3 class="font-semibold text-emerald-950">Pendaftaran diterima</h3>
    <p id="registration-success-message" class="mt-2 text-sm leading-6 text-emerald-900"></p>
    <p class="mt-3 text-sm text-emerald-900">Kami akan memberi tahu Anda melalui email setelah tim menyelesaikan peninjauan.</p>
    <a href="/login" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-900 hover:text-emerald-700">Kembali ke halaman masuk <span aria-hidden="true">→</span></a>
</div>

<form id="registration-form" class="space-y-4" novalidate>
    <div>
        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nama lengkap</label>
        <input id="name" name="name" type="text" autocomplete="name" maxlength="255" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="Nama Anda">
    </div>
    <div>
        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" maxlength="255" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="nama@perusahaan.com">
    </div>
    <div>
        <label for="property_id" class="mb-2 block text-sm font-medium text-slate-700">Properti</label>
        <select id="property_id" name="property_id" required disabled class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            <option value="">Memuat properti…</option>
        </select>
    </div>
    <div>
        <label for="department_id" class="mb-2 block text-sm font-medium text-slate-700">Departemen</label>
        <select id="department_id" name="department_id" required disabled class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            <option value="">Pilih properti terlebih dahulu</option>
        </select>
    </div>
    <div>
        <label for="position" class="mb-2 block text-sm font-medium text-slate-700">Jabatan / sebutan pekerjaan</label>
        <input id="position" name="position" type="text" autocomplete="organization-title" maxlength="255" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="Contoh: Front Office Supervisor">
    </div>
    <div>
        <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
        <div class="relative">
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-12 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="Minimal 12 karakter">
            <button id="toggle-password-visibility" type="button" class="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-xl text-slate-500 transition hover:text-emerald-800 focus:outline-none focus:ring-4 focus:ring-inset focus:ring-emerald-700/20" aria-label="Show password" aria-pressed="false" aria-controls="password" title="Show password">
                <svg id="password-eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                    <circle cx="12" cy="12" r="2.75" />
                </svg>
                <svg id="password-eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="hidden h-5 w-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.08A10.8 10.8 0 0 1 12 4.88c6.25 0 9.75 7.12 9.75 7.12a16.2 16.2 0 0 1-3.08 3.8M6.23 6.23C3.67 8.03 2.25 12 2.25 12s3.5 7.12 9.75 7.12a10.6 10.6 0 0 0 4.08-.82" />
                </svg>
            </button>
        </div>
    </div>
    <div>
        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Konfirmasi password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="Ulangi password">
    </div>
    <div id="registration-error" role="alert" aria-live="polite" class="hidden rounded-xl px-4 py-3 text-sm"></div>
    <button id="registration-submit" type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101b2d] px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-400/30 disabled:cursor-wait disabled:opacity-60">
        <span>Daftar dan minta akses</span><span aria-hidden="true">→</span>
    </button>
    <p class="text-center text-xs leading-5 text-slate-400">Password disimpan secara aman dan tidak dapat dibaca kembali.</p>
</form>

<p id="login-link" class="mt-6 text-center text-sm text-slate-500">Sudah punya akun? <a href="/login" class="font-semibold text-emerald-800 hover:text-emerald-950">Masuk</a></p>

<script>
    const registrationForm = document.getElementById('registration-form');
    const registrationError = document.getElementById('registration-error');
    const registrationSubmit = document.getElementById('registration-submit');
    const propertySelect = document.getElementById('property_id');
    const departmentSelect = document.getElementById('department_id');
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordVisibilityButton = document.getElementById('toggle-password-visibility');
    let registrationProperties = [];

    function updatePasswordVisibilityLabel(language = localStorage.getItem('hotel_task_language')) {
        const isPasswordVisible = passwordInput.type === 'text';
        const label = language === 'id'
            ? (isPasswordVisible ? 'Sembunyikan password' : 'Tampilkan password')
            : (isPasswordVisible ? 'Hide password' : 'Show password');

        passwordVisibilityButton.setAttribute('aria-label', label);
        passwordVisibilityButton.setAttribute('title', label);
    }

    passwordVisibilityButton.addEventListener('click', () => {
        const isPasswordVisible = passwordInput.type === 'text';

        passwordInput.type = isPasswordVisible ? 'password' : 'text';
        passwordVisibilityButton.setAttribute('aria-pressed', String(!isPasswordVisible));
        document.getElementById('password-eye-open').classList.toggle('hidden', !isPasswordVisible);
        document.getElementById('password-eye-closed').classList.toggle('hidden', isPasswordVisible);
        updatePasswordVisibilityLabel();
    });

    window.addEventListener('hotel-task-language-change', (event) => updatePasswordVisibilityLabel(event.detail?.language));

    ['copy', 'cut', 'paste'].forEach((eventName) => {
        passwordConfirmationInput.addEventListener(eventName, (event) => event.preventDefault());
    });

    function updateRegistrationDepartments() {
        const selectedProperty = registrationProperties.find(property => property.id === propertySelect.value);
        departmentSelect.replaceChildren(new Option(selectedProperty ? 'Pilih departemen' : 'Pilih properti terlebih dahulu', ''));
        (selectedProperty?.departments || []).forEach(department => {
            departmentSelect.add(new Option(department.name, department.id));
        });
        departmentSelect.disabled = !selectedProperty || selectedProperty.departments.length === 0;
        if (selectedProperty && selectedProperty.departments.length === 0) {
            departmentSelect.replaceChildren(new Option('Belum ada departemen', ''));
        }
    }

    propertySelect.addEventListener('change', updateRegistrationDepartments);
    fetch('/api/v1/registration-options', { headers: { 'Accept': 'application/json' } })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Daftar properti tidak dapat dimuat.');
            registrationProperties = data.data || [];
            propertySelect.replaceChildren(new Option('Pilih properti', ''));
            registrationProperties.forEach(property => propertySelect.add(new Option(property.name, property.id)));
            propertySelect.disabled = registrationProperties.length === 0;
            if (registrationProperties.length === 0) propertySelect.replaceChildren(new Option('Belum ada properti tersedia', ''));
            updateRegistrationDepartments();
        })
        .catch(error => {
            propertySelect.replaceChildren(new Option('Tidak dapat memuat properti', ''));
            registrationError.textContent = error.message;
            registrationError.className = 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
        });

    registrationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        registrationError.className = 'hidden rounded-xl px-4 py-3 text-sm';
        registrationSubmit.disabled = true;
        registrationSubmit.querySelector('span').textContent = 'Mengirim pendaftaran…';
        const values = new FormData(registrationForm);
        try {
            const response = await fetch('/api/v1/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: values.get('name'), email: values.get('email'),
                    property_id: values.get('property_id'), department_id: values.get('department_id'),
                    position: values.get('position'),
                    password: values.get('password'), password_confirmation: values.get('password_confirmation')
                })
            });
            const data = await response.json();
            if (!response.ok) {
                const errors = Object.values(data.errors || {}).flat();
                throw new Error(errors[0] || data.message || 'Pendaftaran gagal. Periksa kembali data Anda.');
            }
            document.getElementById('registration-success-message').textContent = data.message;
            document.getElementById('registration-success').classList.remove('hidden');
            registrationForm.classList.add('hidden');
            document.getElementById('login-link').classList.add('hidden');
        } catch (error) {
            registrationError.textContent = error.message || 'Tidak dapat menghubungi server.';
            registrationError.className = 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
        } finally {
            registrationSubmit.disabled = false;
            registrationSubmit.querySelector('span').textContent = 'Daftar dan minta akses';
        }
    });
</script>
@endsection
