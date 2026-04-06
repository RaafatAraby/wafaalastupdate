<x-filament-panels::page>
    <style>
        .ap-box{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;direction:rtl}
        .ap-title{font-size:22px;font-weight:900;margin-bottom:14px}
        .ap-table{width:100%;border-collapse:collapse}
        .ap-table th,.ap-table td{padding:10px;border-bottom:1px solid #f1f5f9;text-align:right}
        .ap-table th{background:#f8fafc;font-weight:700}
        .ap-badge{display:inline-block;background:#e5e7eb;color:#111827;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}
    </style>

    <div class="ap-box">
        <div class="ap-title">أرشيف المشاريع</div>

        <table class="ap-table">
            <thead>
                <tr>
                    <th>رقم المشروع</th>
                    <th>اسم المشروع</th>
                    <th>الحالة</th>
                    <th>تاريخ الأرشفة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->projects as $row)
                    <tr>
                        <td>{{ $row['project_number'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td><span class="ap-badge">{{ $row['state'] }}</span></td>
                        <td>{{ $row['archived_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">لا توجد مشاريع مؤرشفة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
