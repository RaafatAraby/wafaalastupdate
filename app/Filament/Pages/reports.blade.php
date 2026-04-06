<x-filament-panels::page>
    @php
        $summary = is_array($this->summary ?? null) ? $this->summary : [];
        $projectsByState = is_array($this->projectsByState ?? null) ? $this->projectsByState : [];
        $monthlyFinance = is_array($this->monthlyFinance ?? null) ? $this->monthlyFinance : [];
        $lateProjects = is_array($this->lateProjects ?? null) ? $this->lateProjects : [];

        $totalProjects = (int) data_get($summary, 'total_projects', 0);
        $delayedProjects = (int) data_get($summary, 'delayed_projects', 0);
        $completedProjects = (int) data_get($summary, 'completed_projects', 0);
        $incomingTotal = (float) data_get($summary, 'incoming_total', 0);
        $outgoingTotal = (float) data_get($summary, 'outgoing_total', 0);
        $balance = (float) data_get($summary, 'balance', ($incomingTotal - $outgoingTotal));

        $maxStateCount = max(1, collect($projectsByState)->max(fn ($row) => (int) data_get($row, 'total', 0)) ?? 1);
    @endphp

    <div dir="rtl" class="space-y-6">
        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-l from-emerald-50 to-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-emerald-900">لوحة التقارير الذكية</h2>
                    <p class="mt-1 text-sm text-gray-600">ملخص فوري لحالة المشاريع والحركة المالية</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white px-4 py-3 text-right">
                    <div class="text-xs text-gray-500">الرصيد الحالي</div>
                    <div class="text-xl font-extrabold {{ $balance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ number_format($balance, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-gray-500">إجمالي المشاريع</div>
                <div class="mt-2 text-3xl font-black text-gray-900">{{ number_format($totalProjects) }}</div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="text-sm text-amber-700">المشاريع المتأخرة</div>
                <div class="mt-2 text-3xl font-black text-amber-800">{{ number_format($delayedProjects) }}</div>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="text-sm text-emerald-700">المكتملة / المغلقة</div>
                <div class="mt-2 text-3xl font-black text-emerald-800">{{ number_format($completedProjects) }}</div>
            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <div class="text-sm text-blue-700">نسبة الإنجاز</div>
                <div class="mt-2 text-3xl font-black text-blue-800">
                    {{ $totalProjects > 0 ? number_format(($completedProjects / $totalProjects) * 100, 1) : '0.0' }}%
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-lg font-extrabold text-gray-900">توزيع المشاريع حسب الحالة</h3>
                <div class="space-y-3">
                    @forelse ($projectsByState as $row)
                        @php
                            $state = (string) data_get($row, 'state', '-');
                            $count = (int) data_get($row, 'total', 0);
                            $percent = ($count / $maxStateCount) * 100;
                        @endphp

                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $state }}</span>
                                <span class="font-bold text-emerald-700">{{ number_format($count) }}</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-100">
                                <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ max(4, $percent) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                            لا توجد بيانات حالات حتى الآن
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-lg font-extrabold text-gray-900">آخر 6 أشهر مالية</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="rounded-r-lg px-3 py-2 font-semibold text-gray-700">الشهر</th>
                                <th class="px-3 py-2 font-semibold text-emerald-700">وارد</th>
                                <th class="px-3 py-2 font-semibold text-rose-700">صادر</th>
                                <th class="rounded-l-lg px-3 py-2 font-semibold text-gray-700">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($monthlyFinance as $row)
                                @php
                                    $month = (string) data_get($row, 'month', '-');
                                    $incoming = (float) data_get($row, 'incoming_total', 0);
                                    $outgoing = (float) data_get($row, 'outgoing_total', 0);
                                    $monthBalance = (float) data_get($row, 'balance', ($incoming - $outgoing));
                                @endphp
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-2">{{ $month }}</td>
                                    <td class="px-3 py-2 font-semibold text-emerald-700">{{ number_format($incoming, 2) }}</td>
                                    <td class="px-3 py-2 font-semibold text-rose-700">{{ number_format($outgoing, 2) }}</td>
                                    <td class="px-3 py-2 font-bold {{ $monthBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($monthBalance, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">لا توجد حركة مالية</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-extrabold text-gray-900">المشاريع المتأخرة</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-right text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="rounded-r-lg px-3 py-2 font-semibold text-gray-700">رقم المشروع</th>
                            <th class="px-3 py-2 font-semibold text-gray-700">اسم المشروع</th>
                            <th class="px-3 py-2 font-semibold text-gray-700">الحالة</th>
                            <th class="rounded-l-lg px-3 py-2 font-semibold text-gray-700">تاريخ الانتهاء المتوقع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lateProjects as $row)
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-2 font-semibold text-gray-900">{{ data_get($row, 'project_number', '-') }}</td>
                                <td class="px-3 py-2">{{ data_get($row, 'title', '-') }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                                        {{ data_get($row, 'state', '-') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ data_get($row, 'expected_end_date', '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-5 text-center text-gray-500">لا توجد مشاريع متأخرة حاليًا</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
