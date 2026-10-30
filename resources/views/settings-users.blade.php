<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengguna · HotelTask</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-slate-900 antialiased">
    @include('partials.app-sidebar')
    <div data-app-content class="min-h-screen transition-[padding] duration-200 lg:pl-64">
    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <div class="mb-8"><p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Administrasi tim</p><h1 id="users-page-heading" class="mt-2 text-3xl font-semibold tracking-tight">Pengguna</h1><p id="users-page-description" class="mt-2 text-sm text-slate-500">Lihat anggota berdasarkan properti dan departemen, lalu perbarui profil atau akses sesuai wewenang Anda.</p></div>
        <div id="notice" role="status" aria-live="polite" class="mb-6 hidden rounded-xl px-4 py-3 text-sm"></div>
        <section id="approvals-section" class="mb-8 hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50 sm:p-6">
            <div class="mb-5"><h2 class="font-semibold">Pendaftaran menunggu persetujuan</h2><p class="mt-1 text-sm text-slate-500">Pilih properti, departemen, dan role untuk setiap calon pengguna.</p></div>
            <p id="approvals-empty" class="hidden py-5 text-sm text-slate-400">Tidak ada pendaftaran yang menunggu persetujuan.</p>
            <div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead><tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400"><th class="px-3 py-3 font-medium">Calon pengguna</th><th class="px-3 py-3 font-medium">Properti</th><th class="px-3 py-3 font-medium">Departemen</th><th class="px-3 py-3 font-medium">Role</th><th class="px-3 py-3 text-right font-medium">Aksi</th></tr></thead><tbody id="approvals-table"></tbody></table></div>
            <div id="approvals-pagination" class="mt-4 flex items-center justify-between text-xs text-slate-500"></div>
        </section>
        <section id="roles-section" class="mb-8 hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50 sm:p-6">
            <div class="mb-5"><h2 class="font-semibold">Privilege role</h2><p class="mt-1 text-sm text-slate-500">Atur hak akses yang berlaku untuk semua pengguna dengan role tersebut. Akses task dibatasi ke departemen yang diberikan kepada setiap pengguna.</p></div>
            <div id="roles-list" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"><p class="text-sm text-slate-400">Memuat role…</p></div>
        </section>
        <section id="user-management-section" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50 sm:p-6">
            <div class="mb-5 grid gap-4 md:grid-cols-2">
                <div id="property-filter-wrap"><label for="property-filter" class="mb-2 block text-xs font-medium text-slate-600">Properti</label><select id="property-filter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></select></div>
                <div><label for="department-filter" class="mb-2 block text-xs font-medium text-slate-600">Departemen</label><select id="department-filter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700"><option value="">Semua departemen</option></select></div>
            </div>
            <div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead><tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400"><th class="px-3 py-3 font-medium">Nama</th><th class="px-3 py-3 font-medium">Email</th><th class="px-3 py-3 font-medium">Departemen</th><th class="px-3 py-3 font-medium">Job Role</th><th class="px-3 py-3 text-right font-medium">Aksi</th></tr></thead><tbody id="users-table"><tr><td colspan="5" class="px-3 py-8 text-center text-slate-400">Memuat pengguna…</td></tr></tbody></table></div>
            <div id="pagination" class="mt-4 flex items-center justify-between text-xs text-slate-500"></div>
        </section>
    </main>
    </div>

    <div id="edit-modal" class="fixed inset-0 z-20 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="edit-title">
        <section class="my-auto max-h-[calc(100dvh-1rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-4 shadow-xl sm:max-h-[calc(100dvh-2rem)] sm:p-6">
            <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Kelola akun</p><h2 id="edit-title" class="mt-1 text-xl font-semibold">Edit pengguna</h2></div><button id="close-modal" type="button" class="rounded-lg px-3 py-1 text-xl text-slate-400 hover:bg-slate-100" aria-label="Tutup">×</button></div>
            <form id="edit-form" class="mt-6 space-y-4">
                <input type="hidden" name="id">
                <div><label class="mb-1.5 block text-sm font-medium text-slate-700" for="edit-name">Nama</label><input id="edit-name" name="name" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></div>
                <div><label class="mb-1.5 block text-sm font-medium text-slate-700" for="edit-email">Email</label><input id="edit-email" name="email" type="email" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></div>
                <div id="edit-password-fields" class="hidden grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 sm:p-4">
                    <label class="block text-sm font-medium text-slate-700" for="edit-password">Password baru <span class="font-normal text-slate-400">(opsional)</span><input id="edit-password" name="password" type="password" minlength="12" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></label>
                    <label class="block text-sm font-medium text-slate-700" for="edit-password-confirmation">Konfirmasi password<input id="edit-password-confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></label>
                    <p class="text-xs text-slate-500 sm:col-span-2">Kosongkan kedua kolom agar password tidak berubah. Password baru minimal 12 karakter; sesi aktif pengguna akan dikeluarkan setelah password diganti.</p>
                </div>
                <div><label class="mb-1.5 block text-sm font-medium text-slate-700" for="edit-position">Jabatan / posisi</label><input id="edit-position" name="position" maxlength="255" placeholder="Contoh: Front Office Supervisor" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-emerald-700"><p class="mt-1 text-xs text-slate-400">Jabatan menjelaskan pekerjaan; role privilege menentukan akses di aplikasi.</p></div>
                <div id="role-field"><label class="mb-1.5 block text-sm font-medium text-slate-700" for="edit-role">Role privilege</label><select id="edit-role" name="role" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></select></div>
                <fieldset class="min-w-0"><legend class="mb-1.5 block text-sm font-medium text-slate-700">Akses departemen</legend><div id="edit-departments" class="max-h-48 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 sm:max-h-56"></div><p class="mt-1 text-xs text-slate-400">Pilih satu atau beberapa departemen. Departemen default akan otomatis ikut dipilih.</p></fieldset>
                <div><label class="mb-1.5 block text-sm font-medium text-slate-700" for="edit-default-department">Departemen default</label><select id="edit-default-department" name="default_department_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700"></select></div>
                <div id="edit-error" role="alert" class="hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800"></div>
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end sm:gap-3"><button id="cancel-edit" type="button" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 sm:w-auto">Batal</button><button id="save-edit" type="submit" class="w-full rounded-xl bg-[#101b2d] px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 sm:w-auto">Simpan perubahan</button></div>
            </form>
        </section>
    </div>

    <script>
        const token = sessionStorage.getItem('hotel_task_access_token');
        const actor = JSON.parse(sessionStorage.getItem('hotel_task_user') || 'null');
        const privileges = actor?.privileges || [];
        const canManage = privileges.includes('users.manage');
        const canManagePasswordsAndDelete = canManage && ['Admin', 'IT'].includes(actor?.role);
        const canManageRolePrivileges = actor?.role === 'Admin' && privileges.includes('roles.manage');
        const canApprove = privileges.includes('users.approve');
        const canSeeAllProperties = privileges.includes('properties.view_all');
        const isApprovalsOnlyPage = window.location.pathname === '/approvals';
        const propertySelect = document.getElementById('property-filter');
        const departmentSelect = document.getElementById('department-filter');
        const table = document.getElementById('users-table');
        const modal = document.getElementById('edit-modal');
        const editForm = document.getElementById('edit-form');
        const notice = document.getElementById('notice');
        let properties = [];
        let departments = [];
        let users = [];
        let roleSettings = null;
        let currentPage = 1;
        let approvalsPage = 1;

        const frontlineRoles = ['Associate', 'Vendor', 'Daily Worker', 'Casual'];
        const roleLabels = { 'Daily Worker': 'Daily Worker (DW)' };
        const hierarchy = { Associate: 1, Vendor: 1, 'Daily Worker': 1, Casual: 1, 'Department Leader': 2, Manager: 3, GM: 4, IT: 5, Admin: 6 };
        const roleOptions = actor?.role === 'Admin'
            ? ['Admin', 'IT', 'GM', 'Manager', 'Department Leader', ...frontlineRoles]
            : actor?.role === 'IT' ? ['GM', 'Manager', 'Department Leader', ...frontlineRoles]
            : actor?.role === 'GM' ? ['Manager', 'Department Leader', ...frontlineRoles]
            : actor?.role === 'Manager' ? ['Department Leader', ...frontlineRoles]
            : actor?.role === 'Department Leader' ? frontlineRoles : [];
        const approvalRoleOptions = actor?.role === 'Admin'
            ? [...frontlineRoles, 'Department Leader', 'Manager', 'GM', 'IT']
            : actor?.role === 'IT' ? [...frontlineRoles, 'Department Leader', 'Manager', 'GM']
            : actor?.role === 'GM' ? [...frontlineRoles, 'Department Leader', 'Manager']
            : actor?.role === 'Manager' ? [...frontlineRoles, 'Department Leader']
            : actor?.role === 'Department Leader' ? frontlineRoles : ['Associate'];

        if (!token) window.location.replace('/login');
        else if (isApprovalsOnlyPage ? !canApprove : !privileges.includes('users.view')) {
            showNotice('Akun Anda tidak memiliki akses untuk melihat pengguna.');
        } else {
            if (isApprovalsOnlyPage) {
                document.getElementById('users-page-heading').textContent = 'Approvals';
                document.getElementById('users-page-description').textContent = 'Review registrations assigned to your approval scope.';
                document.getElementById('user-management-section').classList.add('hidden');
                document.getElementById('roles-section').classList.add('hidden');
                initializeApprovalsOnly();
            } else {
                if (actor?.role === 'Department Leader') document.getElementById('property-filter-wrap').classList.add('hidden');
                initialize();
            }
        }

        async function api(path, options = {}) {
            const response = await fetch(path, { ...options, headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}`, ...(options.body ? { 'Content-Type': 'application/json' } : {}), ...options.headers } });
            const data = await response.json().catch(() => ({}));
            if (response.status === 401) { sessionStorage.clear(); window.location.replace('/login'); throw new Error('Sesi berakhir. Silakan masuk kembali.'); }
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Permintaan tidak dapat diproses.');
            return data;
        }

        function showNotice(message, success = false) {
            notice.textContent = message;
            notice.className = success ? 'mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900' : 'mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
        }

        function option(label, value) { const item = document.createElement('option'); item.value = value; item.textContent = label; return item; }

        async function initialize() {
            try {
                const response = await api('/api/v1/properties');
                properties = response.data || [];
                propertySelect.replaceChildren(...properties.map(property => option(property.name, property.id)));
                if (actor?.property_id && properties.some(property => property.id === actor.property_id)) propertySelect.value = actor.property_id;
                propertySelect.addEventListener('change', () => { currentPage = 1; loadDepartments().then(loadUsers); });
                departmentSelect.addEventListener('change', () => { currentPage = 1; loadUsers(); });
                await loadDepartments();
                await loadUsers();
                if (canApprove) await loadPendingApprovals();
                if (canManageRolePrivileges) {
                    document.getElementById('roles-section').classList.remove('hidden');
                    await loadRoleSettings();
                }
            } catch (error) { showNotice(error.message); }
        }

        async function initializeApprovalsOnly() {
            try {
                const response = await api('/api/v1/properties');
                properties = response.data || [];
                await loadPendingApprovals();
            } catch (error) {
                showNotice(error.message);
            }
        }

        async function loadRoleSettings() {
            try {
                roleSettings = await api('/api/v1/roles');
                renderRoleCards();
            } catch (error) {
                document.getElementById('roles-list').textContent = error.message;
            }
        }

        const privilegeLabels = {
            'properties.view_all': 'Lihat semua properti', 'properties.manage': 'Kelola properti',
            'departments.manage': 'Kelola departemen', 'users.view': 'Lihat pengguna',
            'users.view_all': 'Lihat pengguna lintas properti', 'users.approve': 'Setujui pendaftaran',
            'users.assign_roles': 'Tetapkan role pengguna', 'users.manage': 'Edit pengguna',
            'users.manage_departments': 'Kelola akses departemen', 'roles.manage': 'Kelola privilege role',
            'tasks.assign': 'Buat dan tugaskan task', 'tasks.manage': 'Kelola task di departemen berakses',
            'tasks.view_all': 'Lihat seluruh task di departemen berakses',
            'tasks.update_assigned': 'Perbarui task yang ditugaskan', 'tasks.comment': 'Komentari task',
            'subtasks.manage_assigned': 'Kelola subtask pada task yang ditugaskan', 'reports.view': 'Lihat laporan'
        };

        const roleDescriptions = {
            Admin: { en: 'System-wide account and configuration control. Task management privileges are restricted.', id: 'Mengelola akun dan konfigurasi seluruh sistem. Hak untuk mengelola task dibatasi.' },
            IT: { en: 'Supports users, tasks, and administrative tools across properties.', id: 'Mengelola pengguna, task, dan alat administrasi lintas properti.' },
            GM: { en: 'Oversees property operations, departments, tasks, and reports.', id: 'Mengawasi operasional properti, departemen, task, dan laporan.' },
            Manager: { en: 'Manages department users and assigns or tracks team tasks.', id: 'Mengelola pengguna departemen serta menugaskan dan memantau task tim.' },
            'Department Leader': { en: 'Coordinates team members and tasks in assigned departments.', id: 'Mengoordinasikan anggota tim dan task di departemen yang diakses.' },
            Associate: { en: 'Updates assigned tasks, comments, and related subtasks.', id: 'Memperbarui task yang ditugaskan, komentar, dan subtask terkait.' },
            Vendor: { en: 'Updates assigned tasks, comments, and related subtasks.', id: 'Memperbarui task yang ditugaskan, komentar, dan subtask terkait.' },
            'Daily Worker': { en: 'Updates assigned tasks, comments, and related subtasks, like an Associate.', id: 'Memperbarui task yang ditugaskan, komentar, dan subtask seperti Associate.' },
            Casual: { en: 'Updates assigned tasks, comments, and related subtasks, like an Associate.', id: 'Memperbarui task yang ditugaskan, komentar, dan subtask seperti Associate.' },
        };

        function renderRoleCards() {
            const list = document.getElementById('roles-list');
            list.replaceChildren();

            roleSettings.roles.forEach(role => {
                const card = document.createElement('article');
                card.className = 'flex min-w-0 flex-col rounded-xl border border-slate-200 p-4';
                const heading = document.createElement('div');
                heading.className = 'mb-3 flex items-center justify-between gap-2';
                const title = document.createElement('h3');
                title.className = 'text-sm font-semibold text-slate-800';
                title.textContent = role.name;
                const badge = document.createElement('span');
                badge.className = 'shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-500';
                badge.textContent = role.key;
                heading.append(title, badge);

                const description = document.createElement('p');
                description.className = 'mb-3 min-h-10 text-xs leading-5 text-slate-500';
                const roleDescription = roleDescriptions[role.key] || { en: 'Access depends on the privileges selected below.', id: 'Akses ditentukan oleh privilege yang dipilih di bawah.' };
                const language = localStorage.getItem('hotel_task_language') === 'id' ? 'id' : 'en';
                description.textContent = roleDescription[language];

                const allowedPrivileges = roleSettings.assignable_privileges[role.key] || role.privileges;
                const controls = document.createElement('div');
                controls.className = 'hidden max-h-64 space-y-2 overflow-y-auto border-t border-slate-100 pt-3';

                const appendCheckboxes = (container, items) => {
                    items.forEach(privilege => {
                        const label = document.createElement('label');
                        label.className = `flex items-start gap-2 text-sm ${role.key === 'Admin' ? 'cursor-not-allowed text-slate-400' : 'cursor-pointer text-slate-600'}`;
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.value = privilege;
                        checkbox.checked = role.privileges.includes(privilege);
                        checkbox.disabled = role.key === 'Admin';
                        checkbox.className = 'mt-0.5 size-4 shrink-0 rounded border-slate-300 accent-emerald-700 focus:ring-emerald-700';
                        const text = document.createElement('span');
                        text.textContent = privilegeLabels[privilege] || privilege;
                        label.append(checkbox, text);
                        container.append(label);
                    });
                };

                appendCheckboxes(controls, allowedPrivileges);

                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'mb-3 text-xs font-semibold text-emerald-800 hover:text-emerald-900';
                toggle.textContent = 'Buka';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.addEventListener('click', () => {
                    const isOpen = controls.classList.toggle('hidden') === false;
                    toggle.textContent = isOpen ? 'Tutup' : 'Buka';
                    toggle.setAttribute('aria-expanded', String(isOpen));
                    save.classList.toggle('hidden', !isOpen);
                });

                card.append(heading, description, toggle, controls);
                const save = document.createElement('button');
                save.type = 'button';
                save.className = 'mt-auto w-full rounded-lg bg-[#101b2d] px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300';
                save.disabled = role.key === 'Admin';
                save.textContent = role.key === 'Admin' ? 'Role sistem terkunci' : 'Simpan perubahan';
                save.classList.add('hidden');
                if (role.key !== 'Admin') {
                    save.classList.add('mt-4');
                    save.classList.remove('mt-auto');
                    save.addEventListener('click', async () => {
                        save.disabled = true;
                        try {
                            const selected = [...controls.querySelectorAll('input[type="checkbox"]:checked')].map(input => input.value);
                            const response = await api(`/api/v1/roles/${encodeURIComponent(role.key)}/privileges`, {
                                method: 'PUT',
                                body: JSON.stringify({ privileges: selected })
                            });
                            role.privileges = response.data.privileges;
                            showNotice(`Privilege ${role.name} berhasil diperbarui.`, true);
                        } catch (error) {
                            showNotice(error.message);
                        } finally {
                            save.disabled = false;
                        }
                    });
                }
                card.append(save);
                list.append(card);
            });
        }

        async function loadPendingApprovals() {
            const section = document.getElementById('approvals-section');
            const body = document.getElementById('approvals-table');
            const empty = document.getElementById('approvals-empty');
            section.classList.remove('hidden');
            try {
                const response = await api(`/api/v1/users/pending-approvals?page=${approvalsPage}`);
                const pending = response.data || [];
                const meta = response.meta;
                if (meta && approvalsPage > meta.last_page) {
                    approvalsPage = Math.max(1, meta.last_page);
                    return loadPendingApprovals();
                }
                body.replaceChildren();
                empty.classList.toggle('hidden', pending.length > 0);
                pending.forEach(user => body.append(createApprovalRow(user)));
                const pagination = document.getElementById('approvals-pagination');
                pagination.replaceChildren();
                if (meta && meta.last_page > 1) {
                    const label = document.createElement('span'); label.textContent = `Halaman ${meta.current_page} dari ${meta.last_page} · ${meta.total} pendaftaran`;
                    const controls = document.createElement('div'); controls.className = 'flex gap-2';
                    const previous = document.createElement('button'); previous.type = 'button'; previous.textContent = 'Sebelumnya'; previous.disabled = meta.current_page <= 1; previous.className = 'rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40'; previous.addEventListener('click', () => { approvalsPage--; loadPendingApprovals(); });
                    const next = document.createElement('button'); next.type = 'button'; next.textContent = 'Berikutnya'; next.disabled = meta.current_page >= meta.last_page; next.className = 'rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40'; next.addEventListener('click', () => { approvalsPage++; loadPendingApprovals(); });
                    controls.append(previous, next); pagination.append(label, controls);
                } else if (meta) pagination.textContent = `${meta.total} pendaftaran`;
            } catch (error) { showNotice(error.message); }
        }

        function createApprovalRow(user) {
            const row = document.createElement('tr'); row.className = 'border-b border-slate-50 last:border-0 align-top';
            const applicant = document.createElement('td'); applicant.className = 'px-3 py-4';
            const name = document.createElement('p'); name.className = 'font-medium text-slate-800'; name.textContent = user.name;
            const email = document.createElement('p'); email.className = 'mt-1 text-xs text-slate-500'; email.textContent = user.email;
            const position = document.createElement('p'); position.className = 'mt-1 text-xs text-slate-600'; position.textContent = user.position || 'Jabatan belum diisi'; applicant.append(name, email, position);
            const propertyCell = document.createElement('td'); propertyCell.className = 'px-3 py-4';
            const property = document.createElement('select'); property.className = 'w-48 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs'; property.append(option('Pilih properti', ''));
            properties.forEach(item => property.append(option(item.name, item.id))); propertyCell.append(property);
            property.value = user.property?.id || '';
            const departmentCell = document.createElement('td'); departmentCell.className = 'px-3 py-4';
            const department = document.createElement('select'); department.className = 'w-44 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs'; department.disabled = true; department.append(option('Pilih departemen', '')); departmentCell.append(department);
            const roleCell = document.createElement('td'); roleCell.className = 'px-3 py-4';
            const role = document.createElement('select'); role.className = 'w-40 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs'; approvalRoleOptions.forEach(item => role.append(option(roleLabels[item] || item, item))); role.value = 'Associate'; roleCell.append(role);
            const actions = document.createElement('td'); actions.className = 'px-3 py-4 text-right whitespace-nowrap';
            const approve = document.createElement('button'); approve.type = 'button'; approve.textContent = 'Setujui'; approve.disabled = true; approve.className = 'rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40';
            const reject = document.createElement('button'); reject.type = 'button'; reject.textContent = 'Tolak'; reject.className = 'ml-2 rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700';
            property.addEventListener('change', async () => {
                const selectedPropertyId = property.value;
                department.disabled = true; department.replaceChildren(option('Memuat…', '')); approve.disabled = true;
                if (!selectedPropertyId) { department.replaceChildren(option('Pilih departemen', '')); return; }
                try {
                    const response = await api(`/api/v1/properties/${encodeURIComponent(selectedPropertyId)}/departments`);
                    if (property.value !== selectedPropertyId) return;
                    department.replaceChildren(option('Pilih departemen', ''), ...(response.data || []).map(item => option(item.name, item.id)));
                    department.disabled = false;
                    if (property.value === user.property?.id && user.default_department?.id) {
                        department.value = user.default_department.id;
                        approve.disabled = !department.value;
                    }
                } catch (error) {
                    if (property.value !== selectedPropertyId) return;
                    showNotice(error.message); department.replaceChildren(option('Tidak dapat memuat', ''));
                }
            });
            if (property.value) property.dispatchEvent(new Event('change'));
            department.addEventListener('change', () => { approve.disabled = !department.value; });
            approve.addEventListener('click', async () => {
                approve.disabled = true;
                try {
                    const result = await api(`/api/v1/users/${encodeURIComponent(user.id)}/approve`, { method: 'POST', body: JSON.stringify({ property_id: property.value, department_id: department.value, role: role.value }) });
                    showNotice(result.message || 'Pendaftaran berhasil disetujui.', true); await loadPendingApprovals();
                    if (!isApprovalsOnlyPage) await loadUsers();
                } catch (error) { showNotice(error.message); approve.disabled = false; }
            });
            reject.addEventListener('click', async () => {
                if (!window.confirm(`Tolak pendaftaran ${user.name}?`)) return;
                reject.disabled = true;
                try {
                    const result = await api(`/api/v1/users/${encodeURIComponent(user.id)}/reject`, { method: 'POST' });
                    showNotice(result.message || 'Pendaftaran ditolak.', true); await loadPendingApprovals();
                } catch (error) { showNotice(error.message); reject.disabled = false; }
            });
            actions.append(approve, reject); row.append(applicant, propertyCell, departmentCell, roleCell, actions);
            return row;
        }

        async function loadDepartments() {
            const propertyId = propertySelect.value;
            if (!propertyId) return;
            const response = await api(`/api/v1/properties/${encodeURIComponent(propertyId)}/departments`);
            departments = response.data || [];
            departmentSelect.replaceChildren(option('Semua departemen', ''));
            departments.forEach(department => departmentSelect.append(option(department.name, department.id)));
        }

        async function loadUsers() {
            table.replaceChildren();
            const loading = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 5; cell.className = 'px-3 py-8 text-center text-slate-400'; cell.textContent = 'Memuat pengguna…'; loading.append(cell); table.append(loading);
            try {
                const query = new URLSearchParams({ page: String(currentPage) });
                if (propertySelect.value) query.set('property_id', propertySelect.value);
                if (departmentSelect.value) query.set('department_id', departmentSelect.value);
                const response = await api(`/api/v1/users?${query}`);
                users = response.data || [];
                renderUsers();
                const meta = response.meta;
                if (meta && currentPage > meta.last_page) { currentPage = Math.max(1, meta.last_page); return loadUsers(); }
                const pagination = document.getElementById('pagination');
                pagination.replaceChildren();
                if (meta) {
                    const label = document.createElement('span'); label.textContent = `Halaman ${meta.current_page} dari ${meta.last_page} · ${meta.total} pengguna`;
                    const controls = document.createElement('div'); controls.className = 'flex gap-2';
                    const previous = document.createElement('button'); previous.type = 'button'; previous.textContent = 'Sebelumnya'; previous.disabled = meta.current_page <= 1; previous.className = 'rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40'; previous.addEventListener('click', () => { currentPage--; loadUsers(); });
                    const next = document.createElement('button'); next.type = 'button'; next.textContent = 'Berikutnya'; next.disabled = meta.current_page >= meta.last_page; next.className = 'rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40'; next.addEventListener('click', () => { currentPage++; loadUsers(); });
                    controls.append(previous, next); pagination.append(label, controls);
                } else pagination.textContent = `${users.length} pengguna`;
            } catch (error) { showNotice(error.message); }
        }

        function renderUsers() {
            table.replaceChildren();
            if (!users.length) { const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 5; cell.className = 'px-3 py-8 text-center text-slate-400'; cell.textContent = 'Tidak ada pengguna pada departemen ini.'; row.append(cell); table.append(row); return; }
            users.forEach(user => {
                const row = document.createElement('tr'); row.className = 'border-b border-slate-50 last:border-0';
                const name = document.createElement('td'); name.className = 'px-3 py-4 font-medium text-slate-800'; name.textContent = user.name;
                const email = document.createElement('td'); email.className = 'px-3 py-4 text-slate-500'; email.textContent = user.email;
                const department = document.createElement('td'); department.className = 'px-3 py-4 text-slate-500'; department.textContent = (user.departments || []).map(item => item.name).join(', ') || '—';
                const role = document.createElement('td'); role.className = 'px-3 py-4'; const title = document.createElement('p'); title.className = 'font-medium text-slate-800'; title.textContent = user.position || 'Belum diatur'; const privilegeRole = document.createElement('p'); privilegeRole.className = 'mt-1 text-xs text-slate-500'; privilegeRole.textContent = `Role privilege: ${user.role}`; role.append(title, privilegeRole);
                const action = document.createElement('td'); action.className = 'px-3 py-4 text-right';
                const canManageTarget = actor?.id !== user.id && user.role !== 'Admin' && (hierarchy[actor?.role] || 0) > (hierarchy[user.role] || 0);
                if (canManage && canManageTarget) {
                    const button = document.createElement('button'); button.type = 'button'; button.className = 'mr-2 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50'; button.textContent = 'Edit'; button.addEventListener('click', () => openEditor(user)); action.append(button);
                }
                if (canManagePasswordsAndDelete && canManageTarget) {
                    const deleteButton = document.createElement('button'); deleteButton.type = 'button'; deleteButton.className = 'rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50'; deleteButton.textContent = 'Hapus'; deleteButton.addEventListener('click', () => deleteUser(user)); action.append(deleteButton);
                }
                if (!action.childElementCount) { action.textContent = '?'; action.classList.add('text-slate-300'); }
                row.append(name, email, department, role, action); table.append(row);
            });
        }

        function openEditor(user) {
            editForm.reset();
            editForm.elements.id.value = user.id;
            editForm.elements.name.value = user.name;
            editForm.elements.email.value = user.email;
            editForm.elements.position.value = user.position || '';
            document.getElementById('edit-password-fields').classList.toggle('hidden', !canManagePasswordsAndDelete);
            const roleSelect = document.getElementById('edit-role');
            roleSelect.replaceChildren(...roleOptions.filter(role => actor.role === 'Admin' || (hierarchy[role] || 0) < (hierarchy[actor.role] || 0)).map(role => option(roleLabels[role] || role, role)));
            if (!roleOptions.length) document.getElementById('role-field').classList.add('hidden'); else { document.getElementById('role-field').classList.remove('hidden'); roleSelect.value = user.role; }
            const allowedDepartments = actor.role === 'Department Leader'
                ? departments.filter(department => (user.departments || []).some(item => item.id === department.id))
                : departments;
            const departmentMulti = document.getElementById('edit-departments');
            const currentIds = (user.departments || []).map(department => department.id);
            departmentMulti.replaceChildren();
            allowedDepartments.forEach(department => {
                const label = document.createElement('label');
                label.className = 'flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = department.id;
                checkbox.checked = currentIds.includes(department.id);
                checkbox.className = 'size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-700';
                const departmentName = document.createElement('span');
                departmentName.textContent = department.name;
                label.append(checkbox, departmentName);
                departmentMulti.append(label);
            });
            const defaultSelect = document.getElementById('edit-default-department');
            defaultSelect.replaceChildren(...allowedDepartments.map(department => option(department.name, department.id)));
            defaultSelect.value = user.default_department?.id || currentIds[0] || '';
            defaultSelect.onchange = () => {
                const defaultCheckbox = [...departmentMulti.querySelectorAll('input[type="checkbox"]')]
                    .find(item => item.value === defaultSelect.value);
                if (defaultCheckbox) {
                    defaultCheckbox.checked = true;
                }
            };
            defaultSelect.onchange();
            document.getElementById('edit-error').classList.add('hidden');
            modal.classList.remove('hidden'); modal.classList.add('flex');
        }

        function closeEditor() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        async function deleteUser(user) {
            if (!window.confirm(`Hapus akun ${user.name}? Riwayat task tetap disimpan dan identitas akun akan dianonimkan.`)) return;
            try {
                await api(`/api/v1/users/${encodeURIComponent(user.id)}`, { method: 'DELETE' });
                showNotice('Akun berhasil dihapus. Riwayat task tetap tersimpan.', true);
                await loadUsers();
            } catch (error) { showNotice(error.message); }
        }

        document.getElementById('close-modal').addEventListener('click', closeEditor);
        document.getElementById('cancel-edit').addEventListener('click', closeEditor);
        modal.addEventListener('click', event => { if (event.target === modal) closeEditor(); });
        editForm.addEventListener('submit', async event => {
            event.preventDefault();
            const error = document.getElementById('edit-error');
            const save = document.getElementById('save-edit');
            error.classList.add('hidden'); save.disabled = true;
            const data = new FormData(editForm);
            const selectedDepartments = [...document.querySelectorAll('#edit-departments input[type="checkbox"]:checked')]
                .map(item => item.value);
            const defaultDepartmentId = data.get('default_department_id');
            if (defaultDepartmentId && !selectedDepartments.includes(defaultDepartmentId)) {
                selectedDepartments.push(defaultDepartmentId);
            }
            const payload = {
                name: data.get('name'), email: data.get('email'), position: data.get('position') || null,
                department_ids: selectedDepartments,
                default_department_id: defaultDepartmentId
            };
            const newPassword = data.get('password');
            const passwordConfirmation = data.get('password_confirmation');
            if (newPassword || passwordConfirmation) {
                payload.password = newPassword;
                payload.password_confirmation = passwordConfirmation;
            }
            if (!document.getElementById('role-field').classList.contains('hidden')) payload.role = data.get('role');
            try {
                const response = await api(`/api/v1/users/${encodeURIComponent(data.get('id'))}`, { method: 'PATCH', body: JSON.stringify(payload) });
                closeEditor(); showNotice(response.message || 'Data pengguna berhasil diperbarui.', true); await loadUsers();
            } catch (exception) { error.textContent = exception.message; error.classList.remove('hidden'); }
            finally { save.disabled = false; }
        });

        window.addEventListener('hotel-task-language-change', () => {
            if (roleSettings) {
                renderRoleCards();
            }
        });

    </script>
</body>
</html>

