<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tasks · HotelTask</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-slate-900 antialiased">
    @include('partials.app-sidebar')
    <div data-app-content class="min-h-screen transition-[padding] duration-200 lg:pl-64">
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-7 sm:px-8 sm:py-10">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Operations</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Tasks</h1><p class="mt-2 text-sm text-slate-500">View and manage tasks in the departments you can access.</p></div>
                <button id="new-task-open" type="button" class="hidden rounded-xl bg-[#101b2d] px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Create task</button>
            </header>
            <div id="tasks-notice" role="status" aria-live="polite" class="hidden rounded-xl px-4 py-3 text-sm"></div>
            <section id="task-create-panel" class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex items-center justify-between"><div><h2 class="font-semibold">Create a task</h2><p class="mt-1 text-sm text-slate-500">Choose a department you have access to.</p></div><button id="new-task-close" type="button" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-100">Close</button></div>
                <form id="task-create-form" class="grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">Task title<input name="title" required maxlength="255" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal" /></label>
                    <label class="text-sm font-medium text-slate-700">Department<select id="create-department" name="department_id" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal"></select></label>
                    <label class="text-sm font-medium text-slate-700">Priority<select name="priority" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal"><option value="LOW">Low</option><option value="MEDIUM" selected>Medium</option><option value="HIGH">High</option></select></label>
                    <label class="text-sm font-medium text-slate-700"><span data-language-id="Penerima tugas" data-language-en="Assignee">Assignee</span><select id="create-assignee" name="assigned_to" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal" disabled><option value="">Leave unassigned</option></select></label>
                    <label class="text-sm font-medium text-slate-700">Due date<input name="due_date" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal" /></label>
                    <label class="text-sm font-medium text-slate-700">Task type<select id="create-task-type" name="task_type" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal"><option value="description" data-language-id="Deskripsi" data-language-en="Description">Description</option><option value="checklist" selected data-language-id="Checklist" data-language-en="Checklist">Checklist</option></select></label>
                    <label id="create-description-field" class="text-sm font-medium text-slate-700 md:col-span-2"><span data-language-id="Deskripsi" data-language-en="Description">Description</span><textarea name="description" rows="3" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal"></textarea></label>
                    <section id="create-checklist-field" class="hidden space-y-3 md:col-span-2">
                        <div class="flex items-center justify-between gap-3"><h3 class="text-sm font-semibold text-slate-700" data-language-id="Item checklist" data-language-en="Checklist items">Checklist items</h3><button id="create-checklist-add" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-language-id="Tambah item" data-language-en="Add item">Add item</button></div>
                        <div id="create-checklist-items" class="space-y-2"></div>
                    </section>
                    <label class="text-sm font-medium text-slate-700 md:col-span-2"><span data-language-id="Lampiran" data-language-en="Attachments">Attachments</span><input id="create-attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium" /><span class="mt-1 block text-xs font-normal text-slate-500" data-language-id="Gambar, PDF, dan file Microsoft Office. Maksimal 5 file, masing-masing 10 MB." data-language-en="Images, PDF, and Microsoft Office files. Up to 5 files, 10 MB each.">Images, PDF, and Microsoft Office files. Up to 5 files, 10 MB each.</span></label>
                    <div class="flex justify-end md:col-span-2"><button id="task-create-submit" type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Save task</button></div>
                </form>
            </section>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold">Task list</h2><p class="mt-1 text-sm text-slate-500">Task visibility follows your role and department access.</p></div><div class="flex flex-col gap-2 sm:flex-row"><select id="department-filter" aria-label="Filter by department" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"><option value="">All accessible departments</option></select><select id="status-filter" aria-label="Filter by status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"><option value="">All statuses</option><option value="NOT_STARTED">Created</option><option value="IN_PROGRESS">In progress</option><option value="DONE" data-language-id="Selesai" data-language-en="Completed">Completed</option><option value="OVERDUE" data-language-id="Terlambat" data-language-en="Overdue">Overdue</option></select></div></div>
                <div id="task-list" class="divide-y divide-slate-100"><p class="px-5 py-10 text-center text-sm text-slate-400">Loading tasks…</p></div>
                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4"><span id="task-count" class="text-xs text-slate-400"></span><div class="flex gap-2"><button id="tasks-previous" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-40">Previous</button><button id="tasks-next" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-40">Next</button></div></div>
            </section>
            @if (request()->is('dashboardadmin/tasks') || (request()->user()?->hasPrivilege('tasks.assign') && ! in_array(request()->user()->role, ['Associate', 'Vendor', 'Daily Worker', 'Casual'], true)))
                @include('partials.task-tracker')
            @endif
        </main>
    </div>
    <div id="task-detail-modal" class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/50 p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="task-detail-title">
        <section class="max-h-[94dvh] w-full max-w-3xl overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
            <div class="flex items-start justify-between gap-4"><div><p id="task-detail-meta" class="text-xs font-semibold uppercase tracking-wide text-emerald-700"></p><h2 id="task-detail-title" class="mt-2 text-xl font-semibold"></h2><p id="task-detail-description" class="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-500"></p></div><button id="task-detail-close" type="button" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-100">Close</button></div>
            <div id="task-progress-wrap" class="mt-5 rounded-xl bg-slate-50 p-4"><div class="flex items-center justify-between"><span id="task-progress-label" class="text-sm font-medium" data-language-id="Progress" data-language-en="Progress">Progress</span><span id="task-progress-value" class="text-sm font-semibold text-emerald-800"></span></div><div id="task-progress-meter" class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-labelledby="task-progress-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div id="task-progress-fill" class="h-full rounded-full bg-emerald-600 transition-[width] duration-300" style="width: 0%"></div></div><div id="task-progress-controls" class="mt-3 hidden"><label for="task-progress" class="sr-only">Set task progress</label><input id="task-progress" type="range" min="0" max="100" step="5" class="w-full accent-emerald-700"><button id="task-progress-save" type="button" class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60" data-language-id="Simpan progress" data-language-en="Save progress">Save progress</button></div></div>
            <section id="task-checklist-section" class="mt-5 hidden rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-3"><h3 class="font-semibold" data-language-id="Checklist task" data-language-en="Task checklist">Task checklist</h3><span id="task-checklist-count" class="text-xs text-slate-500"></span></div><div id="task-checklist-list" class="mt-3 space-y-2"></div></section>
            <section class="mt-5 rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-3"><h3 class="font-semibold" data-language-id="Lampiran" data-language-en="Attachments">Attachments</h3><span id="task-attachment-count" class="text-xs text-slate-400"></span></div><div id="task-attachment-list" class="mt-3 space-y-3"><p class="text-sm text-slate-400" data-language-id="Belum ada lampiran." data-language-en="No attachments.">No attachments.</p></div><form id="task-attachment-form" class="mt-4 hidden gap-3 sm:flex-row sm:items-center"><input id="task-attachment-files" type="file" multiple accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv" aria-label="Select files to upload" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-2 file:py-1.5 file:text-xs file:font-medium"><button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800" data-language-id="Unggah file" data-language-en="Upload files">Upload files</button></form></section>
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <section><div class="flex items-center justify-between"><h3 class="font-semibold">Subtasks</h3><span id="subtask-count" class="text-xs text-slate-400"></span></div><div id="subtask-list" class="mt-3 space-y-2"></div><form id="subtask-form" class="mt-4 hidden gap-2"><input name="title" required maxlength="255" placeholder="Add a subtask or blocker" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm"><button type="submit" class="rounded-lg bg-[#101b2d] px-3 py-2 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">Add</button></form></section>
                <section><h3 class="font-semibold">Comments</h3><div id="comment-list" class="mt-3 max-h-64 space-y-3 overflow-y-auto"></div><form id="comment-form" class="mt-4 hidden gap-2"><textarea name="comment" required maxlength="10000" rows="2" placeholder="Write an update" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea><button type="submit" class="self-end rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">Send</button></form></section>
            </div>
            <div class="mt-6 flex justify-end"><button id="task-delete" type="button" class="hidden rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">Delete task</button></div>
        </section>
    </div>
    <div id="task-due-date-modal" class="fixed inset-0 z-[60] hidden items-end justify-center bg-slate-950/50 p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="task-due-date-heading">
        <form id="task-due-date-form" class="w-full max-w-md space-y-5 rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
            <div>
                <h2 id="task-due-date-heading" class="text-lg font-semibold" data-language-id="Perpanjang due date" data-language-en="Extend due date">Extend due date</h2>
                <p id="task-due-date-task-title" class="mt-1 break-words text-sm text-slate-500"></p>
            </div>
            <label for="task-due-date-input" class="block text-sm font-medium text-slate-700" data-language-id="Tanggal due date baru" data-language-en="New due date">New due date</label>
            <input id="task-due-date-input" name="due_date" type="date" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
            <p id="task-due-date-error" class="hidden text-sm text-rose-700" role="alert"></p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button id="task-due-date-cancel" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-language-id="Batal" data-language-en="Cancel">Cancel</button>
                <button id="task-due-date-submit" type="submit" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60" data-language-id="Simpan due date" data-language-en="Save due date">Save due date</button>
            </div>
        </form>
    </div>
    <script>
        (() => {
            const token = sessionStorage.getItem('hotel_task_access_token');
            let actor = JSON.parse(sessionStorage.getItem('hotel_task_user') || 'null');
            let privileges = actor?.privileges || [];
            const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` };
            const list = document.getElementById('task-list');
            const createPanel = document.getElementById('task-create-panel');
            const createDepartment = document.getElementById('create-department');
            const createTaskType = document.getElementById('create-task-type');
            const createDescriptionField = document.getElementById('create-description-field');
            const createChecklistField = document.getElementById('create-checklist-field');
            const createChecklistItems = document.getElementById('create-checklist-items');
            const assigneeSelect = document.getElementById('create-assignee');
            const detailModal = document.getElementById('task-detail-modal');
            const dueDateModal = document.getElementById('task-due-date-modal');
            const dueDateForm = document.getElementById('task-due-date-form');
            const dueDateInput = document.getElementById('task-due-date-input');
            const dueDateError = document.getElementById('task-due-date-error');
            const attachmentForm = document.getElementById('task-attachment-form');
            const trackerSection = document.getElementById('task-tracker');
            const trackerDepartmentFilter = document.getElementById('tracker-department-filter');
            const trackerPeriodFilter = document.getElementById('tracker-period-filter');
            let departments = [];
            let availableAssignees = [];
            let attachmentPreviewUrls = [];
            let tasks = [];
            let currentTask = null;
            let taskToExtendDueDate = null;
            let currentPage = 1;
            let lastPage = 1;
            const subscribedDepartmentIds = new Set();
            let subscribedUserId = null;
            let trackerRefreshTimeout = null;

            if (!token) window.location.replace('/login');
            let canCreate = privileges.includes('tasks.assign');
            let canManage = privileges.includes('tasks.manage');
            let canUpdateAssigned = privileges.includes('tasks.update_assigned');
            let canManageSubtasks = privileges.includes('subtasks.manage_assigned');
            let canComment = privileges.includes('tasks.comment');

            async function api(path, options = {}) {
                const isFormData = options.body instanceof FormData;
                const response = await fetch(path, { ...options, headers: { ...headers, ...(options.body && !isFormData ? { 'Content-Type': 'application/json' } : {}), ...options.headers } });
                const data = await response.json().catch(() => ({}));
                if (response.status === 401) {
                    sessionStorage.removeItem('hotel_task_access_token');
                    sessionStorage.removeItem('hotel_task_user');
                    window.location.replace('/login');
                }
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'The request could not be completed.');
                return data;
            }

            function notice(message, success = false) {
                const element = document.getElementById('tasks-notice');
                element.textContent = message;
                element.className = `rounded-xl border px-4 py-3 text-sm ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-800'}`;
            }

            function option(label, value) { return new Option(label, value); }

            function localizedText(english, indonesian) {
                return localStorage.getItem('hotel_task_language') === 'id' ? indonesian : english;
            }

            function addChecklistInputRow(value = '') {
                if (createChecklistItems.childElementCount >= 50) return;

                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';

                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'checklist_items[]';
                input.value = value;
                input.maxLength = 255;
                input.required = createTaskType.value === 'checklist';
                input.disabled = createTaskType.value !== 'checklist';
                input.placeholder = localizedText('Checklist item', 'Item checklist');
                input.className = 'min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.textContent = '×';
                removeButton.className = 'rounded-lg px-3 py-2 text-lg leading-none text-slate-400 hover:bg-rose-50 hover:text-rose-700';
                removeButton.setAttribute('aria-label', localizedText('Remove checklist item', 'Hapus item checklist'));
                removeButton.addEventListener('click', () => {
                    row.remove();
                    document.getElementById('create-checklist-add').disabled = false;
                    if (createTaskType.value === 'checklist' && !createChecklistItems.childElementCount) {
                        addChecklistInputRow();
                    }
                });

                row.append(input, removeButton);
                createChecklistItems.append(row);
                document.getElementById('create-checklist-add').disabled = createChecklistItems.childElementCount >= 50;
            }

            function syncCreateTaskTypeFields() {
                const checklistSelected = createTaskType.value === 'checklist';
                createDescriptionField.classList.toggle('hidden', checklistSelected);
                createDescriptionField.querySelector('textarea').disabled = checklistSelected;
                createChecklistField.classList.toggle('hidden', !checklistSelected);
                createChecklistItems.querySelectorAll('input[name="checklist_items[]"]').forEach(input => {
                    input.disabled = !checklistSelected;
                    input.required = checklistSelected;
                });

                if (checklistSelected && !createChecklistItems.childElementCount) {
                    addChecklistInputRow();
                }
            }

            function resetCreateTaskTypeFields() {
                createChecklistItems.replaceChildren();
                document.getElementById('create-checklist-add').disabled = false;
                createTaskType.value = 'checklist';
                syncCreateTaskTypeFields();
            }

            function updateLocalizedTaskControls() {
                if (assigneeSelect.options.length && !assigneeSelect.value) {
                    assigneeSelect.options[0].text = localizedText('Leave unassigned', 'Tidak ditugaskan');
                }
                if (currentTask) {
                    renderAttachments(currentTask.attachments?.data || currentTask.attachments || []);
                }
            }

            syncCreateTaskTypeFields();
            updateLocalizedTaskControls();
            window.addEventListener('hotel-task-language-change', updateLocalizedTaskControls);

            async function loadDepartments() {
                const propertiesResponse = await api('/api/v1/properties');
                const properties = propertiesResponse.data || [];
                const membershipIds = new Set((actor?.departments || []).map(department => department.id));
                const canViewAllDepartments = actor?.role === 'Admin';
                const groups = await Promise.all(properties.map(async property => {
                    const response = await api(`/api/v1/properties/${encodeURIComponent(property.id)}/departments`);
                    return (response.data || []).filter(department => canViewAllDepartments || membershipIds.has(department.id)).map(department => ({ ...department, propertyId: property.id, propertyName: property.name }));
                }));
                departments = groups.flat();
                const filter = document.getElementById('department-filter');
                filter.replaceChildren(option('All accessible departments', ''));
                departments.forEach(department => filter.add(option(`${department.propertyName} · ${department.name}`, department.id)));
                if (trackerDepartmentFilter) {
                    const allDepartmentsOption = option('All accessible departments', '');
                    trackerDepartmentFilter.replaceChildren(allDepartmentsOption);
                    departments.forEach(department => trackerDepartmentFilter.add(option(`${department.propertyName} · ${department.name}`, department.id)));
                }
                createDepartment.replaceChildren(...departments.map(department => option(`${department.propertyName} · ${department.name}`, department.id)));
                subscribeToTaskTrackerUpdates();
            }

            function subscribeToTaskTrackerUpdates(echo = window.Echo) {
                if (!echo) return;

                if (actor?.id && subscribedUserId !== actor.id) {
                    subscribedUserId = actor.id;
                    echo.private(`users.${actor.id}`).listen('.task.tracker.changed', refreshTaskViews);
                }

                departments.forEach(department => {
                    if (subscribedDepartmentIds.has(department.id)) return;

                    subscribedDepartmentIds.add(department.id);
                    echo.private(`departments.${department.id}`).listen('.task.tracker.changed', refreshTaskViews);
                });
            }

            function refreshTaskViews(event) {
                const trackerDepartmentId = trackerDepartmentFilter?.value || '';
                const taskDepartmentId = document.getElementById('department-filter').value;

                window.clearTimeout(trackerRefreshTimeout);
                trackerRefreshTimeout = window.setTimeout(() => {
                    if (!trackerDepartmentId || trackerDepartmentId === event.department_id) {
                        loadTaskTracker();
                    }
                    if (!taskDepartmentId || taskDepartmentId === event.department_id) {
                        loadTasks();
                    }
                }, 150);
            }

            window.addEventListener('hotel-task-echo-ready', event => {
                subscribeToTaskTrackerUpdates(event.detail?.echo);
            });

            async function loadTasks() {
                list.replaceChildren(Object.assign(document.createElement('p'), { textContent: 'Loading tasks…', className: 'px-5 py-10 text-center text-sm text-slate-400' }));
                try {
                    const query = new URLSearchParams({ page: String(currentPage) });
                    const departmentId = document.getElementById('department-filter').value;
                    const status = document.getElementById('status-filter').value;
                    if (departmentId) query.set('department_id', departmentId);
                    if (status) query.set('status', status);
                    const response = await api(`/api/v1/tasks?${query}`);
                    tasks = response.data || [];
                    lastPage = response.meta?.last_page || 1;
                    renderTasks();
                    document.getElementById('tasks-previous').disabled = currentPage <= 1;
                    document.getElementById('tasks-next').disabled = currentPage >= lastPage;
                    document.getElementById('task-count').textContent = `${response.meta?.total ?? tasks.length} tasks`;
                } catch (error) { list.replaceChildren(); notice(error.message); }
            }

            async function loadTaskTracker() {
                if (!trackerSection) return;

                const query = new URLSearchParams({
                    period: trackerPeriodFilter.value,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
                });
                if (trackerDepartmentFilter.value) query.set('department_id', trackerDepartmentFilter.value);

                try {
                    const response = await api(`/api/v1/tasks/dashboard?${query}`);
                    document.getElementById('tracker-created-today').textContent = response.period_counts?.day ?? 0;
                    document.getElementById('tracker-created-week').textContent = response.period_counts?.week ?? 0;
                    document.getElementById('tracker-created-month').textContent = response.period_counts?.month ?? 0;
                    document.getElementById('tracker-status-created').textContent = response.statuses?.created ?? 0;
                    document.getElementById('tracker-status-assigned').textContent = response.statuses?.assigned ?? 0;
                    document.getElementById('tracker-status-done').textContent = response.statuses?.done ?? 0;
                    document.getElementById('tracker-timezone').textContent = `Time zone: ${response.timezone || 'UTC'}`;
                    renderTrackerChart(response.chart?.data || []);
                    renderRecentTasks(response.tasks || []);
                    document.getElementById('tracker-error').classList.add('hidden');
                } catch (error) {
                    const message = document.getElementById('tracker-error');
                    message.textContent = error.message;
                    message.classList.remove('hidden');
                }
            }

            function renderTrackerChart(points) {
                const container = document.getElementById('tracker-chart');
                container.replaceChildren();
                if (!points.length || points.every(point => Number(point.total) === 0)) {
                    container.textContent = 'No task data for this period.';
                    return;
                }

                const width = 900;
                const height = 260;
                const left = 44;
                const right = 16;
                const top = 16;
                const bottom = 48;
                const plotWidth = width - left - right;
                const plotHeight = height - top - bottom;
                const max = Math.max(1, ...points.map(point => Number(point.total)));
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
                svg.setAttribute('class', 'h-64 w-full');
                svg.setAttribute('preserveAspectRatio', 'none');

                for (let line = 0; line <= 4; line++) {
                    const y = top + plotHeight * line / 4;
                    const grid = document.createElementNS(svg.namespaceURI, 'line');
                    grid.setAttribute('x1', left);
                    grid.setAttribute('x2', width - right);
                    grid.setAttribute('y1', y);
                    grid.setAttribute('y2', y);
                    grid.setAttribute('stroke', '#e2e8f0');
                    svg.append(grid);
                }

                const slot = plotWidth / points.length;
                const barWidth = Math.max(3, Math.min(34, slot * 0.62));
                points.forEach((point, index) => {
                    const barHeight = Number(point.total) / max * plotHeight;
                    const bar = document.createElementNS(svg.namespaceURI, 'rect');
                    bar.setAttribute('x', left + index * slot + (slot - barWidth) / 2);
                    bar.setAttribute('y', top + plotHeight - barHeight);
                    bar.setAttribute('width', barWidth);
                    bar.setAttribute('height', Math.max(1, barHeight));
                    bar.setAttribute('rx', '4');
                    bar.setAttribute('fill', '#047857');
                    const title = document.createElementNS(svg.namespaceURI, 'title');
                    title.textContent = `${point.label}: ${point.total} tasks`;
                    bar.append(title);
                    svg.append(bar);

                    if (points.length <= 14 || index % Math.ceil(points.length / 10) === 0 || index === points.length - 1) {
                        const label = document.createElementNS(svg.namespaceURI, 'text');
                        label.setAttribute('x', left + index * slot + slot / 2);
                        label.setAttribute('y', height - 18);
                        label.setAttribute('text-anchor', 'middle');
                        label.setAttribute('fill', '#64748b');
                        label.setAttribute('font-size', '10');
                        label.textContent = point.label;
                        svg.append(label);
                    }
                });
                container.append(svg);
            }

            function renderRecentTasks(recentTasks) {
                const container = document.getElementById('tracker-recent-tasks');
                container.replaceChildren();
                if (!recentTasks.length) {
                    const empty = document.createElement('p');
                    empty.className = 'px-5 py-6 text-sm text-slate-400';
                    empty.textContent = 'No tasks to display.';
                    container.append(empty);
                    return;
                }

                recentTasks.slice(0, 5).forEach(task => {
                    const row = document.createElement('article');
                    row.className = 'flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between';
                    const title = document.createElement('h4');
                    title.className = 'font-medium text-slate-800';
                    title.textContent = task.title;
                    const department = document.createElement('p');
                    department.className = 'mt-1 text-xs text-slate-500';
                    department.textContent = task.department?.name || 'No department';
                    const progress = document.createElement('span');
                    progress.className = 'w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800';
                    progress.textContent = `${task.progress_percentage ?? 0}% complete`;
                    const details = document.createElement('div');
                    details.append(title, department);
                    row.append(details, progress);
                    container.append(row);
                });
            }

            function renderTasks() {
                const filteredTasks = tasks;
                list.replaceChildren();
                if (!filteredTasks.length) {
                    const empty = document.createElement('p'); empty.className = 'px-5 py-10 text-center text-sm text-slate-400'; empty.textContent = 'No tasks match these filters.'; list.append(empty); return;
                }
                filteredTasks.forEach(task => {
                    const row = document.createElement('article'); row.className = 'flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between';
                    const info = document.createElement('div'); info.className = 'min-w-0 flex-1';
                    const title = document.createElement('h3'); title.className = 'font-semibold text-slate-800'; title.textContent = task.title;
                    const meta = document.createElement('p'); meta.className = 'mt-1 text-xs text-slate-500'; meta.textContent = [task.property?.name, task.department?.name, task.assignee?.name || 'Unassigned', task.due_date ? `Due ${task.due_date}` : null].filter(Boolean).join(' · ');
                    const progressTrack = document.createElement('div'); progressTrack.className = 'mt-3 h-1.5 max-w-xl overflow-hidden rounded-full bg-slate-100'; progressTrack.setAttribute('role', 'progressbar'); progressTrack.setAttribute('aria-label', 'Task progress'); progressTrack.setAttribute('aria-valuemin', '0'); progressTrack.setAttribute('aria-valuemax', '100'); progressTrack.setAttribute('aria-valuenow', String(task.progress_percentage ?? 0));
                    const progressFill = document.createElement('div'); progressFill.className = 'h-full rounded-full bg-emerald-600'; progressFill.style.width = `${task.progress_percentage ?? 0}%`; progressTrack.append(progressFill);
                    info.append(title, meta, progressTrack);
                    const actions = document.createElement('div'); actions.className = 'flex flex-wrap items-center gap-2 sm:shrink-0';
                    const statusLabels = { NOT_STARTED: 'Created', IN_PROGRESS: 'In progress', DONE: 'Completed' };
                    const progress = document.createElement('span'); progress.className = 'rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800'; progress.textContent = `${task.progress_percentage}% · ${statusLabels[task.status] || task.status}`;
                    actions.append(progress);
                    const isCurrentUserAssignee = String(task.assigned_to ?? task.assignee?.id ?? '') === String(actor?.id ?? '');
                    const canCompleteTask = isCurrentUserAssignee && task.completion_ready === true;
                    const complete = document.createElement('button');
                    complete.type = 'button';
                    complete.className = canCompleteTask
                        ? 'rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed'
                        : 'rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed';
                    complete.textContent = task.status === 'DONE'
                        ? localizedText('Completed', 'Selesai')
                        : localizedText('Mark as complete', 'Tandai selesai');
                    complete.disabled = !canCompleteTask || task.status === 'DONE';
                    if (canCompleteTask && task.status !== 'DONE') {
                        complete.addEventListener('click', async () => {
                            complete.disabled = true;
                            try {
                                await api(`/api/v1/tasks/${encodeURIComponent(task.id)}/complete`, { method: 'PATCH' });
                                notice('Task marked as completed.', true);
                                await loadTasks();
                            } catch (error) {
                                complete.disabled = false;
                                notice(error.message);
                            }
                        });
                    }
                    if (canCreate && task.due_date && task.status !== 'DONE') {
                        const extendDueDate = document.createElement('button');
                        extendDueDate.type = 'button';
                        extendDueDate.className = 'rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50';
                        extendDueDate.textContent = localizedText('Extend due date', 'Perpanjang due date');
                        extendDueDate.addEventListener('click', () => openDueDateModal(task));
                        actions.append(extendDueDate);
                    }
                    const open = document.createElement('button'); open.type = 'button'; open.className = 'rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50'; open.textContent = 'Open'; open.addEventListener('click', () => openTask(task.id));
                    actions.append(complete, open); row.append(info, actions); list.append(row);
                });
            }

            function openDueDateModal(task) {
                if (!canCreate || !task.due_date || task.status === 'DONE') return;

                taskToExtendDueDate = task;
                dueDateForm.reset();
                dueDateInput.value = String(task.due_date).slice(0, 10);
                document.getElementById('task-due-date-task-title').textContent = task.title;
                dueDateError.textContent = '';
                dueDateError.classList.add('hidden');
                dueDateModal.classList.remove('hidden');
                dueDateModal.classList.add('flex');
                dueDateInput.focus();
            }

            function closeDueDateModal() {
                dueDateModal.classList.add('hidden');
                dueDateModal.classList.remove('flex');
                taskToExtendDueDate = null;
            }

            async function loadAssignees(departmentId) {
                availableAssignees = [];
                assigneeSelect.replaceChildren(option(localizedText('Leave unassigned', 'Tidak ditugaskan'), ''));
                assigneeSelect.disabled = true;
                if (!departmentId) return;

                const response = await api(`/api/v1/tasks/assignees?department_id=${encodeURIComponent(departmentId)}`);
                availableAssignees = response.data || [];
                availableAssignees
                    .forEach(user => assigneeSelect.add(option(`${user.name} · ${user.role} · ${user.property_name}`, user.id)));
                assigneeSelect.disabled = availableAssignees.length === 0;
            }

            async function openTask(taskId) {
                try {
                    const response = await api(`/api/v1/tasks/${encodeURIComponent(taskId)}`);
                    currentTask = response.data;
                    document.getElementById('task-detail-title').textContent = currentTask.title;
                    const descriptionElement = document.getElementById('task-detail-description');
                    descriptionElement.textContent = currentTask.description || 'No description provided.';
                    descriptionElement.classList.toggle('hidden', currentTask.task_type === 'checklist');
                    const statusLabels = { NOT_STARTED: 'Created', IN_PROGRESS: 'In progress', DONE: 'Completed' };
                    document.getElementById('task-detail-meta').textContent = `${currentTask.property?.name || ''} · ${currentTask.department?.name || ''} · ${statusLabels[currentTask.status] || currentTask.status}`;
                    const isTaskCreator = currentTask.creator?.id === actor?.id;
                    const isTaskAssignee = currentTask.assignee?.id === actor?.id && currentTask.creator?.id !== actor?.id;
                    const progress = Number(currentTask.progress_percentage) || 0;
                    const progressMeter = document.getElementById('task-progress-meter');
                    progressMeter.setAttribute('aria-valuenow', String(progress));
                    document.getElementById('task-progress-fill').style.width = `${progress}%`;
                    document.getElementById('task-progress-value').textContent = `${progress}%`;
                    const progressInput = document.getElementById('task-progress');
                    progressInput.value = String(progress);
                    progressInput.oninput = event => {
                        const value = Number(event.currentTarget.value);
                        document.getElementById('task-progress-value').textContent = `${value}%`;
                        document.getElementById('task-progress-fill').style.width = `${value}%`;
                        progressMeter.setAttribute('aria-valuenow', String(value));
                    };
                    const canSetManualProgress = canUpdateAssigned && isTaskAssignee && currentTask.task_type === 'description';
                    document.getElementById('task-progress-controls').classList.toggle('hidden', !canSetManualProgress);
                    const checklistItems = currentTask.checklist_items?.data || currentTask.checklist_items || [];
                    document.getElementById('task-checklist-section').classList.toggle('hidden', currentTask.task_type !== 'checklist');
                    renderChecklist(checklistItems, canUpdateAssigned && isTaskAssignee && currentTask.task_type === 'checklist');
                    const canManageTaskSubtasks = canManageSubtasks && isTaskAssignee;
                    const canWriteTaskComments = canComment && (isTaskCreator || isTaskAssignee);
                    document.getElementById('subtask-form').classList.toggle('hidden', !canManageTaskSubtasks);
                    document.getElementById('subtask-form').classList.toggle('flex', canManageTaskSubtasks);
                    document.getElementById('comment-form').classList.toggle('hidden', !canWriteTaskComments);
                    document.getElementById('comment-form').classList.toggle('flex', canWriteTaskComments);
                    document.getElementById('task-delete').classList.toggle('hidden', !(canManage || (canCreate && isTaskCreator)));
                    const canUploadAttachments = isTaskCreator;
                    attachmentForm.classList.toggle('hidden', !canUploadAttachments);
                    attachmentForm.classList.toggle('flex', canUploadAttachments);
                    renderSubtasks(currentTask.subtasks?.data || currentTask.subtasks || []);
                    renderAttachments(currentTask.attachments?.data || currentTask.attachments || []);
                    await loadComments(currentTask.id);
                    detailModal.classList.remove('hidden'); detailModal.classList.add('flex');
                } catch (error) { notice(error.message); }
            }

            function renderAttachments(attachments) {
                attachmentPreviewUrls.forEach(url => URL.revokeObjectURL(url));
                attachmentPreviewUrls = [];
                const container = document.getElementById('task-attachment-list');
                container.replaceChildren();
                document.getElementById('task-attachment-count').textContent = localStorage.getItem('hotel_task_language') === 'id'
                    ? `${attachments.length} file`
                    : `${attachments.length} file${attachments.length === 1 ? '' : 's'}`;
                if (!attachments.length) {
                    const empty = document.createElement('p');
                    empty.className = 'text-sm text-slate-400';
                    empty.textContent = localizedText('No attachments.', 'Belum ada lampiran.');
                    container.append(empty);
                    return;
                }

                attachments.forEach(attachment => {
                    const row = document.createElement('article');
                    row.className = 'rounded-lg border border-slate-100 p-3';
                    const header = document.createElement('div');
                    header.className = 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between';
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'min-w-0';
                    const fileName = document.createElement('p');
                    fileName.className = 'break-all text-sm font-medium text-slate-800';
                    fileName.textContent = attachment.file_name;
                    const fileSize = document.createElement('p');
                    fileSize.className = 'mt-1 text-xs text-slate-500';
                    fileSize.textContent = formatFileSize(attachment.size_bytes);
                    fileInfo.append(fileName, fileSize);

                    const actions = document.createElement('div');
                    actions.className = 'flex shrink-0 gap-2';
                    if (attachment.mime_type?.startsWith('image/')) {
                        const previewButton = document.createElement('button');
                        previewButton.type = 'button';
                        previewButton.className = 'rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50';
                        previewButton.textContent = localizedText('Preview', 'Pratinjau');
                        const preview = document.createElement('img');
                        preview.className = 'mt-3 hidden max-h-80 max-w-full rounded-lg border border-slate-100 object-contain';
                        preview.alt = attachment.file_name;
                        previewButton.addEventListener('click', async () => {
                            if (preview.src) {
                                preview.classList.toggle('hidden');
                                return;
                            }
                            try {
                                const blob = await fetchAttachment(attachment);
                                const previewUrl = URL.createObjectURL(blob);
                                attachmentPreviewUrls.push(previewUrl);
                                preview.src = previewUrl;
                                preview.classList.remove('hidden');
                            } catch (error) {
                                notice(error.message);
                            }
                        });
                        actions.append(previewButton);
                        row.append(header, preview);
                    } else {
                        row.append(header);
                    }

                    const downloadButton = document.createElement('button');
                    downloadButton.type = 'button';
                    downloadButton.className = 'rounded-lg bg-[#101b2d] px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800';
                    downloadButton.textContent = localizedText('Download', 'Unduh');
                    downloadButton.addEventListener('click', async () => {
                        try {
                            const blob = await fetchAttachment(attachment);
                            const url = URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = attachment.file_name;
                            link.click();
                            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
                        } catch (error) {
                            notice(error.message);
                        }
                    });
                    actions.append(downloadButton);
                    header.append(fileInfo, actions);
                    container.append(row);
                });
            }

            async function fetchAttachment(attachment) {
                const response = await fetch(attachment.download_url, {
                    headers: { Accept: 'application/octet-stream', Authorization: `Bearer ${token}` },
                });
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(error.message || 'The attachment could not be downloaded.');
                }

                return response.blob();
            }

            function formatFileSize(sizeBytes) {
                const bytes = Number(sizeBytes) || 0;
                return bytes < 1024 * 1024
                    ? `${Math.max(1, Math.round(bytes / 1024))} KB`
                    : `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
            }

            function renderSubtasks(subtasks) {
                const container = document.getElementById('subtask-list'); container.replaceChildren();
                const canEditSubtasks = canManageSubtasks && currentTask?.assignee?.id === actor?.id;
                document.getElementById('subtask-count').textContent = `${subtasks.filter(item => item.is_completed).length}/${subtasks.length} complete`;
                if (!subtasks.length) { const empty = document.createElement('p'); empty.className = 'text-sm text-slate-400'; empty.textContent = 'No subtasks yet.'; container.append(empty); }
                subtasks.forEach(subtask => {
                    const row = document.createElement('article'); row.className = 'flex items-center gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm';
                    const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.checked = subtask.is_completed; checkbox.disabled = !canEditSubtasks; checkbox.setAttribute('aria-label', `Mark ${subtask.title} complete`); checkbox.addEventListener('change', async () => { try { await api(`/api/v1/tasks/${currentTask.id}/subtasks/${subtask.id}/toggle`, { method: 'PATCH' }); await loadTasks(); await openTask(currentTask.id); } catch (error) { notice(error.message); } });
                    const title = document.createElement('span'); title.className = `min-w-0 flex-1 ${subtask.is_completed ? 'text-slate-400 line-through' : 'text-slate-700'}`; title.textContent = subtask.title; row.append(checkbox, title);
                    if (canEditSubtasks) {
                        const removeButton = document.createElement('button'); removeButton.type = 'button'; removeButton.className = 'rounded-md px-2 text-lg leading-none text-slate-400 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-50'; removeButton.textContent = '×'; removeButton.setAttribute('aria-label', localizedText(`Delete subtask: ${subtask.title}`, `Hapus subtugas: ${subtask.title}`)); removeButton.title = localizedText('Delete subtask', 'Hapus subtugas');
                        removeButton.addEventListener('click', async () => {
                            removeButton.disabled = true;
                            try { await api(`/api/v1/tasks/${currentTask.id}/subtasks/${subtask.id}`, { method: 'DELETE' }); await loadTasks(); await openTask(currentTask.id); }
                            catch (error) { removeButton.disabled = false; notice(error.message); }
                        });
                        row.append(removeButton);
                    }
                    container.append(row);
                });
            }

            function renderChecklist(checklistItems, canToggleItems) {
                const container = document.getElementById('task-checklist-list');
                container.replaceChildren();
                const completedItems = checklistItems.filter(item => item.is_completed).length;
                document.getElementById('task-checklist-count').textContent = `${completedItems}/${checklistItems.length} ${localizedText('complete', 'selesai')}`;

                if (!checklistItems.length) {
                    const empty = document.createElement('p');
                    empty.className = 'text-sm text-slate-400';
                    empty.textContent = localizedText('No checklist items.', 'Belum ada item checklist.');
                    container.append(empty);
                    return;
                }

                checklistItems.forEach(item => {
                    const row = document.createElement('label');
                    row.className = 'flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 text-sm';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.checked = item.is_completed;
                    checkbox.disabled = !canToggleItems;
                    checkbox.setAttribute('aria-label', localizedText(
                        `${item.is_completed ? 'Uncheck' : 'Complete'}: ${item.title}`,
                        `${item.is_completed ? 'Lepas centang' : 'Tandai selesai'}: ${item.title}`,
                    ));
                    checkbox.addEventListener('change', async () => {
                        const previousValue = item.is_completed;
                        checkbox.disabled = true;
                        try {
                            await api(`/api/v1/tasks/${currentTask.id}/checklist-items/${item.id}/toggle`, { method: 'PATCH' });
                            await loadTasks();
                            await openTask(currentTask.id);
                        } catch (error) {
                            checkbox.checked = previousValue;
                            checkbox.disabled = false;
                            notice(error.message);
                        }
                    });
                    const title = document.createElement('span');
                    title.className = `min-w-0 flex-1 ${item.is_completed ? 'text-slate-400 line-through' : 'text-slate-700'}`;
                    title.textContent = item.title;
                    row.append(checkbox, title);
                    container.append(row);
                });
            }

            async function loadComments(taskId) {
                const container = document.getElementById('comment-list'); container.replaceChildren();
                const canAccessComments = canComment && (currentTask?.creator?.id === actor?.id || currentTask?.assignee?.id === actor?.id);
                if (!canAccessComments) { const empty = document.createElement('p'); empty.className = 'text-sm text-slate-400'; empty.textContent = 'You do not have access to comments.'; container.append(empty); return; }
                try {
                    const response = await api(`/api/v1/tasks/${taskId}/comments`);
                    (response.data?.data || response.data || []).forEach(comment => {
                        const entry = document.createElement('article'); entry.className = 'flex items-start justify-between gap-3 rounded-lg bg-slate-50 p-3';
                        const content = document.createElement('div'); content.className = 'min-w-0';
                        const author = document.createElement('p'); author.className = 'text-xs font-semibold text-slate-700'; author.textContent = comment.user?.name || 'User';
                        const body = document.createElement('p'); body.className = 'mt-1 whitespace-pre-wrap text-sm text-slate-600'; body.textContent = comment.comment;
                        content.append(author, body); entry.append(content);
                        if (comment.user?.id === actor?.id) {
                            const removeButton = document.createElement('button'); removeButton.type = 'button'; removeButton.className = 'shrink-0 rounded-md px-2 text-lg leading-none text-slate-400 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-50'; removeButton.textContent = '×'; removeButton.setAttribute('aria-label', localizedText('Delete comment', 'Hapus komentar')); removeButton.title = localizedText('Delete comment', 'Hapus komentar');
                            removeButton.addEventListener('click', async () => {
                                removeButton.disabled = true;
                                try { await api(`/api/v1/tasks/${taskId}/comments/${comment.id}`, { method: 'DELETE' }); await loadComments(taskId); }
                                catch (error) { removeButton.disabled = false; notice(error.message); }
                            });
                            entry.append(removeButton);
                        }
                        container.append(entry);
                    });
                    if (!container.childElementCount) { const empty = document.createElement('p'); empty.className = 'text-sm text-slate-400'; empty.textContent = 'No comments yet.'; container.append(empty); }
                } catch (error) { const empty = document.createElement('p'); empty.className = 'text-sm text-rose-700'; empty.textContent = error.message; container.append(empty); }
            }

            document.getElementById('new-task-open').classList.toggle('hidden', !canCreate);
            document.getElementById('new-task-open').addEventListener('click', () => {
                if (!canCreate) return;
                createPanel.classList.remove('hidden');
                createPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            document.getElementById('new-task-close').addEventListener('click', () => createPanel.classList.add('hidden'));
            document.getElementById('create-department').addEventListener('change', event => loadAssignees(event.currentTarget.value).catch(error => notice(error.message)));
            createTaskType.addEventListener('change', syncCreateTaskTypeFields);
            document.getElementById('create-checklist-add').addEventListener('click', () => addChecklistInputRow());
            document.getElementById('task-create-form').addEventListener('submit', async event => {
                event.preventDefault();
                if (!canCreate) return;
                const form = event.currentTarget; const values = new FormData(form); const department = departments.find(item => item.id === values.get('department_id'));
                values.set('property_id', department?.propertyId || '');
                if (!document.getElementById('create-attachments').files.length) values.delete('attachments[]');
                try {
                    await api('/api/v1/tasks', { method: 'POST', body: values });
                    form.reset();
                    resetCreateTaskTypeFields();
                    await loadAssignees('');
                    createPanel.classList.add('hidden');
                    currentPage = 1;
                    notice('Task created successfully.', true);
                    await loadTasks();
                    await loadTaskTracker();
                } catch (error) { notice(error.message); }
            });
            attachmentForm.addEventListener('submit', async event => {
                event.preventDefault();
                const files = Array.from(document.getElementById('task-attachment-files').files || []);
                if (!currentTask || !files.length) return;

                const attachmentData = new FormData();
                files.forEach(file => attachmentData.append('attachments[]', file));
                try {
                    await api(`/api/v1/tasks/${encodeURIComponent(currentTask.id)}/attachments`, { method: 'POST', body: attachmentData });
                    document.getElementById('task-attachment-files').value = '';
                    notice('Attachments uploaded.', true);
                    await openTask(currentTask.id);
                } catch (error) {
                    notice(error.message);
                }
            });
            async function submitWithLock(form, busyLabel, action) {
                if (form.dataset.submitting === 'true') return;

                const button = form.querySelector('button[type="submit"]');
                const originalLabel = button.textContent;
                form.dataset.submitting = 'true';
                button.disabled = true;
                button.textContent = busyLabel;

                try {
                    await action();
                } finally {
                    form.dataset.submitting = 'false';
                    button.disabled = false;
                    button.textContent = originalLabel;
                }
            }

            document.getElementById('subtask-form').addEventListener('submit', async event => {
                event.preventDefault();
                const form = event.currentTarget;
                const title = new FormData(form).get('title');
                await submitWithLock(form, localizedText('Adding…', 'Menambahkan…'), async () => {
                    try {
                        await api(`/api/v1/tasks/${currentTask.id}/subtasks`, { method: 'POST', body: JSON.stringify({ title }) });
                        form.reset();
                        await loadTasks();
                        await openTask(currentTask.id);
                    } catch (error) { notice(error.message); }
                });
            });
            document.getElementById('comment-form').addEventListener('submit', async event => {
                event.preventDefault();
                const form = event.currentTarget;
                const comment = new FormData(form).get('comment');
                await submitWithLock(form, localizedText('Sending…', 'Mengirim…'), async () => {
                    try {
                        await api(`/api/v1/tasks/${currentTask.id}/comments`, { method: 'POST', body: JSON.stringify({ comment }) });
                        form.reset();
                        await loadComments(currentTask.id);
                    } catch (error) { notice(error.message); }
                });
            });
            document.getElementById('task-delete').addEventListener('click', async () => { if (!window.confirm('Delete this task?')) return; try { await api(`/api/v1/tasks/${currentTask.id}`, { method: 'DELETE' }); detailModal.classList.add('hidden'); detailModal.classList.remove('flex'); notice('Task deleted.', true); await loadTasks(); } catch (error) { notice(error.message); } });
            dueDateForm.addEventListener('submit', async event => {
                event.preventDefault();
                if (!canCreate || !taskToExtendDueDate || dueDateForm.dataset.submitting === 'true') return;

                const submitButton = document.getElementById('task-due-date-submit');
                dueDateForm.dataset.submitting = 'true';
                submitButton.disabled = true;
                dueDateError.textContent = '';
                dueDateError.classList.add('hidden');

                try {
                    const response = await api(`/api/v1/tasks/${encodeURIComponent(taskToExtendDueDate.id)}/due-date`, {
                        method: 'PATCH',
                        body: JSON.stringify({ due_date: dueDateInput.value }),
                    });
                    closeDueDateModal();
                    notice(response.message || 'Task due date extended.', true);
                    await Promise.all([loadTasks(), loadTaskTracker()]);
                } catch (error) {
                    dueDateError.textContent = error.message;
                    dueDateError.classList.remove('hidden');
                } finally {
                    dueDateForm.dataset.submitting = 'false';
                    submitButton.disabled = false;
                }
            });
            document.getElementById('task-due-date-cancel').addEventListener('click', closeDueDateModal);
            dueDateModal.addEventListener('click', event => {
                if (event.target === dueDateModal) closeDueDateModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !dueDateModal.classList.contains('hidden')) closeDueDateModal();
            });
            document.getElementById('task-progress-save').addEventListener('click', async event => {
                if (!currentTask) return;

                const button = event.currentTarget;
                button.disabled = true;
                try {
                    await api(`/api/v1/tasks/${encodeURIComponent(currentTask.id)}/progress`, {
                        method: 'PATCH',
                        body: JSON.stringify({ progress_percentage: Number(document.getElementById('task-progress').value) }),
                    });
                    notice('Task progress updated.', true);
                    await loadTasks();
                    await openTask(currentTask.id);
                } catch (error) {
                    button.disabled = false;
                    notice(error.message);
                }
            });
            document.getElementById('task-detail-close').addEventListener('click', () => { detailModal.classList.add('hidden'); detailModal.classList.remove('flex'); });
            detailModal.addEventListener('click', event => { if (event.target === detailModal) { detailModal.classList.add('hidden'); detailModal.classList.remove('flex'); } });
            document.getElementById('department-filter').addEventListener('change', () => { currentPage = 1; loadTasks(); });
            document.getElementById('status-filter').addEventListener('change', () => { currentPage = 1; loadTasks(); });
            document.getElementById('tasks-previous').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadTasks(); } });
            document.getElementById('tasks-next').addEventListener('click', () => { if (currentPage < lastPage) { currentPage++; loadTasks(); } });
            trackerDepartmentFilter?.addEventListener('change', loadTaskTracker);
            trackerPeriodFilter?.addEventListener('change', loadTaskTracker);

            const currentUserPromise = window.hotelTaskUserPromise
                ? window.hotelTaskUserPromise.then(user => user ? { data: user } : api('/api/v1/users/me'))
                : api('/api/v1/users/me');

            currentUserPromise
                .then(response => {
                    actor = response.data;
                    privileges = actor.privileges || [];
                    canCreate = privileges.includes('tasks.assign');
                    canManage = privileges.includes('tasks.manage');
                    canUpdateAssigned = privileges.includes('tasks.update_assigned');
                    canManageSubtasks = privileges.includes('subtasks.manage_assigned');
                    canComment = privileges.includes('tasks.comment');
                    sessionStorage.setItem('hotel_task_user', JSON.stringify(actor));
                    document.getElementById('new-task-open').classList.toggle('hidden', !canCreate);
                    return loadDepartments();
                })
                .then(() => Promise.all([loadTasks(), loadTaskTracker()]))
                .catch(error => notice(error.message));

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    loadTasks();
                    loadTaskTracker();
                }
            });
        })();
    </script>
</body>
</html>
