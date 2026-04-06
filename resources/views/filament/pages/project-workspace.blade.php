<x-filament-panels::page>
@php
    $project = $this->project;
@endphp

<style>
/* ─── Reset & Base ─────────────────────────────────── */
.pv-wrap*{box-sizing:border-box}
.pv-wrap{
    direction:rtl;
    font-family:'Alexandria',system-ui,sans-serif;
    --accent:#0F6E56;
    --accent-light:#E1F5EE;
    --accent-mid:#1D9E75;
    --warn:#854F0B;
    --warn-bg:#FAEEDA;
    --danger:#A32D2D;
    --danger-bg:#FCEBEB;
    --info:#185FA5;
    --info-bg:#E6F1FB;
    --gray:#5F5E5A;
    --gray-bg:#F1EFE8;
    --surface:#fff;
    --bg:#f7f8fa;
    --border:#e5e7eb;
    --text:#111827;
    --muted:#6b7280;
    --radius:14px;
    --radius-sm:8px;
    background:var(--bg);
    padding:24px;
    min-height:100vh;
}

/* ─── Project Header ───────────────────────────────── */
.pv-header{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:24px 28px;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:20px;
    position:relative;
    overflow:hidden;
}
.pv-header::before{
    content:'';
    position:absolute;
    top:0;right:0;
    width:6px;height:100%;
    background:linear-gradient(180deg,var(--accent),var(--accent-mid));
    border-radius:0 var(--radius) var(--radius) 0;
}
.pv-header-icon{
    width:60px;height:60px;
    border-radius:16px;
    background:var(--accent-light);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.pv-header-icon svg{width:30px;height:30px;stroke:var(--accent);fill:none;stroke-width:1.8}
.pv-header-info{flex:1}
.pv-header-num{font-size:15px;font-weight:600;color:var(--accent);letter-spacing:.08em;margin-bottom:4px}
.pv-header-title{font-size:22px;font-weight:600;color:var(--text);margin-bottom:6px;line-height:1.3}
.pv-header-meta{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.pv-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:600}
.pv-pill-green{background:var(--accent-light);color:var(--accent)}
.pv-pill-amber{background:var(--warn-bg);color:var(--warn)}
.pv-pill-gray{background:var(--gray-bg);color:var(--gray)}
.pv-pill-blue{background:var(--info-bg);color:var(--info)}
.pv-pill-red{background:var(--danger-bg);color:var(--danger)}
.pv-pill::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.7}

/* ─── KPI Strip ────────────────────────────────────── */
.pv-kpi{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
    gap:12px;
    margin-bottom:20px;
}
.pv-kpi-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:18px 20px;
    position:relative;
    overflow:hidden;
    transition:box-shadow .2s;
}
.pv-kpi-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.pv-kpi-icon{
    width:38px;height:38px;
    border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:12px;
}
.pv-kpi-icon svg{width:20px;height:20px;stroke-width:1.8;fill:none}
.pv-kpi-label{font-size:11px;color:var(--muted);font-weight:600;margin-bottom:4px}
.pv-kpi-val{font-size:22px;font-weight:600;color:var(--text);line-height:1}
.pv-kpi-sub{font-size:11px;color:var(--muted);margin-top:4px}
.kpi-green .pv-kpi-icon{background:var(--accent-light)}
.kpi-green .pv-kpi-icon svg{stroke:var(--accent)}
.kpi-green .pv-kpi-val{color:var(--accent)}
.kpi-amber .pv-kpi-icon{background:var(--warn-bg)}
.kpi-amber .pv-kpi-icon svg{stroke:var(--warn)}
.kpi-amber .pv-kpi-val{color:var(--warn)}
.kpi-red .pv-kpi-icon{background:var(--danger-bg)}
.kpi-red .pv-kpi-icon svg{stroke:var(--danger)}
.kpi-red .pv-kpi-val{color:var(--danger)}
.kpi-blue .pv-kpi-icon{background:var(--info-bg)}
.kpi-blue .pv-kpi-icon svg{stroke:var(--info)}
.kpi-blue .pv-kpi-val{color:var(--info)}

