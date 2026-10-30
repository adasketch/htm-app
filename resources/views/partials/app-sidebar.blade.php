<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[calc(100vw-2rem)] -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:w-64 lg:translate-x-0">
    <div class="flex h-20 items-center justify-between border-b border-slate-100 px-5">
        <a href="/dashboardpublic" class="flex items-center gap-3 text-lg font-semibold tracking-tight"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#101b2d] text-[#a3d7c4]">H</span><span data-sidebar-label>HotelTask</span></a>
        <button type="button" data-sidebar-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Tutup menu" title="Tutup menu"><span aria-hidden="true">×</span></button>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @if (request()->user()?->role !== 'Admin')
            <a data-sidebar-link id="sidebar-dashboard-link" href="/dashboardpublic" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"><span class="w-5 text-center text-base" aria-hidden="true">⌂</span><span data-sidebar-label data-language-id="Dashboard" data-language-en="Dashboard">Dashboard</span></a>
        @endif
        <a data-sidebar-link id="sidebar-tasks-link" href="/tasks" class="hidden items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"><span class="w-5 text-center text-base" aria-hidden="true">✓</span><span data-sidebar-label data-language-id="Tugas" data-language-en="Tasks">Tugas</span></a>
        <a data-sidebar-link id="sidebar-reports-link" href="/reports" class="hidden items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"><span class="w-5 text-center text-base" aria-hidden="true">▥</span><span data-sidebar-label data-language-id="Laporan" data-language-en="Reports">Laporan</span></a>
        <a data-sidebar-link id="sidebar-approvals-link" href="/approvals" class="hidden items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"><span class="w-5 text-center text-base" aria-hidden="true">◷</span><span data-sidebar-label data-language-id="Persetujuan" data-language-en="Approvals">Approvals</span></a>
        <a data-sidebar-link id="sidebar-users-link" href="/dashboardadmin/users" class="hidden items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"><span class="w-5 text-center text-base" aria-hidden="true">♙</span><span data-sidebar-label data-language-id="Pengguna" data-language-en="Users">Pengguna</span></a>
        <a data-sidebar-link id="sidebar-settings-link" href="/dashboardadmin/property-management" class="hidden items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"><span class="w-5 text-center text-base" aria-hidden="true">⚙</span><span data-sidebar-label data-language-id="Property Management" data-language-en="Property Management">Property Management</span></a>
    </nav>
    <div class="border-t border-slate-100 p-3 sm:p-4">
        <div class="relative">
            <div id="profile-menu" class="absolute bottom-full left-0 z-50 mb-2 hidden max-h-[calc(100dvh-6rem)] w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 shadow-xl" role="group" aria-label="Pengaturan profil">
                <label for="language-select" class="mb-1.5 block text-xs font-medium text-slate-500">Language</label>
                <select id="language-select" class="mb-3 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-emerald-700" aria-label="Language">
                    <option value="en">English</option>
                    <option value="id">Bahasa Indonesia</option>
                </select>
                <button id="dark-mode-toggle" type="button" role="switch" aria-checked="false" class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                    <span data-language-id="Mode gelap" data-language-en="Dark mode">Mode gelap</span>
                    <span id="dark-mode-state" class="text-xs text-slate-400">Nonaktif</span>
                </button>
                <button id="sidebar-logout" type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-rose-700 hover:bg-rose-50">
                    <span aria-hidden="true">↪</span><span data-language-id="Keluar / Logout" data-language-en="Log out">Keluar / Logout</span>
                </button>
            </div>
            <button id="profile-menu-toggle" type="button" aria-haspopup="true" aria-controls="profile-menu" aria-expanded="false" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                <span id="sidebar-user-avatar" class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-900" aria-hidden="true">HT</span>
                <span class="min-w-0 flex-1"><span id="sidebar-user-name" class="block truncate text-sm font-semibold text-slate-800">HotelTask User</span><span id="sidebar-user-role" class="mt-0.5 block truncate text-xs text-slate-400"></span></span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌃</span>
            </button>
        </div>
    </div>
</aside>
<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden"></div>
<div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-5 lg:hidden">
    <a href="/dashboardpublic" class="flex items-center gap-3 text-base font-semibold"><span class="grid size-9 place-items-center rounded-xl bg-[#101b2d] text-[#a3d7c4]">H</span>HotelTask</a>
    <button type="button" data-sidebar-toggle class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700" aria-label="Buka menu" aria-expanded="false">Menu</button>
