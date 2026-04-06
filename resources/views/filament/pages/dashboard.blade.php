<x-filament-panels::page>
    <style>
        .db-wrap{direction:rtl;display:flex;flex-direction:column;gap:18px}
        .db-hero{
            padding: 30px 25px;border-radius:24px;
            background:linear-gradient(135deg,#ffffff 0%,#f7fbf8 55%,#eef7f1 100%);
            border:1px solid #deece3;box-shadow:0 18px 40px rgba(15,23,42,.05)
        }
        .db-hero-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:18px;align-items:center}
        .db-kicker{font-size:12px;font-weight:800;color:#16824a}
        .db-title{font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.6;
    color: #0f172a;
    margin: 8px 0 6px;
    padding: 20px 0;}
        .db-sub{font-size:.95rem;color:#5b6677;max-width:620px}
        .db-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
        .db-btn{padding:10px 16px;border-radius:14px;text-decoration:none;font-weight:800;font-size:.95rem}
        .db-btn-primary{background:#167b44;color:#fff}
        .db-btn-soft{background:#fff;color:#0f172a;border:1px solid #dce7df}
        .db-mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .db-mini{
            background:#fff;border:1px solid #e1ece5;border-radius:18px;padding:14px;
            box-shadow:0 10px 25px rgba(15,23,42,.04)
        }
        .db-mini-label{font-size:.88rem;color:#64748b}
        .db-mini-value{font-size:1.5rem;font-weight:900;color:#0f172a;margin-top:4px}
        .db-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
        .db-card{
            background:#fff;border:1px solid #e1ece5;border-radius:22px;padding:16px;
            box-shadow:0 12px 30px rgba(15,23,42,.04)
        }
        .db-card-label{font-size:.9rem;color:#64748b;font-weight:700}
        .db-card-value{font-size:1.8rem;font-weight:900;color:#0f172a;margin-top:8px}
        .db-card-note{font-size:.83rem;color:#7b8794;margin-top:4px}
        .db-panels{display:grid;grid-template-columns:1.15fr .85fr;gap:16px}
        .db-panel{
            background:#fff;border:1px solid #e1ece5;border-radius:24px;padding:18px;
            box-shadow:0 12px 30px rgba(15,23,42,.04)
        }
        .db-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
        .db-panel-title{font-size:1.05rem;font-weight:900;color:#0f172a;margin:0}
        .db-panel-link{font-size:.88rem;color:#167b44;text-decoration:none;font-weight:800}
        .db-canvas{height:280px}
        .db-list{display:flex;flex-direction:column;gap:10px}
        .db-link-card{
            display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
            padding:14px;border:1px solid #edf2ee;border-radius:18px;background:#fbfdfb;text-decoration:none
        }
        .db-link-card:hover{background:#f6fbf8}
        .db-item-title{font-weight:800;color:#0f172a}
        .db-item-sub{font-size:.87rem;color:#64748b;margin-top:3px}
        .db-pill{
            display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;
            background:#eef7f1;color:#167b44;font-size:.8rem;font-weight:800;white-space:nowrap
        }
        .db-bars{display:flex;flex-direction:column;gap:12px}
        .db-bar-top{display:flex;justify-content:space-between;font-size:.92rem;font-weight:700;color:#334155;margin-bottom:5px}
        .db-bar-track{height:10px;background:#eef4ef;border-radius:999px;overflow:hidden}
        .db-bar-fill{height:10px;background:linear-gradient(90deg,#16a34a,#15803d);border-radius:999px}
        .db-table{width:100%;border-collapse:collapse}
        .db-table th,.db-table td{padding:11px 10px;text-align:right;border-bottom:1px solid #edf2ee}
        .db-table th{font-size:.88rem;font-weight:800;color:#475569;background:#f8fbf9}
        .db-table tr:hover td{background:#fbfdfb}
        .db-table-link{text-decoration:none;color:#0f172a;font-weight:700}
        .db-state{display:inline-flex;padding:6px 10px;border-radius:999px;background:#eef6ff;color:#2456d3;font-size:.78rem;font-weight:800}

        @media (max-width: 1200px){
            .db-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
            .db-panels,.db-hero-grid{grid-template-columns:1fr}
        }

        @media (max-width: 700px){
            .db-stats,.db-mini-grid{grid-template-columns:1fr}
            .db-title{font-size:1.25rem}
        }
    </style>

    @php
        $summary = $this->summary ?? [];
        $stateDistribution = $this->stateDistribution ?? [];
        $documentationDistribution = $this->documentationDistribution ?? [];
        $financeMonthly = $this->financeMonthly ?? [];
        $latestProjects = $this->latestProjects ?? [];
        $recentActivity = $this->recentActivity ?? [];
        $topOrganizations = $this->topOrganizations ?? [];

        $maxState = max(1, collect($stateDistribution)->max('value') ?: 1);
        $maxOrg = max(1, collect($topOrganizations)->max('total') ?: 1);
    @endphp

    <div class="db-wrap">
        <section class="db-hero">
            <div class="db-hero-grid">
                <div>
                    <div class="db-kicker">نظرة تشغيلية سريعة</div>
                    <h1 class="db-title">ملخص شامل للمشاريع والحوالات والتوثيق والنشاطات الأخيرة</h1>
                    <div class="db-sub">واجهة تنفيذية مركزة تساعدك على متابعة حالة النظام بالكامل بسرعة ووضوح.</div>

                    <div class="db-actions">
                        <a class="db-btn db-btn-primary" href="/admin/projects">إدارة المشاريع</a>
                        <a class="db-btn db-btn-soft" href="/admin/financial-transactions">الحركات المالية</a>
                        <a class="db-btn db-btn-soft" href="/admin/reports">التقارير</a>
                    </div>
                </div>

                <div class="db-mini-grid">
                    <div class="db-mini">
                        <div class="db-mini-label">إجمالي الوارد</div>
                        <div class="db-mini-value">{{ number_format((float) data_get($summary, 'incoming_total', 0), 2) }}</div>
                    </div>
                    <div class="db-mini">
                        <div class="db-mini-label">إجمالي الصادر</div>
                        <div class="db-mini-value">{{ number_format((float) data_get($summary, 'outgoing_total', 0), 2) }}</div>
                    </div>
                    <div class="db-mini">
                        <div class="db-mini-label">الرصيد الحالي</div>
                        <div class="db-mini-value">{{ number_format((float) data_get($summary, 'balance', 0), 2) }}</div>
                    </div>
                    <div class="db-mini">
                        <div class="db-mini-label">غير موثقة</div>
                        <div class="db-mini-value">{{ number_format((int) data_get($summary, 'undocumented_projects', 0)) }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="db-stats">
            <div class="db-card">
                <div class="db-card-label">إجمالي المشاريع</div>
                <div class="db-card-value">{{ number_format((int) data_get($summary, 'total_projects', 0)) }}</div>
                <div class="db-card-note">كل المشاريع النشطة داخل النظام</div>
            </div>
            <div class="db-card">
                <div class="db-card-label">المشاريع الجارية</div>
                <div class="db-card-value">{{ number_format((int) data_get($summary, 'active_projects', 0)) }}</div>
                <div class="db-card-note">باستثناء المكتملة والمغلقة</div>
            </div>
            <div class="db-card">
                <div class="db-card-label">المشاريع المتأخرة</div>
                <div class="db-card-value">{{ number_format((int) data_get($summary, 'delayed_projects', 0)) }}</div>
                <div class="db-card-note">بحاجة متابعة مباشرة</div>
            </div>
            <div class="db-card">
                <div class="db-card-label">الحركات / المرفقات</div>
                <div class="db-card-value">{{ number_format((int) data_get($summary, 'transactions_count', 0)) }} / {{ number_format((int) data_get($summary, 'attachments_count', 0)) }}</div>
                <div class="db-card-note">إجمالي الحركات والملفات</div>
            </div>
        </section>

        <section class="db-panels">
            <div class="db-panel">
                <div class="db-panel-head">
                    <h3 class="db-panel-title">التحليل المالي الشهري</h3>
                </div>
                <div class="db-canvas">
                    <canvas id="financeMonthlyChart"></canvas>
                </div>
            </div>

            <div class="db-panel">
                <div class="db-panel-head">
                    <h3 class="db-panel-title">حالة التوثيق</h3>
                </div>
                <div class="db-canvas">
                    <canvas id="documentationChart"></canvas>
                </div>
            </div>
        </section>

        <section class="db-panels">
            <div class="db-panel">
                <div class="db-panel-head">
                    <h3 class="db-panel-title">توزيع المشاريع حسب الحالة</h3>
                    <a class="db-panel-link" href="/admin/projects">عرض المشاريع</a>
                </div>

                <div class="db-bars">
                    @foreach ($stateDistribution as $row)
                        @php $percent = $maxState > 0 ? (($row['value'] / $maxState) * 100) : 0; @endphp
                        <div>
                            <div class="db-bar-top">
                                <span>{{ $row['label'] }}</span>
                                <span>{{ $row['value'] }}</span>
                            </div>
                            <div class="db-bar-track">
                                <div class="db-bar-fill" style="width: {{ max($percent, $row['value'] > 0 ? 8 : 0) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="db-panel">
                <div class="db-panel-head">
                    <h3 class="db-panel-title">أكثر الجهات نشاطًا</h3>
                </div>

                <div class="db-bars">
                    @foreach ($topOrganizations as $row)
                        @php $percent = $maxOrg > 0 ? (($row['total'] / $maxOrg) * 100) : 0; @endphp
                        <div>
                            <div class="db-bar-top">
                                <span>{{ $row['name'] }}</span>
                                <span>{{ $row['total'] }}</span>
                            </div>
                            <div class="db-bar-track">
                                <div class="db-bar-fill" style="width: {{ max($percent, $row['total'] > 0 ? 8 : 0) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="db-panels">
            <div class="db-panel">
                <div class="db-panel-head">
                    <h3 class="db-panel-title">أحدث المشاريع</h3>
                    <a class="db-panel-link" href="/admin/projects">عرض الكل</a>
                </div>

                <table class="db-table">
                    <thead>
                        <tr>
                            <th>رقم المشروع</th>
                            <th>اسم المشروع</th>
                            <th>الدولة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestProjects as $row)
                            <tr>
                                <td>
                                    <a class="db-table-link" href="{{ url('/admin/project-workspace?project_id=' . $row['id']) }}">
                                        {{ $row['project_number'] }}
                                    </a>
                                </td>
                                <td>
                                    <a class="db-table-link" href="{{ url('/admin/project-workspace?project_id=' . $row['id']) }}">
                                        {{ $row['title'] }}
                                    </a>
                                </td>
                                <td>{{ $row['country'] }}</td>
                                <td><span class="db-state">{{ $row['state'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4">لا توجد مشاريع</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="db-panel">
                <div class="db-panel-head">
                    <h3 class="db-panel-title">آخر النشاطات</h3>
                    <a class="db-panel-link" href="/admin/activity-log/activity-logs">عرض السجل</a>
                </div>

                <div class="db-list">
                    @forelse ($recentActivity as $row)
                        <a class="db-link-card" href="{{ $row['project_id'] ? url('/admin/project-workspace?project_id=' . $row['project_id']) : '/admin/activity-log/activity-logs' }}">
                            <div>
                                <div class="db-item-title">{{ $row['event'] }}</div>
                                <div class="db-item-sub">{{ $row['description'] }}</div>
                                <div class="db-item-sub">المشروع: {{ $row['project_number'] }} | بواسطة: {{ $row['causer'] }}</div>
                            </div>
                            <span class="db-pill">{{ $row['created_at'] }}</span>
                        </a>
                    @empty
                        <div class="db-link-card">
                            <div class="db-item-title">لا يوجد نشاط حديث</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const finance = @json($financeMonthly);
            const documentation = @json($documentationDistribution);

            const financeCtx = document.getElementById('financeMonthlyChart');
            if (financeCtx) {
                new Chart(financeCtx, {
                    type: 'line',
                    data: {
                        labels: finance.map(item => item.month),
                        datasets: [
                            {
                                label: 'الوارد',
                                data: finance.map(item => item.incoming_total),
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22, 163, 74, .08)',
                                tension: .35,
                                fill: true,
                                borderWidth: 3,
                                pointRadius: 3
                            },
                            {
                                label: 'الصادر',
                                data: finance.map(item => item.outgoing_total),
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, .06)',
                                tension: .35,
                                fill: true,
                                borderWidth: 3,
                                pointRadius: 3
                            }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { usePointStyle: true, font: { family: 'Alexandria', size: 12 } }
                            }
                        },
                        scales: {
                            x: { ticks: { font: { family: 'Alexandria', size: 11 } } },
                            y: { ticks: { font: { family: 'Alexandria', size: 11 } } }
                        }
                    }
                });
            }

            const documentationCtx = document.getElementById('documentationChart');
            if (documentationCtx) {
                new Chart(documentationCtx, {
                    type: 'doughnut',
                    data: {
                        labels: documentation.map(item => item.label),
                        datasets: [{
                            data: documentation.map(item => item.value),
                            backgroundColor: ['#ef4444', '#f59e0b', '#16a34a'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font: { family: 'Alexandria', size: 12 }, usePointStyle: true }
                            }
                        }
                    }
                });
            }
        })();
    </script>
</x-filament-panels::page>
