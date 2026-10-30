<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Property Management · HotelTask</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-slate-900 antialiased">
    @include('partials.app-sidebar')
    <div data-app-content class="min-h-screen transition-[padding] duration-200 lg:pl-64">
    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <div class="mb-8"><p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Property Management</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Property Management</h1><p class="mt-2 text-sm text-slate-500">Kelola properti dan departemen untuk setiap workspace.</p></div>
        <div id="settings-notice" role="status" aria-live="polite" class="mb-6 hidden rounded-xl px-4 py-3 text-sm"></div>
        <div id="settings-denied" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm leading-6 text-amber-950">Akun Anda tidak memiliki akses pengaturan. Hubungi GM atau administrator workspace jika Anda memerlukan akses.</div>

        <div id="settings-content" class="hidden space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50">
                <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><h2 class="font-semibold">Properti dan departemen</h2><p class="mt-1 text-sm text-slate-500">Pilih properti untuk melihat dan mengelola departemennya.</p></div><div class="w-full sm:max-w-xs"><label for="property-select" class="mb-2 block text-xs font-medium text-slate-600">Properti aktif</label><select id="property-select" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10"></select></div></div>
                <div class="grid gap-6 lg:grid-cols-2">
                    <div id="property-create-section" class="hidden rounded-xl bg-slate-50 p-4"><h3 class="text-sm font-semibold">Tambah properti</h3><form id="property-form" class="mt-3 space-y-3"><label class="block text-xs font-medium text-slate-600">Nama properti<input name="name" required maxlength="255" placeholder="Nama properti" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-700"></label><label class="block text-xs font-medium text-slate-600">Zona waktu<select name="timezone" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-700"><option value="Asia/Jakarta" selected>WIB - Asia/Jakarta (UTC+7)</option><option value="Asia/Makassar">WITA - Asia/Makassar (UTC+8)</option><option value="Asia/Jayapura">WIT - Asia/Jayapura (UTC+9)</option></select><span class="mt-1 block font-normal text-slate-400">Gunakan nama zona waktu IANA, misalnya Asia/Jakarta.</span></label><button class="rounded-lg bg-[#101b2d] px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Tambah properti</button></form></div>
                    <div><div class="flex items-center justify-between"><h3 class="text-sm font-semibold">Departemen</h3><span id="department-count" class="text-xs text-slate-400"></span></div><div id="department-list" class="mt-3 max-h-80 space-y-2 overflow-y-auto overscroll-contain pr-2"><p class="text-sm text-slate-400">Memuat departemen…</p></div><form id="department-form" class="mt-4 hidden gap-2"><input name="name" required maxlength="255" placeholder="Nama departemen baru" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-700"><button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Tambah</button></form></div>
                </div>
            </section>

            <section id="property-edit-section" class="hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50">
                <h2 class="font-semibold">Edit properti aktif</h2><p class="mt-1 text-sm text-slate-500">Perbarui nama hotel, zona waktu, atau alamat.</p>
                <form id="property-edit-form" class="mt-4 grid gap-4 md:grid-cols-3">
                    <label class="text-xs font-medium text-slate-600">Nama hotel<input name="name" required maxlength="255" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label>
                    <label class="text-xs font-medium text-slate-600">Zona waktu<select name="timezone" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-700"><option value="Asia/Jakarta">WIB - Asia/Jakarta (UTC+7)</option><option value="Asia/Makassar">WITA - Asia/Makassar (UTC+8)</option><option value="Asia/Jayapura">WIT - Asia/Jayapura (UTC+9)</option></select></label>
                    <label class="text-xs font-medium text-slate-600">Alamat<input name="address" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></label>
                    <div class="flex flex-wrap gap-2 md:col-span-3"><button class="rounded-lg bg-[#101b2d] px-4 py-2 text-sm font-medium text-white">Simpan perubahan</button><button id="delete-property" type="button" class="hidden rounded-lg border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700">Hapus properti</button></div>
                    <p id="delete-property-hint" class="hidden text-xs text-slate-400 md:col-span-3">Penghapusan hanya tersedia jika properti belum memiliki user, task, atau departemen.</p>
                </form>
            </section>
        </div>
    </main>
    </div>
    <script>
        const apiToken = sessionStorage.getItem('hotel_task_access_token');
        const currentUser = JSON.parse(sessionStorage.getItem('hotel_task_user') || 'null');
        const privileges = currentUser?.privileges || [];
        const canManageProperties = privileges.includes('properties.manage');
        const canManageDepartments = privileges.includes('departments.manage');
        const canDeleteProperties = currentUser?.role === 'Admin';
        let properties = [];
        const notice = document.getElementById('settings-notice');

        if (!apiToken) window.location.replace('/login');
        else if (!canManageProperties && !canManageDepartments) {
            document.getElementById('settings-denied').classList.remove('hidden');
        } else {
            document.getElementById('settings-content').classList.remove('hidden');
            if (canManageProperties) document.getElementById('property-create-section').classList.remove('hidden');
            if (canManageProperties) document.getElementById('property-edit-section').classList.remove('hidden');
            if (canDeleteProperties) {
                document.getElementById('delete-property').classList.remove('hidden');
                document.getElementById('delete-property-hint').classList.remove('hidden');
            }
            if (canManageDepartments) document.getElementById('department-form').classList.remove('hidden');
            initializeSettings();
        }

        async function api(path, options = {}) {
            const response = await fetch(path, {
                ...options,
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${apiToken}`, ...(options.body ? { 'Content-Type': 'application/json' } : {}), ...options.headers }
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 401) {
                sessionStorage.removeItem('hotel_task_access_token');
                sessionStorage.removeItem('hotel_task_user');
                window.location.replace('/login');
                throw new Error('Sesi berakhir. Silakan masuk kembali.');
            }
            if (!response.ok) {
                const validationError = Object.values(data.errors || {}).flat()[0];
                throw new Error(validationError || data.message || 'Permintaan tidak dapat diproses.');
            }
            return data;
        }

        function showNotice(message, success = false) {
            notice.textContent = message;
            notice.className = success
                ? 'mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900'
                : 'mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
        }

        async function initializeSettings() {
            try {
                const propertyData = await api('/api/v1/properties').catch(error => ({ error: error.message }));
                if (propertyData?.error) showNotice(propertyData.error);
                else {
                    properties = propertyData.data || [];
                    const select = document.getElementById('property-select');
                    select.replaceChildren();
                    properties.forEach(property => {
                        const option = document.createElement('option');
                        option.value = property.id;
                        option.textContent = property.name;
                        select.append(option);
                    });
                    if (properties.length) {
                        select.value = currentUser?.property_id && properties.some(item => item.id === currentUser.property_id) ? currentUser.property_id : properties[0].id;
                        select.onchange = () => { loadPropertyDetails(); loadDepartments(); };
                        loadPropertyDetails();
                        await loadDepartments();
                    } else {
                        document.getElementById('department-list').textContent = 'Belum ada properti. Tambahkan properti terlebih dahulu.';
                    }
                }
            } catch (error) { showNotice(error.message); }
        }

        function setTimezoneSelect(select, timezone) {
            if (![...select.options].some(option => option.value === timezone)) {
                const legacyOption = document.createElement('option');
                legacyOption.value = timezone;
                legacyOption.textContent = `${timezone} (tersimpan)`;
                select.append(legacyOption);
            }
            select.value = timezone;
        }

        function loadPropertyDetails() {
            const property = properties.find(item => item.id === document.getElementById('property-select').value);
            if (!property) return;
            const form = document.getElementById('property-edit-form');
            form.elements.name.value = property.name || '';
            setTimezoneSelect(form.elements.timezone, property.timezone || 'Asia/Jakarta');
            form.elements.address.value = property.address || '';
        }

        async function loadDepartments() {
            const propertyId = document.getElementById('property-select').value;
            if (!propertyId) return;
            try {
                const response = await api(`/api/v1/properties/${encodeURIComponent(propertyId)}/departments`);
                const departments = response.data || [];
                const list = document.getElementById('department-list');
                document.getElementById('department-count').textContent = `${departments.length} total`;
                list.replaceChildren();
                if (!departments.length) {
                    const empty = document.createElement('p'); empty.className = 'text-sm text-slate-400'; empty.textContent = 'Belum ada departemen.'; list.append(empty);
                }
                departments.forEach(department => {
                    const item = document.createElement('div'); item.className = 'flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2.5 text-sm';
                    const name = document.createElement('span'); name.className = 'font-medium text-slate-700'; name.textContent = department.name;
                    const actions = document.createElement('div'); actions.className = 'flex items-center gap-3';
                    const label = document.createElement('span'); label.className = 'text-xs text-slate-400'; label.textContent = 'Departemen';
                    actions.append(label);
                    if (canManageDepartments) {
                        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'text-xs font-medium text-rose-700 hover:text-rose-900'; remove.textContent = 'Hapus';
                        remove.addEventListener('click', async () => {
                            if (!window.confirm(`Hapus departemen “${department.name}”? Departemen yang masih memiliki user atau tugas tidak dapat dihapus.`)) return;
                            try {
                                await api(`/api/v1/properties/${encodeURIComponent(propertyId)}/departments/${encodeURIComponent(department.id)}`, { method: 'DELETE' });
                                showNotice('Departemen berhasil dihapus.', true);
                                await loadDepartments();
                            } catch (error) { showNotice(error.message); }
                        });
                        actions.append(remove);
                    }
                    item.append(name, actions); list.append(item);
                });
            } catch (error) { showNotice(error.message); }
        }

        document.getElementById('property-form').addEventListener('submit', async event => {
            event.preventDefault();
            const form = event.currentTarget;
            try {
                const values = new FormData(form);
                const result = await api('/api/v1/properties', { method: 'POST', body: JSON.stringify({ name: values.get('name'), timezone: values.get('timezone') }) });
                form.reset();
                showNotice(result.message || 'Properti berhasil ditambahkan.', true);
                await initializeSettings();
            } catch (error) { showNotice(error.message); }
        });

        document.getElementById('property-edit-form').addEventListener('submit', async event => {
            event.preventDefault();
            const propertyId = document.getElementById('property-select').value;
            const values = new FormData(event.currentTarget);
            try {
                const result = await api(`/api/v1/properties/${encodeURIComponent(propertyId)}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ name: values.get('name'), timezone: values.get('timezone') || null, address: values.get('address') || null })
                });
                showNotice(result.message || 'Properti berhasil diperbarui.', true);
                const selectedId = propertyId;
                await initializeSettings();
                document.getElementById('property-select').value = selectedId;
                loadPropertyDetails();
                await loadDepartments();
            } catch (error) { showNotice(error.message); }
        });

        document.getElementById('delete-property').addEventListener('click', async () => {
            const propertyId = document.getElementById('property-select').value;
            const property = properties.find(item => item.id === propertyId);
            if (!property || !window.confirm(`Hapus properti "${property.name}"? Properti yang memiliki data tidak bisa dihapus.`)) return;
            try {
                await api(`/api/v1/properties/${encodeURIComponent(propertyId)}`, { method: 'DELETE' });
                showNotice('Properti berhasil dihapus.', true);
                await initializeSettings();
            } catch (error) { showNotice(error.message); }
        });

        document.getElementById('department-form').addEventListener('submit', async event => {
            event.preventDefault();
            const form = event.currentTarget;
            const propertyId = document.getElementById('property-select').value;
            if (!propertyId) { showNotice('Pilih properti terlebih dahulu.'); return; }
            try {
                await api(`/api/v1/properties/${encodeURIComponent(propertyId)}/departments`, { method: 'POST', body: JSON.stringify({ name: new FormData(form).get('name') }) });
                form.reset();
                showNotice('Departemen berhasil ditambahkan.', true);
                await loadDepartments();
            } catch (error) { showNotice(error.message); }
        });

    </script>
</body>
</html>

