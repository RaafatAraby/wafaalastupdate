@php
    /** @var string $title */
    /** @var string $body */
    /** @var string|null $url */
    /** @var string|null $event */
    /** @var array $context */
    /** @var \App\Models\User $recipient */

    $brandColor = '#0F6E56';
    $brandLight = '#E1F5EE';

    $eventColor = match (true) {
        str_contains((string) $event, 'approved'),
        str_contains((string) $event, 'closed') => '#16a34a',
        str_contains((string) $event, 'rejected'),
        str_contains((string) $event, 'rolled_back') => '#dc2626',
        str_contains((string) $event, 'created') => $brandColor,
        default => $brandColor,
    };

    $eventLabel = match (true) {
        str_contains((string) $event, 'approved') => 'اعتماد',
        str_contains((string) $event, 'rejected') => 'رفض',
        str_contains((string) $event, 'rolled_back') => 'إرجاع لمرحلة سابقة',
        str_contains((string) $event, 'closed') => 'إغلاق',
        str_contains((string) $event, 'created') => 'إنشاء',
        str_contains((string) $event, 'updated') => 'تحديث',
        str_contains((string) $event, 'archived') => 'أرشفة',
        default => 'إشعار',
    };

    $stateMap = [
        'new' => 'جديد',
        'pending_readiness' => 'بانتظار اعتماد الجاهزية',
        'ready_for_execution' => 'جاهز للتنفيذ',
        'not_ready' => 'غير جاهز للتنفيذ',
        'in_execution' => 'قيد التنفيذ',
        'completed' => 'تم التنفيذ',
        'delayed' => 'متأخر',
        'final_report_pending' => 'بانتظار اعتماد التقرير',
        'final_report_approved' => 'التقرير معتمد',
        'closed' => 'مغلق',
    ];

    $stateRaw = $context['state'] ?? null;
    $stateLabel = $stateRaw ? ($stateMap[$stateRaw] ?? $stateRaw) : null;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Tahoma,'Segoe UI',Arial,sans-serif;color:#111827;direction:rtl;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.06);overflow:hidden;max-width:600px;width:100%;">
                    {{-- Header --}}
                    <tr>
                        <td style="background:{{ $brandColor }};padding:24px 28px;color:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-size:20px;font-weight:700;line-height:1.4;">
                                        نظام وفاء المحسنين
                                    </td>
                                    <td align="left" style="font-size:13px;opacity:0.85;">
                                        {{ now()->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Title strip --}}
                    <tr>
                        <td style="padding:24px 28px 8px 28px;">
                            <span style="display:inline-block;background:{{ $brandLight }};color:{{ $brandColor }};font-size:12px;font-weight:700;padding:4px 10px;border-radius:99px;">
                                {{ $eventLabel }}
                            </span>
                            <h1 style="font-size:22px;font-weight:800;color:#111827;margin:14px 0 6px 0;line-height:1.3;">
                                {{ $title }}
                            </h1>
                            <p style="font-size:15px;color:#374151;line-height:1.7;margin:0;">
                                {{ $body }}
                            </p>
                        </td>
                    </tr>

                    {{-- Project details card --}}
                    @if (! empty($context['project_number']) || ! empty($context['project_title']))
                    <tr>
                        <td style="padding:18px 28px 8px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="6" border="0" style="font-size:14px;color:#111827;">
                                            @if (! empty($context['project_number']))
                                            <tr>
                                                <td width="35%" style="color:#6b7280;font-weight:600;">رقم المشروع</td>
                                                <td style="font-weight:700;">{{ $context['project_number'] }}</td>
                                            </tr>
                                            @endif
                                            @if (! empty($context['project_title']))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">اسم المشروع</td>
                                                <td>{{ $context['project_title'] }}</td>
                                            </tr>
                                            @endif
                                            @if (! empty($context['country']))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">الدولة</td>
                                                <td>{{ $context['country'] }}</td>
                                            </tr>
                                            @endif
                                            @if ($stateLabel)
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">الحالة</td>
                                                <td>
                                                    <span style="display:inline-block;background:{{ $eventColor }};color:#ffffff;font-size:12px;font-weight:700;padding:3px 10px;border-radius:99px;">
                                                        {{ $stateLabel }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endif
                                            @php
                                                $eventDate = $context['transaction_date'] ?? ($context['attachment_date'] ?? null);
                                            @endphp
                                            @if (! empty($eventDate))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">التاريخ</td>
                                                <td>{{ $eventDate }}</td>
                                            </tr>
                                            @endif
                                            @if (! empty($context['transaction_type_label']))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">نوع الحركة</td>
                                                <td>{{ $context['transaction_type_label'] }}</td>
                                            </tr>
                                            @endif
                                            @if (! empty($context['attachment_category_label']))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">نوع المرفق</td>
                                                <td>{{ $context['attachment_category_label'] }}</td>
                                            </tr>
                                            @endif
                                            @if (! empty($context['amount']))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">المبلغ</td>
                                                {{-- amount is already formatted by InternalNotifier (e.g. "1,234.56 USD") --}}
                                                <td style="font-weight:700;">{{ $context['amount'] }}</td>
                                            </tr>
                                            @endif
                                            @if (! empty($context['actor_name']))
                                            <tr>
                                                <td style="color:#6b7280;font-weight:600;">منفّذ الإجراء</td>
                                                <td>{{ $context['actor_name'] }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- Notes block --}}
                    @if (! empty($context['notes']))
                    <tr>
                        <td style="padding:0 28px 8px 28px;">
                            <div style="background:#fffbeb;border-right:4px solid #f59e0b;border-radius:8px;padding:12px 14px;color:#78350f;font-size:14px;line-height:1.7;">
                                <strong style="display:block;margin-bottom:4px;color:#92400e;">ملاحظات</strong>
                                {{ $context['notes'] }}
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- CTA button --}}
                    @if (! empty($url))
                    <tr>
                        <td align="center" style="padding:18px 28px 28px 28px;">
                            <a href="{{ $url }}" style="display:inline-block;background:{{ $brandColor }};color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:12px 28px;border-radius:10px;">
                                عرض في النظام &laquo;
                            </a>
                        </td>
                    </tr>
                    @else
                    <tr><td style="padding-bottom:28px;"></td></tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 28px;color:#6b7280;font-size:12px;line-height:1.6;">
                            <strong style="color:#111827;">فريق نظام وفاء المحسنين</strong><br>
                            تم إرسال هذا الإشعار إلى: {{ $recipient->email ?? '' }}<br>
                            هذه رسالة آلية، يُرجى عدم الرد عليها.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