</div>
<button id="sidebar-desktop-open" type="button" class="fixed left-4 top-4 z-30 hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-md hover:bg-slate-50" aria-label="Buka menu samping">☰ Menu</button>
<script>
    (() => {
        const sidebarToken = sessionStorage.getItem('hotel_task_access_token');
        const sidebar = document.getElementById('app-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const desktopOpen = document.getElementById('sidebar-desktop-open');
        const profileToggle = document.getElementById('profile-menu-toggle');
        const profileMenu = document.getElementById('profile-menu');
        const languageSelect = document.getElementById('language-select');
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const darkModeState = document.getElementById('dark-mode-state');
        const language = localStorage.getItem('hotel_task_language') || 'en';
        let sidebarUser = null;

        try {
            sidebarUser = JSON.parse(sessionStorage.getItem('hotel_task_user') || 'null');
        } catch (_) {
            sessionStorage.removeItem('hotel_task_user');
        }

        const applyLanguage = selectedLanguage => {
            const languageCode = selectedLanguage === 'en' ? 'en' : 'id';
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
            darkModeState.textContent = isDark ? 'Aktif' : 'Nonaktif';
            darkModeState.dataset.languageId = isDark ? 'Aktif' : 'Nonaktif';
            darkModeState.dataset.languageEn = isDark ? 'On' : 'Off';
            applyLanguage(languageSelect.value);
        };

        const renderSidebarUser = user => {
            sidebarUser = user;
            if (user) {
                sessionStorage.setItem('hotel_task_user', JSON.stringify(user));
            }

            const privileges = user?.privileges || [];
            const canOpenTasks = privileges.some(privilege => ['tasks.view_all', 'tasks.manage', 'tasks.assign', 'tasks.update_assigned'].includes(privilege));
            document.getElementById('sidebar-tasks-link').classList.toggle('hidden', !canOpenTasks);
            document.getElementById('sidebar-tasks-link').classList.toggle('flex', canOpenTasks);
            const hasAdminDashboardAccess = ['Admin', 'IT'].includes(user?.role);
            document.getElementById('sidebar-tasks-link').setAttribute('href', hasAdminDashboardAccess ? '/dashboardadmin/tasks' : '/tasks');
            document.getElementById('sidebar-reports-link').setAttribute('href', hasAdminDashboardAccess ? '/dashboardadmin/reports' : '/reports');
            const canOpenReports = privileges.includes('reports.view');
            document.getElementById('sidebar-reports-link').classList.toggle('hidden', !canOpenReports);
            document.getElementById('sidebar-reports-link').classList.toggle('flex', canOpenReports);
            const canOpenAdminUsers = hasAdminDashboardAccess && privileges.includes('users.view');
            document.getElementById('sidebar-users-link').classList.toggle('hidden', !canOpenAdminUsers);
            document.getElementById('sidebar-users-link').classList.toggle('flex', canOpenAdminUsers);
            const canOpenApprovals = !hasAdminDashboardAccess && privileges.includes('users.approve');
            document.getElementById('sidebar-approvals-link').classList.toggle('hidden', !canOpenApprovals);
            document.getElementById('sidebar-approvals-link').classList.toggle('flex', canOpenApprovals);
            const canOpenPropertySettings = hasAdminDashboardAccess && privileges.some(privilege => ['properties.manage', 'departments.manage'].includes(privilege));
            document.getElementById('sidebar-settings-link').classList.toggle('hidden', !canOpenPropertySettings);
            document.getElementById('sidebar-settings-link').classList.toggle('flex', canOpenPropertySettings);

            const userName = user?.name || 'HotelTask User';
            document.getElementById('sidebar-user-name').textContent = userName;
            document.getElementById('sidebar-user-role').textContent = user?.role === 'Admin' ? 'System Admin' : (user?.role || '');
            document.getElementById('sidebar-user-avatar').textContent = userName.split(/\s+/).slice(0, 2).map(namePart => namePart.charAt(0)).join('').toUpperCase();
        };

        const openMobileSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.querySelector('[data-sidebar-toggle]')?.setAttribute('aria-expanded', 'true');
        };

        const closeSidebar = () => {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                document.documentElement.classList.add('sidebar-collapsed');
                desktopOpen.classList.remove('hidden');
                desktopOpen.classList.add('inline-flex');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.querySelector('[data-sidebar-toggle]')?.setAttribute('aria-expanded', 'false');
            }
        };

        const closeProfileMenu = () => {
            profileMenu.classList.add('hidden');
            profileToggle.setAttribute('aria-expanded', 'false');
        };

        document.documentElement.classList.toggle('dark', localStorage.getItem('hotel_task_theme') === 'dark');
        applyLanguage(language);
        applyTheme(localStorage.getItem('hotel_task_theme') === 'dark');
        renderSidebarUser(sidebarUser);

        document.querySelectorAll('[data-sidebar-link]').forEach(link => {
            if (window.location.pathname === link.getAttribute('href')) {
                link.classList.remove('text-slate-600', 'hover:bg-slate-100');
                link.classList.add('bg-[#101b2d]', 'text-white');
            }
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });

        window.hotelTaskUserPromise = sidebarToken
            ? fetch('/api/v1/users/me', { headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${sidebarToken}` } })
                .then(response => response.ok ? response.json() : null)
                .then(data => {
                    if (data?.data) renderSidebarUser(data.data);
                    return data?.data || null;
                })
                .catch(() => null)
            : Promise.resolve(sidebarUser);

        document.querySelectorAll('[data-sidebar-toggle]').forEach(button => button.addEventListener('click', openMobileSidebar));
        document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', closeSidebar));
        desktopOpen.addEventListener('click', () => {
            document.documentElement.classList.remove('sidebar-collapsed');
            desktopOpen.classList.add('hidden');
            desktopOpen.classList.remove('inline-flex');
        });
        overlay.addEventListener('click', closeSidebar);
        profileToggle.addEventListener('click', () => {
            const isOpen = profileMenu.classList.toggle('hidden') === false;
            profileToggle.setAttribute('aria-expanded', String(isOpen));
        });
        languageSelect.addEventListener('change', () => applyLanguage(languageSelect.value));
        darkModeToggle.addEventListener('click', () => applyTheme(!document.documentElement.classList.contains('dark')));
        document.addEventListener('click', event => {
            if (!profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
                closeProfileMenu();
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeProfileMenu();
                if (!overlay.classList.contains('hidden')) {
                    closeSidebar();
                }
            }
        });

        document.getElementById('sidebar-logout').addEventListener('click', async () => {
            if (sidebarToken) {
                try {
                    await fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${sidebarToken}` },
                    });
                } catch (_) {}
            }
            sessionStorage.removeItem('hotel_task_access_token');
            sessionStorage.removeItem('hotel_task_user');
            window.location.assign('/login');
        });
    })();
</script>
