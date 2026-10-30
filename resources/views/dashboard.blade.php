<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workspace · HotelTask</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-8">
            <a href="/dashboardpublic" class="flex items-center gap-3 text-lg font-semibold tracking-tight">
                <span class="grid size-10 place-items-center rounded-xl bg-[#101b2d] text-[#a3d7c4]">H</span>
                <span>HotelTask</span>
            </a>
            <div class="relative">
                <button id="workspace-profile-toggle" type="button" aria-haspopup="true" aria-controls="workspace-profile-menu" aria-expanded="false" class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                    <span id="workspace-user-avatar" class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-900" aria-hidden="true">HT</span>
                    <span class="hidden min-w-0 text-right sm:block"><span id="workspace-user-name" class="block max-w-40 truncate text-sm font-semibold text-slate-800">{{ $workspaceUser->name }}</span><span id="workspace-user-role" class="mt-0.5 block truncate text-xs text-slate-500">{{ $workspaceUser->role }}</span></span>
                    <span aria-hidden="true" class="text-xs text-slate-400">▾</span>
                </button>
                <div id="workspace-profile-menu" class="absolute right-0 top-full z-30 mt-2 hidden w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl" role="group" aria-label="Profile settings">
                    <p class="mb-3 border-b border-slate-100 px-2 pb-3 text-sm font-semibold text-slate-800" data-language-id="Pengaturan profil" data-language-en="Profile settings">Profile settings</p>
                    <label for="workspace-language" class="mb-1.5 block text-xs font-medium text-slate-500" data-language-id="Bahasa" data-language-en="Language">Language</label>
                    <select id="workspace-language" class="mb-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-emerald-700" aria-label="Language">
                        <option value="en">English</option>
                        <option value="id">Bahasa Indonesia</option>
                    </select>
                    <button id="workspace-dark-mode-toggle" type="button" role="switch" aria-checked="false" class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <span data-language-id="Mode gelap" data-language-en="Dark mode">Dark mode</span>
                        <span id="workspace-dark-mode-state" class="text-xs text-slate-400">Off</span>
                    </button>
                    <button id="workspace-logout" type="button" class="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-rose-700 hover:bg-rose-50">
                        <span aria-hidden="true">↪</span><span data-language-id="Keluar / Logout" data-language-en="Log out">Log out</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-8 px-4 py-7 sm:px-8 sm:py-10">
        <section class="rounded-3xl bg-[#101b2d] px-6 py-8 text-white shadow-sm sm:px-9 sm:py-10">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-200" data-language-id="Ruang kerja Anda" data-language-en="Your workspace">Your workspace</p>
            <h1 id="workspace-heading" class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl" data-language-id="Selamat datang" data-language-en="Welcome">Welcome, {{ $workspaceUser->name }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300" data-language-id="Akses tugas dan laporan untuk properti dan departemen Anda." data-language-en="Access tasks and reports for your property and department.">Access tasks and reports for your property and department.</p>
            <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-300">
                <span id="workspace-property" class="hidden"></span>
                <span id="workspace-department" class="hidden"></span>
            </div>
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold" data-language-id="Ruang kerja" data-language-en="Workspace">Workspace</h2>
                <p class="mt-1 text-sm text-slate-500" data-language-id="Pilih fitur yang tersedia untuk akun Anda." data-language-en="Choose a feature available to your account.">Choose a feature available to your account.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <a id="workspace-tasks-card" href="/tasks" class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-lg text-emerald-800" aria-hidden="true">✓</span>
                    <h3 class="mt-4 font-semibold" data-language-id="Tugas" data-language-en="Tasks">Tasks</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500" data-language-id="Lihat tugas yang dapat Anda akses dan kirim pembaruan progress." data-language-en="View tasks you can access and update their progress.">View tasks you can access and update their progress.</p>
                </a>
                <a id="workspace-reports-card" href="/reports" class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <span class="grid size-11 place-items-center rounded-xl bg-violet-50 text-lg text-violet-800" aria-hidden="true">▥</span>
                    <h3 class="mt-4 font-semibold" data-language-id="Laporan" data-language-en="Reports">Reports</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500" data-language-id="Tinjau kinerja dan penyelesaian tugas." data-language-en="Review task performance and completion.">Review task performance and completion.</p>
                </a>
                <a id="workspace-approvals-card" href="/approvals" class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
                    <span class="grid size-11 place-items-center rounded-xl bg-amber-50 text-lg text-amber-800" aria-hidden="true">◷</span>
                    <h3 class="mt-4 font-semibold" data-language-id="Persetujuan" data-language-en="Approvals">Approvals</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500" data-language-id="Tinjau pendaftaran pengguna yang menunggu persetujuan." data-language-en="Review user registrations awaiting approval.">Review user registrations awaiting approval.</p>
                </a>
                @if ($workspaceUser->role === 'IT')
                    <a id="workspace-it-admin-card" href="/dashboardadmin" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
                        <span class="grid size-11 place-items-center rounded-xl bg-slate-100 text-lg text-slate-800" aria-hidden="true">⚙</span>
                        <h3 class="mt-4 font-semibold" data-language-id="Administrasi IT" data-language-en="IT administration">IT administration</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500" data-language-id="Buka area administrasi sesuai hak akses IT Anda." data-language-en="Open the administration area granted to your IT account.">Open the administration area granted to your IT account.</p>
                    </a>
                @endif
                <p id="workspace-empty" class="hidden rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500" data-language-id="Belum ada fitur yang tersedia untuk akun ini." data-language-en="No workspace tools are available for this account yet.">No workspace tools are available for this account yet.</p>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const token = sessionStorage.getItem('hotel_task_access_token');
            const languageSelect = document.getElementById('workspace-language');
            const profileToggle = document.getElementById('workspace-profile-toggle');
            const profileMenu = document.getElementById('workspace-profile-menu');
            const darkModeToggle = document.getElementById('workspace-dark-mode-toggle');
            const darkModeState = document.getElementById('workspace-dark-mode-state');
            const cards = {
                tasks: document.getElementById('workspace-tasks-card'),
                reports: document.getElementById('workspace-reports-card'),
                approvals: document.getElementById('workspace-approvals-card'),
            };

            if (!token) {
                window.location.replace('/login');
                return;
            }

            const applyLanguage = selectedLanguage => {
                const languageCode = selectedLanguage === 'id' ? 'id' : 'en';
                document.documentElement.lang = languageCode;
                languageSelect.value = languageCode;
                document.querySelectorAll('[data-language-id][data-language-en]').forEach(element => {
                    element.textContent = element.dataset[`language${languageCode === 'en' ? 'En' : 'Id'}`];
                });
                localStorage.setItem('hotel_task_language', languageCode);
                window.dispatchEvent(new CustomEvent('hotel-task-language-change', { detail: { language: languageCode } }));
            };

            const applyTheme = isDark => {
                document.documentElement.classList.toggle('dark', isDark);
                localStorage.setItem('hotel_task_theme', isDark ? 'dark' : 'light');
                darkModeToggle.setAttribute('aria-checked', String(isDark));
                darkModeState.textContent = isDark ? 'On' : 'Off';
                darkModeState.dataset.languageId = isDark ? 'Aktif' : 'Nonaktif';
                darkModeState.dataset.languageEn = isDark ? 'On' : 'Off';
                applyLanguage(languageSelect.value);
            };

            const closeProfileMenu = () => {
                profileMenu.classList.add('hidden');
                profileToggle.setAttribute('aria-expanded', 'false');
            };

            document.documentElement.classList.toggle('dark', localStorage.getItem('hotel_task_theme') === 'dark');

            applyLanguage(localStorage.getItem('hotel_task_language') || 'en');
            applyTheme(localStorage.getItem('hotel_task_theme') === 'dark');
            languageSelect.addEventListener('change', () => applyLanguage(languageSelect.value));
            profileToggle.addEventListener('click', () => {
                const isOpen = profileMenu.classList.toggle('hidden') === false;
                profileToggle.setAttribute('aria-expanded', String(isOpen));
            });
            darkModeToggle.addEventListener('click', () => applyTheme(!document.documentElement.classList.contains('dark')));
            document.addEventListener('click', event => {
                if (!profileMenu.contains(event.target) && !profileToggle.contains(event.target)) closeProfileMenu();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeProfileMenu();
            });

            fetch('/api/v1/users/me', {
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            }).then(async response => {
                if (response.status === 401 || response.status === 403) {
                    sessionStorage.removeItem('hotel_task_access_token');
                    sessionStorage.removeItem('hotel_task_user');
                    window.location.replace('/login');
                    return null;
                }
                if (!response.ok) throw new Error('Unable to load workspace access.');

                return response.json();
            }).then(result => {
                if (!result?.data) return;

                const user = result.data;
                const privileges = user.privileges || [];
                const hasTaskAccess = ['tasks.view_all', 'tasks.manage', 'tasks.assign', 'tasks.update_assigned']
                    .some(privilege => privileges.includes(privilege));
                const userName = user.name || 'HotelTask User';
                document.getElementById('workspace-user-name').textContent = userName;
                document.getElementById('workspace-user-role').textContent = user.role || '';
                document.getElementById('workspace-user-avatar').textContent = userName.split(/\s+/).slice(0, 2).map(namePart => namePart.charAt(0)).join('').toUpperCase();
                const updateWorkspaceHeading = language => {
                    document.getElementById('workspace-heading').textContent = `${language === 'id' ? 'Halo' : 'Welcome'}, ${userName}`;
                };
                updateWorkspaceHeading(localStorage.getItem('hotel_task_language') || 'en');
                languageSelect.addEventListener('change', () => updateWorkspaceHeading(languageSelect.value));

                if (user.property?.name) {
                    const propertyLabel = document.getElementById('workspace-property');
                    propertyLabel.textContent = user.property.name;
                    propertyLabel.classList.remove('hidden');
                }
                if (user.default_department?.name) {
                    const departmentLabel = document.getElementById('workspace-department');
                    departmentLabel.textContent = user.default_department.name;
                    departmentLabel.classList.remove('hidden');
                }

                cards.tasks.classList.toggle('hidden', !hasTaskAccess);
                cards.reports.classList.toggle('hidden', !privileges.includes('reports.view'));
                cards.approvals.classList.toggle('hidden', !privileges.includes('users.approve'));
                const hasVisibleCard = Object.values(cards).some(card => !card.classList.contains('hidden'))
                    || Boolean(document.getElementById('workspace-it-admin-card'));
                document.getElementById('workspace-empty').classList.toggle('hidden', hasVisibleCard);
                sessionStorage.setItem('hotel_task_user', JSON.stringify(user));
            }).catch(() => {
                document.getElementById('workspace-empty').textContent = 'Workspace information could not be loaded. Please refresh the page.';
                document.getElementById('workspace-empty').classList.remove('hidden');
            });

            document.getElementById('workspace-logout').addEventListener('click', async () => {
                try {
                    await fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
                    });
                } catch (_) {}
                sessionStorage.removeItem('hotel_task_access_token');
                sessionStorage.removeItem('hotel_task_user');
                window.location.assign('/login');
            });
        })();
    </script>
</body>
</html>
