<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports · HotelTask</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-slate-900 antialiased">
    @include('partials.app-sidebar')
    <div data-app-content class="min-h-screen transition-[padding] duration-200 lg:pl-64">
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-7 sm:px-8 sm:py-10">
            <header><p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Analytics</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Department performance</h1><p class="mt-2 text-sm text-slate-500">Review task completion, due dates, and average completion time.</p></header>
            <div id="report-notice" role="status" aria-live="polite" class="hidden rounded-xl px-4 py-3 text-sm"></div>
            <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end sm:p-5">
                <label id="report-property-wrap" class="hidden flex-1 text-sm font-medium text-slate-700">Property<select id="report-property" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"></select></label>
                <label class="flex-1 text-sm font-medium text-slate-700">Department<select id="report-department" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"></select></label>
                <label class="text-sm font-medium text-slate-700">Period<select id="report-period" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"><option value="7">Last 7 days</option><option value="30" selected>Last 30 days</option><option value="90">Last 90 days</option></select></label>
                <label class="text-sm font-medium text-slate-700">Task status<select id="report-task-status" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"><option value="all">All tasks</option><option value="completed">Completed</option><option value="unfinished">Unfinished</option></select></label>
                <button id="report-refresh" type="button" class="rounded-xl bg-[#101b2d] px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">View report</button>
            </section>
            <section id="report-content" class="hidden space-y-6">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total tasks</p><p id="report-total" class="mt-3 text-3xl font-semibold">0</p></article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Completed</p><p id="report-done" class="mt-3 text-3xl font-semibold">0</p></article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Pending</p><p id="report-pending" class="mt-3 text-3xl font-semibold">0</p></article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Overdue</p><p id="report-overdue" class="mt-3 text-3xl font-semibold">0</p></article>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold">Performance</h2><div class="mt-4 space-y-3 text-sm"><p class="flex justify-between gap-3"><span class="text-slate-500">Completion rate</span><strong id="report-completion"></strong></p><p class="flex justify-between gap-3"><span class="text-slate-500">On-time completion</span><strong id="report-on-time"></strong></p><p class="flex justify-between gap-3"><span class="text-slate-500">Speed score</span><strong id="report-speed"></strong></p><p class="flex justify-between gap-3 border-t border-slate-100 pt-3"><span class="text-slate-500">Overall score</span><strong id="report-score"></strong></p><p class="flex justify-between gap-3"><span class="text-slate-500">Status</span><strong id="report-status"></strong></p></div></article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold">Completion time</h2><p class="mt-3 text-3xl font-semibold"><span id="report-avg-days">0</span><span class="ml-2 text-sm font-normal text-slate-500">days average</span></p><p class="mt-2 text-sm text-slate-500">Average time from task creation to completion.</p></article>
                </div>
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 id="report-task-list-title" class="font-semibold">Tasks</h2></div><div id="report-tasks" class="divide-y divide-slate-100"></div></section>
            </section>
        </main>
    </div>
    <script>
        (() => {
            const token = sessionStorage.getItem('hotel_task_access_token');
            let actor = JSON.parse(sessionStorage.getItem('hotel_task_user') || 'null');
            let privileges = actor?.privileges || [];
            const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` };
            const propertySelect = document.getElementById('report-property');
            const departmentSelect = document.getElementById('report-department');
            let canViewAll = privileges.includes('properties.view_all');
            let properties = [];

            if (!token) window.location.replace('/login');

            async function api(path) {
                const response = await fetch(path, { headers });
                const data = await response.json().catch(() => ({}));
                if (response.status === 401) { sessionStorage.removeItem('hotel_task_access_token'); sessionStorage.removeItem('hotel_task_user'); window.location.replace('/login'); }
                if (!response.ok) throw new Error(data.message || 'The report could not be loaded.');
                return data;
            }

            function showNotice(message) {
                const notice = document.getElementById('report-notice');
                notice.textContent = message;
                notice.className = 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
            }

            async function loadProperties() {
                const response = await api('/api/v1/properties');
                properties = response.data || [];
                if (canViewAll) {
                    document.getElementById('report-property-wrap').classList.remove('hidden');
                    propertySelect.replaceChildren(...properties.map(property => new Option(property.name, property.id)));
                    if (actor?.property_id) propertySelect.value = actor.property_id;
                }
                await loadDepartments();
            }

            async function loadDepartments() {
                const propertyId = canViewAll ? propertySelect.value : actor?.property_id;
                if (!propertyId) { departmentSelect.replaceChildren(new Option('No accessible departments', '')); return; }
                const response = await api(`/api/v1/properties/${encodeURIComponent(propertyId)}/departments`);
                const accessibleDepartmentIds = new Set((actor?.departments || []).map(department => department.id));
                const departments = actor?.role === 'Admin'
                    ? (response.data || [])
                    : (response.data || []).filter(department => accessibleDepartmentIds.has(department.id));
                departmentSelect.replaceChildren(...departments.map(department => new Option(department.name, department.id)));
                if (!departmentSelect.value && departmentSelect.options.length) departmentSelect.selectedIndex = 0;
            }

            async function loadReport() {
                const propertyId = canViewAll ? propertySelect.value : actor?.property_id;
                const departmentId = departmentSelect.value;
                if (!departmentId) { showNotice('Select a department to view its report.'); return; }
                const query = new URLSearchParams({
                    department_id: departmentId,
                    period: document.getElementById('report-period').value,
                    task_status: document.getElementById('report-task-status').value,
                });
                try {
                    const response = await fetch(`/api/v1/reports/department-performance?${query}`, { headers: { ...headers, 'X-Property-ID': propertyId || '' } });
                    const data = await response.json().catch(() => ({}));
                    if (response.status === 401) { sessionStorage.removeItem('hotel_task_access_token'); sessionStorage.removeItem('hotel_task_user'); window.location.replace('/login'); }
                    if (!response.ok) throw new Error(data.message || 'The report could not be loaded.');
                    renderReport(data);
                    document.getElementById('report-notice').classList.add('hidden');
                } catch (error) { showNotice(error.message); }
            }

            function renderReport(report) {
                document.getElementById('report-content').classList.remove('hidden');
                document.getElementById('report-total').textContent = report.metrics.total_tasks;
                document.getElementById('report-done').textContent = report.metrics.done;
                document.getElementById('report-pending').textContent = report.metrics.pending;
                document.getElementById('report-overdue').textContent = report.metrics.overdue_now;
                document.getElementById('report-completion').textContent = report.performance.completion_rate;
                document.getElementById('report-on-time').textContent = report.performance.on_time_rate;
                document.getElementById('report-speed').textContent = report.performance.speed_score;
                document.getElementById('report-score').textContent = report.performance.overall_score;
                document.getElementById('report-status').textContent = report.performance.status;
                document.getElementById('report-avg-days').textContent = report.metrics.avg_time_to_finish_days;
                const container = document.getElementById('report-tasks'); container.replaceChildren();
                const taskFilter = document.getElementById('report-task-status').value;
                document.getElementById('report-task-list-title').textContent = taskFilter === 'completed'
                    ? 'Completed tasks'
                    : taskFilter === 'unfinished' ? 'Unfinished tasks' : 'Tasks';
                const tasks = report.tasks?.data || report.tasks || [];
                if (!tasks.length) {
                    const empty = document.createElement('p');
                    empty.className = 'px-5 py-8 text-sm text-slate-400';
                    empty.textContent = taskFilter === 'completed'
                        ? 'No completed tasks in this period.'
                        : taskFilter === 'unfinished' ? 'No unfinished tasks in this period.' : 'No tasks in this period.';
                    container.append(empty);
                    return;
                }
                tasks.forEach(task => {
                    const row = document.createElement('article'); row.className = 'flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between';
                    const title = document.createElement('p'); title.className = 'font-medium text-slate-800'; title.textContent = task.title;
                    const statusLabels = { DONE: 'Completed', IN_PROGRESS: 'In progress', NOT_STARTED: 'Created' };
                    const completionDate = task.completed_at ? new Date(task.completed_at).toLocaleDateString() : 'Not completed';
                    const detail = document.createElement('p'); detail.className = 'text-xs text-slate-500'; detail.textContent = `${task.assignee?.name || 'Unassigned'} · ${statusLabels[task.status] || task.status} · ${completionDate}`;
                    row.append(title, detail); container.append(row);
                });
            }

            propertySelect.addEventListener('change', () => loadDepartments().catch(error => showNotice(error.message)));
            document.getElementById('report-refresh').addEventListener('click', loadReport);
            const currentUserPromise = window.hotelTaskUserPromise || Promise.resolve(actor);
            currentUserPromise
                .then(user => {
                    if (user) {
                        actor = user;
                        privileges = user.privileges || [];
                        canViewAll = privileges.includes('properties.view_all');
                    }
                    return loadProperties();
                })
                .catch(error => showNotice(error.message));
        })();
    </script>
</body>
</html>