/* ─── Tabs ─────────────────────────────────────────── */
.pv-tabs-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.pv-tab-nav{
    display:flex;
    border-bottom:1px solid var(--border);
    background:#f9fafb;
    overflow-x:auto;
    scrollbar-width:none;
}
.pv-tab-nav::-webkit-scrollbar{display:none}
.pv-tab-btn{
    display:flex;align-items:center;gap:7px;
    padding:14px 20px;
    font-size:13px;font-weight:600;
    color:var(--muted);
    border:none;background:none;cursor:pointer;
    border-bottom:3px solid transparent;
    white-space:nowrap;
    transition:color .2s,border-color .2s,background .2s;
    font-family:inherit;
}
.pv-tab-btn:hover{color:var(--text);background:rgba(0,0,0,.03)}
.pv-tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);background:var(--surface)}
.pv-tab-btn svg{width:16px;height:16px;stroke-width:2;fill:none;stroke:currentColor}
.pv-tab-badge{
    background:var(--accent-light);color:var(--accent);
    border-radius:99px;
    font-size:10px;font-weight:700;
    padding:1px 7px;
    min-width:18px;text-align:center;
}

/* ─── Tab Panels ───────────────────────────────────── */
.pv-tab-panels{padding:24px}
.pv-panel{display:none;animation:fadeIn .2s ease}
.pv-panel.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

/* ─── Basic Info Panel ─────────────────────────────── */
.pv-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.pv-section-head{
    font-size:11px;font-weight:700;
    color:var(--muted);letter-spacing:.08em;
    text-transform:uppercase;
    margin-bottom:14px;
    padding-bottom:8px;
    border-bottom:1px solid var(--border);
}
.pv-kv-list{}
.pv-kv-item{
    display:flex;justify-content:space-between;align-items:baseline;
    padding:10px 0;
    border-bottom:1px solid #f3f4f6;
    gap:12px;
}
.pv-kv-item:last-child{border-bottom:none}
.pv-kv-k{font-size:12px;color:var(--muted);flex-shrink:0}
.pv-kv-v{font-size:13px;font-weight:600;color:var(--text);text-align:left}

