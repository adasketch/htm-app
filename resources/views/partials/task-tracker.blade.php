<div>
    <!-- Very little is needed to make a happy life. - Marcus Aurelius -->
</div>
<section id="task-tracker" class="space-y-5 scroll-mt-6" aria-labelledby="task-tracker-heading">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-700" data-language-id="Analitik task" data-language-en="Task analytics">Task analytics</p>
            <h2 id="task-tracker-heading" class="mt-2 text-2xl font-semibold tracking-tight">Task Tracker</h2>
            <p class="mt-1 text-sm text-slate-500" data-language-id="Ringkasan task berdasarkan periode dan departemen yang dapat Anda akses." data-language-en="Task summary by period and department within your access.">Task summary by period and department within your access.</p>
        </div>
        <label class="text-sm text-slate-600" for="tracker-department-filter">
            <span data-language-id="Departemen" data-language-en="Department">Department</span>
            <select id="tracker-department-filter" class="mt-1 block min-w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 outline-none focus:border-emerald-700">
                <option value="" data-language-id="Semua departemen yang dapat diakses" data-language-en="All accessible departments">All accessible departments</option>
            </select>
        </label>
    </div>

    <div id="tracker-error" role="status" aria-live="polite" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"></div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500" data-language-id="Tugas dibuat hari ini" data-language-en="Tasks created today">Tasks created today</p><p id="tracker-created-today" class="mt-3 text-4xl font-semibold tracking-tight">—</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500" data-language-id="Tugas dibuat minggu ini" data-language-en="Tasks created this week">Tasks created this week</p><p id="tracker-created-week" class="mt-3 text-4xl font-semibold tracking-tight">—</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500" data-language-id="Tugas dibuat bulan ini" data-language-en="Tasks created this month">Tasks created this month</p><p id="tracker-created-month" class="mt-3 text-4xl font-semibold tracking-tight">—</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Created · <span data-language-id="belum ditugaskan" data-language-en="unassigned">unassigned</span></p><p id="tracker-status-created" class="mt-3 text-4xl font-semibold tracking-tight">—</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Assigned · <span data-language-id="belum selesai" data-language-en="in progress">in progress</span></p><p id="tracker-status-assigned" class="mt-3 text-4xl font-semibold tracking-tight">—</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Done · <span data-language-id="selesai" data-language-en="completed">completed</span></p><p id="tracker-status-done" class="mt-3 text-4xl font-semibold tracking-tight">—</p></article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div><h3 class="font-semibold" data-language-id="Total tugas" data-language-en="Total tasks">Total tasks</h3><p id="tracker-timezone" class="mt-1 text-xs text-slate-400"></p></div>
            <label class="text-sm text-slate-600" for="tracker-period-filter"><span data-language-id="Periode grafik" data-language-en="Chart period">Chart period</span>
                <select id="tracker-period-filter" class="ml-2 rounded-xl border border-slate-200 bg-white px-3 py-2 outline-none focus:border-emerald-700">
                    <option value="day" data-language-id="Per hari" data-language-en="Daily">Daily</option><option value="week" data-language-id="Per minggu" data-language-en="Weekly">Weekly</option><option value="month" data-language-id="Per bulan" data-language-en="Monthly">Monthly</option><option value="year" data-language-id="Per tahun" data-language-en="Yearly">Yearly</option>
                </select>
            </label>
        </div>
        <div id="tracker-chart" class="mt-6 min-h-64" role="img" aria-label="Task count chart"></div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-semibold" data-language-id="Tugas terbaru" data-language-en="Recent tasks">Recent tasks</h3><p class="mt-1 text-sm text-slate-500" data-language-id="Lima task terakhir sesuai departemen yang dipilih." data-language-en="The five latest tasks in the selected department.">The five latest tasks in the selected department.</p></div>
        <div id="tracker-recent-tasks" class="max-h-80 divide-y divide-slate-100 overflow-y-auto overscroll-contain scroll-smooth"><p class="px-5 py-6 text-sm text-slate-400" data-language-id="Memuat task…" data-language-en="Loading tasks…">Loading tasks…</p></div>
    </section>
</section>
