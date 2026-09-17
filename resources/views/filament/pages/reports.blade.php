<x-filament-panels::page>
    @php
        $summary = is_array($this->summary ?? null) ? $this->summary : [];
        $projectsByState = is_array($this->projectsByState ?? null) ? $this->projectsByState : [];
        $monthlyFinance = is_array($this->monthlyFinance ?? null) ? $this->monthlyFinance : [];
        $lateProjects = is_array($this->lateProjects ?? null) ? $this->lateProjects : [];
        $filters = is_array($this->filters ?? null) ? $this->filters : [];
    @endphp

    <style>
        .rp-wrap{direction:rtl;display:flex;flex-direction:column;gap:16px}
        .rp-head{background:linear-gradient(135deg,#ecfdf3,#ffffff);border:1px solid #b7e4c7;border-radius:16px;padding:18px}
        .rp-title{margin:0;font-size:30px;font-weight:900;color:#065f46}
        .rp-sub{margin:6px 0 0;color:#4b5563}
        .rp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
        .rp-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px}
        .rp-lbl{font-size:13px;color:#6b7280}
        .rp-num{font-size:28px;font-weight:900;margin-top:6px;color:#111827}
        .rp-box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px}
        .rp-h3{margin:0 0 10px;font-size:20px;font-weight:900;color:#111827}
        .rp-table{width:100%;border-collapse:collapse}
        .rp-table th,.rp-table td{padding:10px;border-bottom:1px solid #f1f5f9;text-align:right}
        .rp-table th{background:#f8fafc;font-weight:700}
        .rp-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
        .rp-field label{display:block;font-size:13px;margin-bottom:6px;color:#374151;font-weight:700}
        .rp-field select,.rp-field input{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px;background:#fff}
        .rp-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:end}
        .rp-btn{display:inline-block;border:none;border-radius:10px;padding:10px 16px;font-weight:800;text-decoration:none;cursor:pointer}
        .rp-btn-primary{background:#047857;color:#fff}
        .rp-btn-light{background:#f3f4f6;color:#111827}
        .rp-badge{display:inline-block;background:#fef3c7;color:#92400e;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}
    </style>

    <div class="rp-wrap">
        <div class="rp-head">
            <h1 class="rp-title">التقارير</h1>
            <p class="rp-sub">تقارير المشاريع والحركة المالية مع فلترة وتصدير</p>
        </div>

        <div class="rp-box">
            <h3 class="rp-h3">الفلاتر</h3>

            <form method="GET" action="{{ url()->current() }}" class="rp-form">
                <div class="rp-field">
                    <label>الدولة</label>
                    <select name="country_id">
                        <option value="">الكل</option>
                        @foreach (($this->countries ?? []) as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['country_id'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rp-field">
                    <label>الجهة</label>
                    <select name="organization_id">
                        <option value="">الكل</option>
                        @foreach (($this->organizations ?? []) as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['organization_id'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rp-field">
                    <label>الحالة</label>
                    <select name="state">
                        <option value="">الكل</option>
                        <option value="new" @selected(($filters['state'] ?? '') === 'new')>جديد</option>
                        <option value="pending_readiness" @selected(($filters['state'] ?? '') === 'pending_readiness')>بانتظار الجاهزية</option>
                        <option value="ready_for_execution" @selected(($filters['state'] ?? '') === 'ready_for_execution')>جاهز للتنفيذ</option>
                        <option value="in_execution" @selected(($filters['state'] ?? '') === 'in_execution')>قيد التنفيذ</option>
                        <option value="pending_documentation" @selected(($filters['state'] ?? '') === 'pending_documentation')>بانتظار التوثيق</option>
                        <option value="delayed" @selected(($filters['state'] ?? '') === 'delayed')>متأخر</option>
                        <option value="completed" @selected(($filters['state'] ?? '') === 'completed')>مكتمل</option>
                        <option value="closed" @selected(($filters['state'] ?? '') === 'closed')>مغلق</option>
                    </select>
                </div>

                <div class="rp-field">
                    <label>من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="rp-field">
                    <label>إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="rp-actions">
                    <button class="rp-btn rp-btn-primary" type="submit">تطبيق الفلاتر</button>
                    <a class="rp-btn rp-btn-light" href="{{ url()->current() }}">إعادة ضبط</a>
                    <a class="rp-btn rp-btn-primary" href="{{ route('reports.export', request()->query()) }}">تصدير Excel</a>
                </div>
            </form>
        </div>

        <div class="rp-grid">
            <div class="rp-card">
                <div class="rp-lbl">إجمالي المشاريع</div>
                <div class="rp-num">{{ number_format(data_get($summary, 'total_projects', 0)) }}</div>
            </div>
            <div class="rp-card">
                <div class="rp-lbl">المشاريع المتأخرة</div>
                <div class="rp-num">{{ number_format(data_get($summary, 'delayed_projects', 0)) }}</div>
            </div>
            <div class="rp-card">
                <div class="rp-lbl">إجمالي الوارد</div>
                <div class="rp-num">{{ number_format(data_get($summary, 'incoming_total', 0), 2) }}</div>
            </div>
            <div class="rp-card">
                <div class="rp-lbl">إجمالي الصادر</div>
                <div class="rp-num">{{ number_format(data_get($summary, 'outgoing_total', 0), 2) }}</div>
            </div>
        </div>

        <div class="rp-box">
            <h3 class="rp-h3">توزيع المشاريع حسب الحالة</h3>
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>الحالة</th>
                        <th>العدد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projectsByState as $row)
                        <tr>
                            <td>{{ data_get($row, 'state', '-') }}</td>
                            <td>{{ number_format((int) data_get($row, 'total', 0)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rp-box">
            <h3 class="rp-h3">آخر 6 أشهر مالية</h3>
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>الشهر</th>
                        <th>الوارد</th>
                        <th>الصادر</th>
                        <th>الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($monthlyFinance as $row)
                        <tr>
                            <td>{{ data_get($row, 'month', '-') }}</td>
                            <td>{{ number_format((float) data_get($row, 'incoming_total', 0), 2) }}</td>
                            <td>{{ number_format((float) data_get($row, 'outgoing_total', 0), 2) }}</td>
                            <td>{{ number_format((float) data_get($row, 'balance', 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rp-box">
            <h3 class="rp-h3">المشاريع المتأخرة</h3>
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>رقم المشروع</th>
                        <th>اسم المشروع</th>
                        <th>الحالة</th>
                        <th>تاريخ الانتهاء المتوقع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lateProjects as $row)
                        <tr>
                            <td>{{ data_get($row, 'project_number', '-') }}</td>
                            <td>{{ data_get($row, 'title', '-') }}</td>
                            <td><span class="rp-badge">{{ data_get($row, 'state', '-') }}</span></td>
                            <td>{{ data_get($row, 'expected_end_date', '-') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">لا توجد مشاريع متأخرة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
