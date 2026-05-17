<x-filament-panels::page>
    <div
        wire:poll.3s="refreshStatus"
        class="space-y-6"
        dir="rtl"
    >
        @php
            $stateLabels = [
                'open' => 'متصل',
                'connecting' => 'جارٍ الاتصال…',
                'qr' => 'بانتظار مسح رمز QR',
                'close' => 'غير متصل',
                'logged_out' => 'مسجَّل خروج — يلزم إعادة الربط',
                'unknown' => 'غير معروف',
            ];
            $label = $stateLabels[$status['state'] ?? 'unknown'] ?? $status['state'];
            $isConnected = ! empty($status['connected']);
        @endphp

        {{-- Status banner --}}
        <div class="rounded-xl border p-4 shadow-sm
            {{ $isConnected ? 'bg-green-50 border-green-200 dark:bg-green-950/30 dark:border-green-900' : 'bg-amber-50 border-amber-200 dark:bg-amber-950/30 dark:border-amber-900' }}">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full
                        {{ $isConnected ? 'bg-green-500' : 'bg-amber-500' }} animate-pulse"></div>
                    <div>
                        <div class="text-base font-semibold">
                            {{ $label }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300 mt-1 space-x-2 space-x-reverse">
                            @if (! empty($status['phone']))
                                <span>الرقم المرتبط: <strong dir="ltr">{{ $status['phone'] }}</strong></span>
                            @endif
                            @if (! empty($status['lastConnectedAt']))
                                <span>· آخر اتصال: {{ $status['lastConnectedAt'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="refreshStatus"
                        icon="heroicon-o-arrow-path"
                        color="gray"
                        size="sm">
                        تحديث
                    </x-filament::button>

                    @if ($isConnected)
                        <x-filament::button
                            wire:click="disconnect"
                            wire:confirm="سيتم فصل الجلسة الحالية ويلزم مسح QR جديد. متابعة؟"
                            icon="heroicon-o-power"
                            color="danger"
                            size="sm">
                            فصل الاتصال
                        </x-filament::button>
                    @endif
                </div>
            </div>

            @if (! empty($status['error']))
                <div class="mt-3 text-sm text-red-700 dark:text-red-300">
                    {{ $status['error'] }}
                </div>
            @endif
        </div>

        {{-- QR or connected card --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-xl border bg-white dark:bg-gray-900 p-6 shadow-sm">
                <h3 class="text-lg font-semibold mb-4">ربط الجهاز</h3>

                @if (empty($status['configured']))
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        الـ Bridge غير مهيأ بعد. أضف هذه القيم إلى ملف <code>.env</code> ثم نفّذ
                        <code>php artisan config:cache</code>:
                        <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-800 rounded p-3 overflow-x-auto"
dir="ltr">WHATSAPP_BRIDGE_URL=https://your-bridge.example.com
WHATSAPP_BRIDGE_KEY=long-random-secret</pre>
                    </div>
                @elseif ($isConnected)
                    <div class="flex flex-col items-center justify-center text-center py-8">
                        <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="font-semibold">الجهاز مرتبط</div>
                        <div class="text-xs text-gray-500 mt-1">الإشعارات ستُرسل للمستخدمين الذين عُيِّن لهم رقم واتساب.</div>
                    </div>
                @elseif (! empty($status['qr']))
                    <div class="flex flex-col items-center">
                        <img
                            src="{{ $status['qr'] }}"
                            alt="WhatsApp QR"
                            class="w-64 h-64 border rounded-lg bg-white p-2"
                        />
                        <ol class="text-sm mt-4 space-y-1 text-gray-700 dark:text-gray-300 list-decimal pr-5">
                            <li>افتح واتساب على الهاتف المخصص للنظام.</li>
                            <li>اذهب إلى الإعدادات → الأجهزة المرتبطة.</li>
                            <li>اضغط <strong>«ربط جهاز»</strong> ثم امسح هذا الرمز.</li>
                        </ol>
                        @if (! empty($status['qrAge']))
                            <div class="text-xs text-gray-500 mt-2">عمر الرمز: {{ $status['qrAge'] }} ث</div>
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500">
                        <svg class="w-10 h-10 animate-spin mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <div class="text-sm">جارٍ تجهيز رمز QR…</div>
                    </div>
                @endif
            </div>

            {{-- Test send --}}
            <div class="rounded-xl border bg-white dark:bg-gray-900 p-6 shadow-sm">
                <h3 class="text-lg font-semibold mb-4">إرسال رسالة اختبار</h3>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">رقم المستلم (دولي بدون +)</label>
                        <input
                            type="tel"
                            wire:model="testNumber"
                            placeholder="966512345678"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">نص الرسالة</label>
                        <textarea
                            wire:model="testMessage"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 text-sm"
                        ></textarea>
                    </div>

                    <x-filament::button
                        wire:click="sendTest"
                        icon="heroicon-o-paper-airplane"
                        color="primary"
                        :disabled="! $isConnected">
                        إرسال
                    </x-filament::button>

                    @if (! $isConnected)
                        <div class="text-xs text-amber-600">يجب ربط الجهاز أولاً قبل إرسال أي رسالة.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