/* ─── Timeline ─────────────────────────────────────── */
.pv-timeline{position:relative;padding-right:20px}
.pv-timeline::before{
    content:'';
    position:absolute;right:7px;top:14px;bottom:14px;
    width:2px;background:var(--border);
    border-radius:2px;
}
.pv-tl-item{position:relative;padding-right:28px;padding-bottom:20px}
.pv-tl-item:last-child{padding-bottom:0}
.pv-tl-dot{
    position:absolute;right:0;top:3px;
    width:16px;height:16px;
    border-radius:50%;
    border:2px solid var(--surface);
    box-shadow:0 0 0 2px var(--border);
    background:var(--muted);
}
.pv-tl-dot.green{background:var(--accent);box-shadow:0 0 0 2px var(--accent-light)}
.pv-tl-dot.amber{background:#EF9F27;box-shadow:0 0 0 2px var(--warn-bg)}
.pv-tl-dot.gray{background:#B4B2A9;box-shadow:0 0 0 2px var(--gray-bg)}
.pv-tl-time{font-size:11px;color:var(--muted);margin-bottom:2px}
.pv-tl-from{font-size:12px;color:var(--muted)}
.pv-tl-arrow{font-size:12px;color:var(--muted);margin:0 4px}
.pv-tl-to{font-size:12px;font-weight:700;color:var(--text)}
.pv-tl-by{font-size:11px;color:var(--muted);margin-top:3px}

/* ─── Finance Infographic ──────────────────────────── */
.pv-fin-infographic{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    margin-bottom:24px;
}
.pv-donut-wrap{
    background:#f9fafb;
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:16px;
}
.pv-donut-svg{width:150px;height:150px}
.pv-donut-legend{width:100%;display:flex;flex-direction:column;gap:8px}
.pv-legend-item{display:flex;align-items:center;gap:8px;font-size:12px}
.pv-legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.pv-legend-label{color:var(--muted);flex:1}
.pv-legend-val{font-weight:700;color:var(--text)}
.pv-bar-wrap{
    background:#f9fafb;
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:16px;
    justify-content:center;
}
.pv-bar-item-label{display:flex;justify-content:space-between;margin-bottom:6px}
.pv-bar-label-text{font-size:12px;color:var(--muted)}
.pv-bar-label-val{font-size:12px;font-weight:700;color:var(--text)}
.pv-bar-track{height:10px;background:var(--border);border-radius:99px;overflow:hidden}
.pv-bar-fill{height:100%;border-radius:99px;transition:width 1s ease}
.fill-green{background:linear-gradient(90deg,var(--accent-mid),var(--accent))}
.fill-amber{background:linear-gradient(90deg,#EF9F27,#BA7517)}
.fill-blue{background:linear-gradient(90deg,#378ADD,#185FA5)}

/* ─── Table ────────────────────────────────────────── */
.pv-table-wrap{border-radius:var(--radius-sm);border:1px solid var(--border);overflow:hidden}
.pv-table{width:100%;border-collapse:collapse}
.pv-table th{
    background:#f9fafb;
    font-size:11px;font-weight:700;
    color:var(--muted);letter-spacing:.05em;
    padding:10px 14px;
    border-bottom:1px solid var(--border);
    text-align:right;
    white-space:nowrap;
}
.pv-table td{
    padding:11px 14px;
    font-size:13px;
    color:var(--text);
    border-bottom:1px solid #f3f4f6;
    vertical-align:middle;
}
.pv-table tr:last-child td{border-bottom:none}
.pv-table tbody tr:hover td{background:#f9fafb}
.pv-muted{color:var(--muted)}
.pv-link{color:var(--accent);font-weight:600;text-decoration:none}
.pv-link:hover{text-decoration:underline}

/* ─── Status Panel ─────────────────────────────────── */
.pv-status-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.pv-status-card{
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:18px 20px;
    background:var(--surface);
}
.pv-status-icon{
    width:44px;height:44px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:12px;
}
.pv-status-icon svg{width:22px;height:22px;fill:none;stroke-width:2;stroke:currentColor}
.pv-status-lbl{font-size:11px;color:var(--muted);font-weight:600;margin-bottom:6px}
.pv-status-val{font-size:15px;font-weight:600;color:var(--text)}
.pv-status-note{font-size:11px;color:var(--muted);margin-top:6px;line-height:1.5}

/* ─── Attachments ──────────────────────────────────── */
.pv-attachment-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:20px}
.pv-attach-card{
    background:#f9fafb;
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    padding:14px;
    display:flex;align-items:center;gap:12px;
}
.pv-attach-icon{
    width:36px;height:36px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.pv-attach-icon svg{width:18px;height:18px;fill:none;stroke-width:2;stroke:currentColor}
.pv-attach-name{font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px}
.pv-attach-meta{font-size:11px;color:var(--muted)}

/* ─── Activity Log ─────────────────────────────────── */
.pv-activity-item{
    display:flex;gap:14px;
    padding:12px 0;
    border-bottom:1px solid #f3f4f6;
}
.pv-activity-item:last-child{border-bottom:none}
.pv-act-avatar{
    width:34px;height:34px;border-radius:50%;
    background:var(--accent-light);
    color:var(--accent);
    display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:700;
    flex-shrink:0;
}
.pv-act-body{flex:1}
.pv-act-event{font-size:13px;font-weight:600;color:var(--text)}
.pv-act-desc{font-size:12px;color:var(--muted);margin-top:2px}
.pv-act-time{font-size:11px;color:var(--muted);margin-top:4px}

/* ─── Progress Ring utility (SVG) ─────────────────── */
.pv-prog-ring{transform:rotate(-90deg)}
</style>

<div class="pv-wrap">

{{-- ── HEADER ─────────────────────────────────────── --}}
<div class="pv-header">
    <div class="pv-header-icon">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
    </div>
    <div class="pv-header-info">
        <div class="pv-header-num">مشروع رقم: {{ $project->project_number ?? '—' }}</div>
        <div class="pv-header-title">{{ $this->projectTitle() }}</div>
        <div class="pv-header-meta">
            <span class="pv-pill pv-pill-amber">{{ $this->projectState() }}</span>
            <span class="pv-pill pv-pill-gray">{{ $this->documentationStatus() }}</span>
            <span class="pv-pill pv-pill-green">{{ $this->financialStatus() }}</span>
            @if($project->country)
                <span class="pv-pill pv-pill-blue">{{ $project->country->name_ar }}</span>
            @endif
        </div>
    </div>
</div>

{{-- ── KPI STRIP ───────────────────────────────────── --}}
@php
    $incoming = $this->financeSummary['incoming'];
    $outgoing = $this->financeSummary['outgoing'];
    $balance  = $this->financeSummary['balance'];
    $approved = (float)($project->approved_amount ?? 0);
    $burnPct  = $approved > 0 ? min(100, round($outgoing / $approved * 100)) : 0;
@endphp
<div class="pv-kpi">
    <div class="pv-kpi-card kpi-green">
        <div class="pv-kpi-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="pv-kpi-label">إجمالي الوارد</div>
        <div class="pv-kpi-val">{{ number_format($incoming, 2) }}</div>
        <div class="pv-kpi-sub">المبالغ المستلمة</div>
    </div>
    <div class="pv-kpi-card kpi-amber">
        <div class="pv-kpi-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="pv-kpi-label">إجمالي الصادر</div>
        <div class="pv-kpi-val">{{ number_format($outgoing, 2) }}</div>
        <div class="pv-kpi-sub">المبالغ المصروفة</div>
    </div>
    <div class="pv-kpi-card kpi-blue">
        <div class="pv-kpi-icon">
            <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        </div>
        <div class="pv-kpi-label">الرصيد المتاح</div>
        <div class="pv-kpi-val">{{ number_format($balance, 2) }}</div>
        <div class="pv-kpi-sub">وارد ناقص صادر</div>
    </div>
    <div class="pv-kpi-card kpi-red">
        <div class="pv-kpi-icon">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="pv-kpi-label">نسبة الصرف</div>
        <div class="pv-kpi-val">{{ $burnPct }}%</div>
        <div class="pv-kpi-sub">من الميزانية المعتمدة</div>
    </div>
</div>

{{-- ── TABS ────────────────────────────────────────── --}}
<div class="pv-tabs-wrap">
    <nav class="pv-tab-nav" role="tablist">
        <button class="pv-tab-btn active" onclick="pvTab(this,'tab-basic')" role="tab">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            البيانات الأساسية
        </button>
        <button class="pv-tab-btn" onclick="pvTab(this,'tab-finance')" role="tab">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            الملخص المالي
            <span class="pv-tab-badge">{{ $project->financialTransactions->count() }}</span>
        </button>
      <button class="pv-tab-btn" onclick="pvTab(this,'tab-docs')" role="tab">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
    المرفقات
<span class="pv-tab-badge">{{ collect($this->projectAttachments ?? [])->count() }}</span>
</button>
        <button class="pv-tab-btn" onclick="pvTab(this,'tab-status')" role="tab">
            <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            الحالة والروابط
        </button>
        <button class="pv-tab-btn" onclick="pvTab(this,'tab-history')" role="tab">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
            سجل الحالات
            <span class="pv-tab-badge">{{ $project->stateHistories->count() }}</span>
        </button>
        <button class="pv-tab-btn" onclick="pvTab(this,'tab-activity')" role="tab">
            <svg viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            سجل العمليات
            <span class="pv-tab-badge">{{ $project->activityLogs->count() }}</span>
        </button>
    </nav>

    <div class="pv-tab-panels">

        {{-- ──────────────────────────────────────────
             TAB 1: البيانات الأساسية
        ─────────────────────────────────────────── --}}
        <div id="tab-basic" class="pv-panel active">
            <div class="pv-grid-2">
                <div>
                    <div class="pv-section-head">معلومات المشروع</div>
                    <div class="pv-kv-list">
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">رقم المشروع</span>
                            <span class="pv-kv-v">{{ $project->project_number ?? '—' }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">اسم المشروع</span>
                            <span class="pv-kv-v">{{ $this->projectTitle() }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">الدولة</span>
                            <span class="pv-kv-v">{{ $project->country->name_ar ?? '—' }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">الجهة المنفذة</span>
                            <span class="pv-kv-v">{{ $project->organization->name ?? '—' }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">الوصف</span>
                            <span class="pv-kv-v" style="max-width:260px;white-space:normal;text-align:right">{{ $project->description ?: '—' }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="pv-section-head">الجدول الزمني والميزانية</div>
                    <div class="pv-kv-list">
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">المبلغ المعتمد</span>
                            <span class="pv-kv-v" style="color:var(--accent)">{{ number_format($approved, 2) }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">تاريخ البداية</span>
                            <span class="pv-kv-v">{{ optional($project->start_date)->format('Y-m-d') ?? '—' }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">الانتهاء المتوقع</span>
                            <span class="pv-kv-v">{{ optional($project->expected_end_date)->format('Y-m-d') ?? '—' }}</span>
                        </div>
                    </div>

                    {{-- Burn rate mini infographic --}}
                    <div style="margin-top:20px;background:#f9fafb;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px">
                        <div style="font-size:11px;color:var(--muted);font-weight:700;margin-bottom:12px">نسبة استهلاك الميزانية</div>
                        <div style="display:flex;align-items:center;gap:16px">
                            <svg width="80" height="80" viewBox="0 0 80 80">
                                @php
                                    $r = 32; $cx = 40; $cy = 40;
                                    $circ = 2 * M_PI * $r;
                                    $filled = $circ * ($burnPct / 100);
                                    $empty  = $circ - $filled;
                                @endphp
                                <circle cx="{{$cx}}" cy="{{$cy}}" r="{{$r}}" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                                <circle cx="{{$cx}}" cy="{{$cy}}" r="{{$r}}" fill="none"
                                    stroke="{{ $burnPct > 80 ? '#A32D2D' : ($burnPct > 50 ? '#854F0B' : '#0F6E56') }}"
                                    stroke-width="10"
                                    stroke-dasharray="{{ $filled }} {{ $empty }}"
                                    stroke-linecap="round"
                                    transform="rotate(-90 {{$cx}} {{$cy}})"/>
                                <text x="{{$cx}}" y="{{$cy}}" text-anchor="middle" dy="5"
                                    font-size="14" font-weight="800"
                                    fill="{{ $burnPct > 80 ? '#A32D2D' : ($burnPct > 50 ? '#854F0B' : '#0F6E56') }}"
                                    font-family="Cairo,sans-serif">
                                    {{ $burnPct }}%
                                </text>
                            </svg>
                            <div style="flex:1">
                                <div style="font-size:11px;color:var(--muted);margin-bottom:6px">المصروف: <strong style="color:var(--text)">{{ number_format($outgoing,2) }}</strong></div>
                                <div style="font-size:11px;color:var(--muted)">المعتمد: <strong style="color:var(--text)">{{ number_format($approved,2) }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ──────────────────────────────────────────
             TAB 2: الملخص المالي
        ─────────────────────────────────────────── --}}
        <div id="tab-finance" class="pv-panel">
            {{-- Infographic --}}
            <div class="pv-fin-infographic">
                <div class="pv-donut-wrap">
                    @php
                        $total = $incoming + $outgoing;
                        $inPct  = $total > 0 ? ($incoming / $total * 100) : 0;
                        $outPct = $total > 0 ? ($outgoing / $total * 100) : 0;
                        $r2 = 50; $cx2 = 60; $cy2 = 60;
                        $c2 = 2 * M_PI * $r2;
                        $inArc  = $c2 * ($inPct / 100);
                        $outArc = $c2 * ($outPct / 100);
                    @endphp
                    <svg class="pv-donut-svg" viewBox="0 0 120 120">
                        <circle cx="{{$cx2}}" cy="{{$cy2}}" r="{{$r2}}" fill="none" stroke="#E1F5EE" stroke-width="18"/>
                        <circle cx="{{$cx2}}" cy="{{$cy2}}" r="{{$r2}}" fill="none"
                            stroke="#0F6E56"
                            stroke-width="18"
                            stroke-dasharray="{{ $inArc }} {{ $c2 - $inArc }}"
                            stroke-linecap="butt"
                            transform="rotate(-90 {{$cx2}} {{$cy2}})"/>
                        <circle cx="{{$cx2}}" cy="{{$cy2}}" r="{{$r2}}" fill="none"
                            stroke="#EF9F27"
                            stroke-width="18"
                            stroke-dasharray="{{ $outArc }} {{ $c2 - $outArc }}"
                            stroke-linecap="butt"
                            stroke-dashoffset="{{ -$inArc }}"
                            transform="rotate(-90 {{$cx2}} {{$cy2}})"/>
                        <text x="{{$cx2}}" y="{{$cy2}}" text-anchor="middle" dy="4"
                            font-size="11" font-weight="800" fill="#111827" font-family="Cairo,sans-serif">
                            الرصيد
                        </text>
                        <text x="{{$cx2}}" y="{{$cy2+16}}" text-anchor="middle"
                            font-size="9" fill="#6b7280" font-family="Cairo,sans-serif">
                            {{ number_format($balance,0) }}
                        </text>
                    </svg>
                    <div class="pv-donut-legend">
                        <div class="pv-legend-item">
                            <div class="pv-legend-dot" style="background:#0F6E56"></div>
                            <span class="pv-legend-label">وارد ({{ round($inPct) }}%)</span>
                            <span class="pv-legend-val">{{ number_format($incoming,2) }}</span>
                        </div>
                        <div class="pv-legend-item">
                            <div class="pv-legend-dot" style="background:#EF9F27"></div>
                            <span class="pv-legend-label">صادر ({{ round($outPct) }}%)</span>
                            <span class="pv-legend-val">{{ number_format($outgoing,2) }}</span>
                        </div>
                        <div class="pv-legend-item" style="padding-top:8px;border-top:1px solid var(--border)">
                            <div class="pv-legend-dot" style="background:#185FA5"></div>
                            <span class="pv-legend-label">الرصيد</span>
                            <span class="pv-legend-val" style="color:var(--info)">{{ number_format($balance,2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="pv-bar-wrap">
                    <div style="font-size:11px;font-weight:700;color:var(--muted);margin-bottom:4px">مقارنة المبالغ</div>
                    <div>
                        <div class="pv-bar-item-label">
                            <span class="pv-bar-label-text">الوارد</span>
                            <span class="pv-bar-label-val">{{ number_format($incoming,2) }}</span>
                        </div>
                        <div class="pv-bar-track">
                            <div class="pv-bar-fill fill-green" style="width:{{ $total>0 ? round($inPct) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="pv-bar-item-label">
                            <span class="pv-bar-label-text">الصادر</span>
                            <span class="pv-bar-label-val">{{ number_format($outgoing,2) }}</span>
                        </div>
                        <div class="pv-bar-track">
                            <div class="pv-bar-fill fill-amber" style="width:{{ $total>0 ? round($outPct) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="pv-bar-item-label">
                            <span class="pv-bar-label-text">نسبة الصرف من المعتمد</span>
                            <span class="pv-bar-label-val">{{ $burnPct }}%</span>
                        </div>
                        <div class="pv-bar-track">
                            <div class="pv-bar-fill fill-blue" style="width:{{ $burnPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions Table --}}
            <div style="font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.06em;margin-bottom:10px">آخر الحركات المالية</div>
            <div class="pv-table-wrap">
                <table class="pv-table">
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>المرسل</th>
                            <th>المستقبل</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->financialTransactions->sortByDesc('transaction_date')->take(10) as $row)
                            <tr>
                                <td>
                                    @if($row->transaction_type === 'incoming')
                                        <span class="pv-pill pv-pill-green" style="font-size:11px">واردة</span>
                                    @else
                                        <span class="pv-pill pv-pill-amber" style="font-size:11px">صادرة</span>
                                    @endif
                                </td>
                                <td style="font-weight:700">{{ number_format((float)$row->amount, 2) }}</td>
                                <td class="pv-muted">{{ $row->sender_name ?: '—' }}</td>
                                <td class="pv-muted">{{ $row->receiver_name ?: '—' }}</td>
                                <td class="pv-muted">{{ optional($row->transaction_date)->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">لا توجد حركات مالية</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ──────────────────────────────────────────
             TAB 3: المرفقات
        ─────────────────────────────────────────── --}}
        <div id="tab-docs" class="pv-panel">
            @php
                // التعديل الرئيسي تم هنا: تغليف المرفقات في Collection لحل مشكلة filter و count
                $attachments = collect($this->projectAttachments ?? []);
                
                $pdfCount = $attachments->filter(fn($a) => str_ends_with(strtolower($a['original_name'] ?? ''),'.pdf'))->count();
                $imgCount = $attachments->filter(fn($a) => preg_match('/\.(jpg|jpeg|png|gif|webp)$/i',$a['original_name'] ?? ''))->count();
                $otherCount = $attachments->count() - $pdfCount - $imgCount;
            @endphp

            {{-- Attachment summary --}}
            <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
                <div style="background:#f9fafb;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 20px;display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:var(--danger-bg);border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <svg width="18" height="18" fill="none" stroke="#A32D2D" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--muted)">PDF</div>
                        <div style="font-size:18px;font-weight:600;color:var(--text)">{{ $pdfCount }}</div>
                    </div>
                </div>
                <div style="background:#f9fafb;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 20px;display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:var(--info-bg);border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <svg width="18" height="18" fill="none" stroke="#185FA5" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--muted)">صور</div>
                        <div style="font-size:18px;font-weight:600;color:var(--text)">{{ $imgCount }}</div>
                    </div>
                </div>
                <div style="background:#f9fafb;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 20px;display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:var(--gray-bg);border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <svg width="18" height="18" fill="none" stroke="#5F5E5A" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--muted)">أخرى</div>
                        <div style="font-size:18px;font-weight:600;color:var(--text)">{{ $otherCount }}</div>
                    </div>
                </div>
            </div>

            <div class="pv-table-wrap">
                <table class="pv-table">
                    <thead>
                        <tr>
                            <th>اسم الملف</th>
                            <th>التصنيف</th>
                            <th>مرتبط بحوالة</th>
                            <th>رفع بواسطة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attachments as $row)
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div style="width:28px;height:28px;border-radius:6px;background:var(--info-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <svg width="14" height="14" fill="none" stroke="#185FA5" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                                        </div>
                                        <a href="{{ url('/admin/attachments/' . ($row['id'] ?? '') . '/edit') }}" target="_blank" class="pv-link" style="font-size:12px;font-weight:600;color:var(--info);">
    {{ $row['original_name'] ?? '—' }}
</a>
                                    </div>
                                </td>
                                <td>
                                    @if(!empty($row['category']))
                                        <span class="pv-pill pv-pill-gray" style="font-size:10px">{{ $row['category'] }}</span>
                                    @else
                                        <span class="pv-muted">—</span>
                                    @endif
                                </td>
                                <td class="pv-muted">
                                    @if(!empty($row['transaction_type']))
                                        {{ $row['transaction_type'] === 'incoming' ? 'واردة' : 'صادرة' }}
                                        @if(!empty($row['transaction_ref'])) / {{ $row['transaction_ref'] }} @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="pv-muted">{{ $row['uploaded_by'] ?? '—' }}</td>
                                <td class="pv-muted">{{ $row['created_at'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">لا توجد مرفقات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ──────────────────────────────────────────
             TAB 4: الحالة والروابط
        ─────────────────────────────────────────── --}}
        <div id="tab-status" class="pv-panel">
            <div class="pv-status-cards">
                <div class="pv-status-card">
                    <div class="pv-status-icon" style="background:var(--warn-bg);color:#854F0B">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    </div>
                    <div class="pv-status-lbl">حالة المشروع</div>
                    <div class="pv-status-val"><span class="pv-pill pv-pill-amber">{{ $this->projectState() }}</span></div>
                </div>
                <div class="pv-status-card">
                    <div class="pv-status-icon" style="background:var(--gray-bg);color:#5F5E5A">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    </div>
                    <div class="pv-status-lbl">حالة التوثيق</div>
                    <div class="pv-status-val"><span class="pv-pill pv-pill-gray">{{ $this->documentationStatus() }}</span></div>
                </div>
                <div class="pv-status-card">
                    <div class="pv-status-icon" style="background:var(--accent-light);color:var(--accent)">
                        <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                    <div class="pv-status-lbl">الحالة المالية</div>
                    <div class="pv-status-val"><span class="pv-pill pv-pill-green">{{ $this->financialStatus() }}</span></div>
                </div>
            </div>

            <div class="pv-grid-2" style="gap:20px">
                <div>
                    <div class="pv-section-head">الروابط الخارجية</div>
                    <div class="pv-kv-list">
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">ألبوم الصور</span>
                            <span class="pv-kv-v">
                                @if(!empty($project->photo_album_url))
                                    <a class="pv-link" href="{{ $project->photo_album_url }}" target="_blank">فتح الرابط ↗</a>
                                @else
                                    <span class="pv-muted">—</span>
                                @endif
                            </span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">ألبوم الفيديو</span>
                            <span class="pv-kv-v">
                                @if(!empty($project->video_album_url))
                                    <a class="pv-link" href="{{ $project->video_album_url }}" target="_blank">فتح الرابط ↗</a>
                                @else
                                    <span class="pv-muted">—</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="pv-section-head">الملاحظات</div>
                    <div class="pv-kv-list">
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">ملاحظات الجاهزية</span>
                            <span class="pv-kv-v" style="white-space:normal;text-align:right">{{ $project->readiness_notes ?: '—' }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">ملاحظات التنفيذ</span>
                            <span class="pv-kv-v" style="white-space:normal;text-align:right">{{ $project->execution_notes ?: '—' }}</span>
                        </div>
                        <div class="pv-kv-item">
                            <span class="pv-kv-k">ملاحظات التوثيق</span>
                            <span class="pv-kv-v" style="white-space:normal;text-align:right">{{ $project->documentation_notes ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ──────────────────────────────────────────
             TAB 5: سجل انتقالات الحالة
        ─────────────────────────────────────────── --}}
        <div id="tab-history" class="pv-panel">
            @php
                $histories = $project->stateHistories->sortByDesc('created_at')->take(20);
            @endphp
            @if($histories->isEmpty())
                <div style="text-align:center;padding:40px;color:var(--muted)">لا يوجد سجل حالات</div>
            @else
                <div class="pv-timeline">
                    @foreach($histories as $row)
                        @php
                            $dotClass = match(true) {
                                str_contains(strtolower($row->to_state ?? ''), 'complet') => 'green',
                                str_contains(strtolower($row->to_state ?? ''), 'draft')   => 'gray',
                                default => 'amber',
                            };
                        @endphp
                        <div class="pv-tl-item">
                            <div class="pv-tl-dot {{ $dotClass }}"></div>
                            <div class="pv-tl-time">{{ optional($row->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
                            <div>
                              <span class="pv-tl-from">
    {{ $row->from_state ? __('project.states.' . $row->from_state) : 'البداية' }}
</span>

<span class="pv-tl-arrow">←</span>

<span class="pv-tl-to">
    {{ $row->to_state ? __('project.states.' . $row->to_state) : '—' }}
</span>
                            </div>
                            <div class="pv-tl-by">بواسطة: {{ $row->user->name ?? 'النظام' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ──────────────────────────────────────────
             TAB 6: سجل العمليات
        ─────────────────────────────────────────── --}}
        <div id="tab-activity" class="pv-panel">
            @forelse ($project->activityLogs->sortByDesc('created_at')->take(20) as $row)
                @php
                    $name = $row->causer->name ?? 'النظام';
                    $initials = mb_substr($name, 0, 1, 'UTF-8');
                    $eventColors = [
                        'created' => ['bg'=>'var(--accent-light)','color'=>'var(--accent)','pill'=>'pv-pill-green'],
                        'updated' => ['bg'=>'var(--info-bg)','color'=>'var(--info)','pill'=>'pv-pill-blue'],
                        'deleted' => ['bg'=>'var(--danger-bg)','color'=>'var(--danger)','pill'=>'pv-pill-red'],
                    ];
                    $ec = $eventColors[$row->event] ?? ['bg'=>'var(--gray-bg)','color'=>'var(--gray)','pill'=>'pv-pill-gray'];
                @endphp
                <div class="pv-activity-item">
                    <div class="pv-act-avatar" style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }}">
                        {{ $initials }}
                    </div>
                    <div class="pv-act-body">
                        <div class="pv-act-event">
                            <span class="pv-pill {{ $ec['pill'] }}" style="font-size:10px;margin-left:6px">{{ $row->event }}</span>
                            {{ $row->description }}
                        </div>
                        <div class="pv-act-time">{{ $row->causer->name ?? 'النظام' }} · {{ optional($row->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                </div>
            @empty
                <div style="text-align:center;padding:40px;color:var(--muted)">لا يوجد سجل عمليات</div>
            @endforelse
        </div>

    </div>{{-- /tab-panels --}}
</div>{{-- /tabs-wrap --}}

</div>{{-- /pv-wrap --}}

<script>
function pvTab(btn, panelId) {
    document.querySelectorAll('.pv-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pv-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
}
</script>
</x-filament-panels::page>