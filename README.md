# نظام وفاء (Wafaa)

نظام إدارة المشاريع الإنسانية والخيرية — يُوثّق دورة حياة المشروع من الإنشاء حتى الإغلاق، مع متابعة المرفقات والحركات المالية وتقرير نهائي لكل مشروع، وتقييدات وصول حسب الدور والدولة، وإشعارات داخلية عبر لوحة Filament ورسائل WhatsApp اختيارية.

> **الجمهور:** المطورون الجدد على المشروع + المشرفون على النشر. يغطي هذا الملف: البنية التقنية، الأدوار والصلاحيات، دورة حياة المشروع، الإشعارات، الأتمتة، التثبيت، النشر، ومراجع الملفات الأساسية.

---

## المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [البنية التقنية](#البنية-التقنية)
3. [هيكلة المستودع](#هيكلة-المستودع)
4. [الأدوار (8 أدوار)](#الأدوار-8-أدوار)
5. [الصلاحيات](#الصلاحيات)
6. [تقييد حسب الدولة (Country Scoping)](#تقييد-حسب-الدولة-country-scoping)
7. [دورة حياة المشروع](#دورة-حياة-المشروع)
8. [حالة التوثيق ونوعه](#حالة-التوثيق-ونوعه)
9. [الإغلاق التلقائي (ProjectAutoClose)](#الإغلاق-التلقائي-projectautoclose)
10. [التأخير التلقائي (ProjectAutoDelay)](#التأخير-التلقائي-projectautodelay)
11. [الحركات المالية](#الحركات-المالية)
12. [المرفقات / المعززات](#المرفقات--المعززات)
13. [الإشعارات الداخلية](#الإشعارات-الداخلية)
14. [جسر واتساب (WhatsApp Bridge)](#جسر-واتساب-whatsapp-bridge)
15. [التثبيت (Setup)](#التثبيت-setup)
16. [الأوامر (Artisan)](#الأوامر-artisan)
17. [النشر (Deployment)](#النشر-deployment)
18. [التدقيق والتتبع (Audit)](#التدقيق-والتتبع-audit)
19. [مراجع الملفات الأساسية](#مراجع-الملفات-الأساسية)
20. [قواعد التطوير](#قواعد-التطوير)

---

## نظرة عامة

نظام وفاء عبارة عن لوحة إدارية واحدة (Filament) مُثبّتة على `/admin` تُدير:

- **المنظمات** (فردية / مؤسسية) — قواعد إغلاق المشروع تختلف حسب نوع المنظمة.
- **المشاريع** — كل مشروع يتبع منظمة تنفيذ + اختيارياً منظمة ممولة، وينتقل عبر سلسلة حالات من "جديد" إلى "مُغلق / مكتمل".
- **الحركات المالية** — واردة (تمويل) / صادرة (صرف)، مع اعتماد مركزي.
- **المرفقات ("المعززات")** — وثائق دعم على مستوى المشروع، مع اعتماد مركزي.
- **المستخدمون والأدوار** — 8 أدوار بصلاحيات مختلفة، بعضها مقيّد بدولة محددة.
- **الإشعارات** — داخلية (Filament Notifications) + WhatsApp اختيارية عبر bridge محلي.
- **التقارير + سجل النشاط + سجل تحولات الحالة** — تدقيق كامل لكل فعل.

قاعدة البيانات: **MySQL** (production). الاختبار على SQLite ممكن لكنه غير مستخدم في هذا الـ repo.

---

## البنية التقنية

| الطبقة | التقنية | الإصدار |
|---|---|---|
| PHP | — | `^8.2` |
| Framework | [Laravel](https://laravel.com) | `^11.31` |
| Admin Panel | [Filament](https://filamentphp.com) | `^5.4` |
| RBAC | [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) | `^6.10` |
| Audit | [spatie/laravel-activitylog](https://spatie.be/docs/laravel-activitylog) | `^4.12` |
| Frontend (لو احتجت dev) | Vite + Tailwind CSS | — |
| WhatsApp Bridge | Node.js 18+ + [@whiskeysockets/baileys](https://www.npmjs.com/package/@whiskeysockets/baileys) | — |
| الخادم المستهدف | cPanel / shared hosting + MySQL | — |

---

## هيكلة المستودع

```
wafaa/
├── app/
│   ├── Concerns/
│   │   └── ScopesByCountry.php          # Trait يُطبّق تقييد الدول على Models
│   ├── Console/Commands/
│   │   ├── AssignUserRolesCommand.php   # أداة CLI لإسناد الأدوار
│   │   └── AutoDelayProjectsCommand.php # مهمة مُجدوَلة (daily) للتحويل إلى متأخر
│   ├── Enums/
│   │   ├── Role.php                     # 8 أدوار (المصدر الموحّد)
│   │   └── Permission.php               # قائمة الصلاحيات
│   ├── Filament/
│   │   ├── Pages/                       # Dashboard, Reports, ProjectWorkspace…
│   │   └── Resources/                   # مُوارد Filament (Projects, Users, ...)
│   ├── Models/                          # 10 موديلات (Project, User, …)
│   ├── Notifications/
│   │   ├── Channels/WhatsAppChannel.php
│   │   └── InternalActionNotification.php
│   ├── Observers/
│   │   ├── AttachmentObserver.php
│   │   ├── FinancialTransactionObserver.php
│   │   └── ProjectObserver.php          # يُحدّث documentation_status + history
│   ├── Policies/                        # 11 Policy (Project, Attachment, ...)
│   ├── Providers/
│   │   ├── AppServiceProvider.php       # تسجيل Observers + Gate::before
│   │   └── Filament/AdminPanelProvider.php
│   └── Services/
│       ├── ActivityLogger.php           # wrapper على spatie/activitylog
│       ├── AlertNotifier.php
│       ├── InternalNotifier.php         # dispatch/notifyRoles + country scoping
│       ├── ProjectAutoClose.php         # إغلاق تلقائي بشروط المنظمة
│       ├── ProjectAutoDelay.php         # تحويل تلقائي إلى متأخر
│       └── ProjectNumberGenerator.php   # IVD-YYYY-NNNN
├── config/
│   ├── notifications-recipients.php     # مصفوفة event → roles (مصدر موحّد)
│   ├── permissions.php                  # مصفوفة role → permissions (مصدر موحّد)
│   ├── services.php                     # WhatsApp + SMTP
│   └── world_countries.php              # قائمة الدول
├── database/
│   ├── migrations/                      # مرتّبة زمنياً
│   └── seeders/
│       ├── CountrySeeder.php
│       ├── DatabaseSeeder.php
│       └── RolesAndPermissionsSeeder.php
├── lang/
│   └── ar/                              # الترجمات العربية (الواجهة + الإشعارات)
├── resources/
│   ├── css/ + js/                       # Vite
│   └── views/
├── whatsapp-bridge/                     # خدمة Node مستقلة (اختيارية)
└── README.md                            # هذا الملف
```

---

## الأدوار (8 أدوار)

المصدر الموحّد: <ref_file file="/home/ubuntu/repos/wafaa/app/Enums/Role.php" />

| الدور (slug) | الاسم العربي | النطاق | الوصف |
|---|---|---|---|
| `system_admin` | مدير النظام | — | كل الصلاحيات (Gate::before يمنح الوصول مباشرة). |
| `project_creator` | منشئ المشروع | — | إنشاء / تعديل المشاريع حتى قرار الجاهزية فقط. |
| `readiness_approver` | معتمد الجاهزية | **دولة** | اعتماد / رفض جاهزية مشاريع دولته. |
| `project_executor` | منفذ المشروع | **دولة** | تحديث حالة التنفيذ + رابط التوثيق + إدخال معززات. |
| `enhancer_entry_country` | مدخل معززات الدولة | **دولة** | إدخال المرفقات + رابط التوثيق فقط (بدون حركات مالية). |
| `enhancer_finance_central` | مدخل ومعتمد المعززات والحوالات | **دولة** | مشاهدة + إضافة + اعتماد مرفقات والحركات المالية (بدون تعديل سجل قائم)، مقيّد بدولة المستخدم. |
| `final_report_preparer` | مُعدّ التقرير النهائي | — | قراءة فقط + كتابة حقل التقرير النهائي. |
| `board_supervisor` | مشرف النظام (مجلس الإدارة) | — | قراءة فقط + اعتماد التقرير النهائي + إرجاع الحالة. |

### ملاحظات الأدوار

- الأدوار **ثابتة** ولا تُضاف ديناميكياً من الواجهة. لتعديلها: حدّث <ref_file file="/home/ubuntu/repos/wafaa/app/Enums/Role.php" /> + <ref_file file="/home/ubuntu/repos/wafaa/config/permissions.php" /> + شغّل الـ seeder.
- المستخدم قد يملك **عدة أدوار** معاً (Spatie roles).
- المستخدم قد يحصل على **صلاحيات إضافية** مباشرة خارج دوره (Spatie direct permissions) — تُعرض في صفحة تعديل المستخدم.

---

## الصلاحيات

المصدر الموحّد: <ref_file file="/home/ubuntu/repos/wafaa/app/Enums/Permission.php" /> + <ref_file file="/home/ubuntu/repos/wafaa/config/permissions.php" />.

### النمط

`<domain>.<action>[.<scope>]` مثل `projects.readiness.approve`.

### المجالات

| المجال | الصلاحيات |
|---|---|
| users / settings | `users.manage`, `settings.manage` |
| projects | `projects.view`, `.create`, `.update`, `.readiness.approve`, `.execution.update`, `.rollback`, `.close` |
| attachments | `.view`, `.create`, `.update`, `.approve` |
| financial | `.view`, `.create`, `.update`, `.approve` |
| final_report | `.prepare`, `.approve` |
| supervision | `supervision.note` |

### مصفوفة الدور ↔ الصلاحيات

موجودة بالكامل في <ref_file file="/home/ubuntu/repos/wafaa/config/permissions.php" />. **قاعدة ذهبية:** عدّل الملف ثم:

```bash
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force
php artisan permission:cache-reset
```

لا تعدّل الـ seeder مباشرة — هو يقرأ الـ config.

---

## تقييد حسب الدولة (Country Scoping)

بعض الأدوار **مقيّدة بدول محددة**. المستخدم يُربَط بدولة/دول عبر جدول `user_countries`.

### الأدوار المقيّدة بدولة

```php
readiness_approver
project_executor
enhancer_entry_country
enhancer_finance_central
```

> **ملاحظة:** اسم الدور `enhancer_finance_central` يحتوي على كلمة "central" لكنه مقيّد بدولة المستخدم بناءً على متطلب التشغيل الفعلي. لا يرى البيانات (مشاريع/حوالات/مرفقات/تقارير) لدول غير دولته.

### كيف يعمل

- كل مشروع/مرفق/حركة مالية يملك `country_id`.
- Trait <ref_file file="/home/ubuntu/repos/wafaa/app/Concerns/ScopesByCountry.php" /> يُطبّق `WHERE country_id IN (user's countries)` على استعلامات Model تلقائياً للأدوار المقيّدة.
- Policies (مثل <ref_file file="/home/ubuntu/repos/wafaa/app/Policies/ProjectPolicy.php" />) تُعيد فحص الدولة قبل أي `update`/`delete`.
- **الإشعارات التلقائية** تأخذ الأمر بعين الاعتبار: `InternalNotifier::dispatch()` يستدعي `recipientsFor()` الذي يُصفّي الأدوار المقيّدة على دولة الموضوع فقط. هكذا لا يصل إشعار مشروع الدولة A لمستخدم الدولة B.

> **مهم:** لا تستخدم `InternalNotifier::notifyRoles()` لأحداث مشاريع فيها أدوار مقيّدة بدولة — استخدم `dispatch($event, $subject, $context)` حتى يُطبّق التصفية الصحيحة.

---

## دورة حياة المشروع

### الحالات (state enum)

المصدر: <ref_file file="/home/ubuntu/repos/wafaa/database/migrations/2026_04_04_215641_create_projects_table.php" />

| الحالة | العربي | الوصف |
|---|---|---|
| `new` | جديد | أُنشئ للتو، لم يُقدَّم للاعتماد بعد. |
| `pending_readiness` | بانتظار اعتماد الجاهزية | انتظار قرار `readiness_approver` الخاص بدولته. |
| `ready_for_execution` | جاهز للتنفيذ | اعتُمد، جاهز ليبدأ التنفيذ. |
| `in_execution` | قيد التنفيذ | التنفيذ جارٍ. |
| `pending_documentation` | بانتظار التوثيق | انتهى التنفيذ، ينتظر روابط التوثيق. |
| `delayed` | متأخر | تجاوز `expected_end_date` دون إكمال (تلقائياً). |
| `completed` | مكتمل | الشروط استوفت، يُحدَّد حسب نوع المنظمة. |
| `closed` | مُغلق | مُغلق يدوياً من مدير النظام أو مشرف. |

### مخطط التحولات

```
   new
    │  (readiness_approver)
    ▼
pending_readiness ──rejected──► pending_readiness (يبقى، إشعار للمنشئ)
    │
    └──approved──► ready_for_execution
                        │
                        ▼
                   in_execution ──(expected_end_date past)──► delayed
                        │
                        ▼
              pending_documentation
                        │
      [شروط الإغلاق تطابقت] ──► completed  (ProjectAutoClose)
                        │                      │
                        │ (system_admin)       │ (system_admin)
                        ▼                      ▼
                      closed ◄─────────────── closed
```

- **الإرجاع (`rollback`):** مشرف المجلس أو مدير النظام يملك صلاحية `projects.rollback` لإرجاع المشروع خطوة إلى الوراء (قيد التنفيذ مثلاً) إن لزم.
- **سجل الحالات:** كل تحوّل (يدوي أو تلقائي) يُدرج صف في `project_state_histories` عبر `ProjectObserver::updated`. هذا يوفّر سجل تدقيق كامل.
- **رفض الجاهزية:** المشروع يبقى في `pending_readiness` (الحالة لم تتغيّر)، فـ Observer لن يرى التغيير. لذا <ref_file file="/home/ubuntu/repos/wafaa/app/Filament/Resources/Projects/Tables/ProjectsTable.php" /> يُدرج صف history يدوياً عند الرفض إذا كانت الحالة السابقة = `pending_readiness`.

---

## حالة التوثيق ونوعه

### نوع التوثيق (`documentation_type`)

يُحدَّد **إلزامياً عند إنشاء المشروع**، ولا يتم تغييره إلا في حالات خاصة:

| القيمة | العربي | أثر الحالة |
|---|---|---|
| `one_time` | مرة واحدة | أي رابط توثيق مُدخل → `documentation_status = complete` |
| `continuous` | مستمر | أي رابط توثيق مُدخل → `documentation_status = partial` |
| `periodic` | دوري | أي رابط توثيق مُدخل → `documentation_status = partial` |

### حالة التوثيق (`documentation_status`)

تُدار آلياً بواسطة `ProjectObserver::updating()` استناداً إلى `documentation_type` والـ `photo_album_url` + `video_album_url`.

القواعد:
- لا يوجد أي رابط → `not_started` (ما عدا لو الحالة اليدوية = `complete` فتبقى).
- يوجد رابط + النوع غير محدد → لا تغيير (انتظار تحديد النوع).
- يوجد رابط + النوع = `one_time` → `complete`.
- يوجد رابط + النوع = `continuous` / `periodic` → `partial`.
- حماية: `complete` اليدوية لا تُكتب فوقها بـ `partial`.

راجع <ref_snippet file="/home/ubuntu/repos/wafaa/app/Observers/ProjectObserver.php" lines="42-125" />.

---

## الإغلاق التلقائي (ProjectAutoClose)

<ref_file file="/home/ubuntu/repos/wafaa/app/Services/ProjectAutoClose.php" />

عند أي حركة مالية معتمَدة أو تغيير رابط توثيق، يُستدعى `ProjectAutoClose::evaluate($project)` تلقائياً من Observers. يُغلق المشروع إلى `completed` عند استيفاء الشروط **معاً**:

### الشروط العامة (كل الحالات)

- الحالة الحالية ∈ `{in_execution, pending_documentation, delayed}` (ليست مبكرة ولا طرفية).
- الرصيد المالي المعتمَد (incoming − outgoing) = 0 (مع فارق عائم صغير).
- **لا توجد** حركات مالية غير معتمَدة (pending/rejected/NULL) على المشروع.

### شروط خاصة بنوع المنظمة

| نوع المنظمة | الشرط الإضافي |
|---|---|
| `individual` (فردية) | `photo_album_url` OR `video_album_url` مُدخل |
| `institution` (مؤسسية) | `final_report_approved = true` |

### التزامن (Concurrency)

يستخدم `DB::transaction` + `lockForUpdate()` مع إعادة فحص الحالة داخل القفل، لمنع مستدعيَين متزامنَين من إدراج صفَي `state_history` + إشعارَين مزدوجَين لنفس الحدث.

---

## التأخير التلقائي (ProjectAutoDelay)

<ref_file file="/home/ubuntu/repos/wafaa/app/Services/ProjectAutoDelay.php" />

يحوّل المشاريع المتجاوزة `expected_end_date` إلى `delayed`.

### القواعد

- فقط المشاريع في `{ready_for_execution, in_execution, pending_documentation}`.
- تُستبعد الحالات الطرفية (`completed`, `closed`) و `pending_readiness` / `new`.
- يُشعر: `system_admin` + `project_executor` (مقيّد بدولة) + `enhancer_entry_country` (مقيّد بدولة).

### آلية التشغيل

**طريقتان:**

1. **تلقائياً عند زيارة Dashboard** عبر `ProjectAutoDelay::kick()` — محمي بـ `Cache::lock('project_auto_delay:lock', 300)` + throttle 6 ساعات. آمن أمام الطلبات المتزامنة.

2. **عبر scheduler** (موصى به لبيئة الإنتاج):
   ```php
   Schedule::command('projects:auto-delay')->dailyAt('00:15');
   ```
   راجع <ref_file file="/home/ubuntu/repos/wafaa/app/Console/Commands/AutoDelayProjectsCommand.php" />.

### التزامن

نفس نمط `ProjectAutoClose`: `lockForUpdate()` مع إعادة فحص الحالة، فلا يُحوَّل المشروع إلى `delayed` مرتين ولا يُرسَل إشعار مزدوج.

---

## الحركات المالية

### الموديل: `FinancialTransaction`

| الحقل | الوصف |
|---|---|
| `transaction_type` | `incoming` (واردة) / `outgoing` (صادرة) |
| `approval_status` | `pending` / `approved` / `rejected` |
| `amount`, `currency` | القيمة + العملة |
| `transaction_date` | تاريخ العملية |
| `funding_source_country` | دولة مصدر التمويل (واردة فقط) |
| `transfer_method` | طريقة التحويل |
| `sender_attachment`, `receiver_attachment` | إيصالات مرفقة |

### سير العمل

1. **إنشاء** — `enhancer_finance_central` أو `system_admin`. `approval_status = pending`.
2. **اعتماد / رفض** — نفس الدورين عبر زرّ "اعتماد" في الجدول.
3. **تأثيرات الاعتماد:**
   - `financial_status` للمشروع يُحتسب إعادةً.
   - `ProjectAutoClose::evaluate()` يُستدعى (قد يُغلق المشروع إن استوفت الشروط).
   - إشعار `financial.approved` أو `financial.rejected` يُرسَل.

### قاعدة مهمة حول `funding_source_country`

الحقل يظهر + مطلوب + يُحفَظ **فقط** إذا `transaction_type = incoming`. التبديل إلى `outgoing` يُخفي الحقل ويستبعده من الحفظ (لا يترك قيمة شبحية على حركة صادرة). راجع <ref_file file="/home/ubuntu/repos/wafaa/app/Filament/Resources/FinancialTransactions/Schemas/FinancialTransactionForm.php" />.

---

## المرفقات / المعززات

### الموديل: `Attachment`

مرتبط بمشروع. الاستخدام: رفع صور/وثائق/PDFs داعمة للمشروع (معززات).

### الدورة

1. **إنشاء** — `enhancer_entry_country` (يراه فقط لمشاريع دولته) أو `enhancer_finance_central` أو `system_admin`.
2. **اعتماد / رفض** — `enhancer_finance_central` أو `system_admin`.
3. **الإشعارات:** `attachment.created` → للمعتمد المركزي. `attachment.approved` / `.rejected` → لصاحب الرفع.

---

## الإشعارات الداخلية

<ref_file file="/home/ubuntu/repos/wafaa/app/Services/InternalNotifier.php" /> + <ref_file file="/home/ubuntu/repos/wafaa/config/notifications-recipients.php" />.

### نقطتا الدخول

| الدالة | الاستخدام |
|---|---|
| `InternalNotifier::dispatch($event, $subject, $context)` | **المفضّل.** يقرأ `notifications-recipients.php`، يُطبّق country scoping عبر `recipientsFor()`، يستبعد الفاعل (actor) تلقائياً. |
| `InternalNotifier::notifyRoles($roles, $titleKey, $bodyKey, $replace, $url)` | توافق-للخلف — **لا** يُطبّق country scoping. استخدم `dispatch()` للأحداث الجديدة. |

### مصفوفة الأحداث

محدّدة بالكامل في <ref_file file="/home/ubuntu/repos/wafaa/config/notifications-recipients.php" />. لتعديل المستلمين لحدث ما: حدّث هذا الملف فقط ولا حاجة لتعديل الكود.

الأحداث الحالية تشمل:
- `project.created`, `project.updated`, `project.readiness_approved`, `project.readiness_rejected`
- `project.execution_updated`, `project.final_report_drafted`, `project.final_report_approved`
- `project.rolled_back`, `project.closed`, `project.completed`, `project.archived`, `project.auto_delayed`
- `attachment.created`, `attachment.approved`, `attachment.rejected`
- `financial.created`, `financial.approved`, `financial.rejected`

### قنوات التسليم

- **Database** (دائماً) — تظهر في جرس Filament.
- **WhatsApp** (اختيارياً) — إذا كان الجسر مُفعّلاً + `whatsapp_number` موجود على المستخدم.
- **Mail** (اختيارياً) — إذا كان SMTP مُعدّاً.

---

## جسر واتساب (WhatsApp Bridge)

خدمة Node.js مستقلة داخل `whatsapp-bridge/` تستخدم [Baileys](https://www.npmjs.com/package/@whiskeysockets/baileys) لإرسال رسائل واتساب من رقم موظف مُقرَن (QR code pairing).

### نقاط النهاية

| Endpoint | الغرض |
|---|---|
| `GET /qr` | عرض صورة QR للمصادقة. |
| `GET /status` | حالة الاتصال. |
| `POST /send` | إرسال `{ phone, message }` — يتطلب `X-API-Key`. |
| `POST /disconnect` | قطع الاتصال. |
| `GET /health` | فحص. |

### الإعداد

```bash
cd whatsapp-bridge
npm install
cp .env.example .env   # ← WHATSAPP_API_KEY=...
npm start              # 3001 by default
```

ثم في `.env` الخاص بـ Laravel:
```
WHATSAPP_BRIDGE_URL=http://localhost:3001
WHATSAPP_BRIDGE_KEY=<same api key>
```

`config/services.php` يُعيد قراءة هذه المتغيرات، و `WhatsAppChannel` يستخدمها مباشرة من `config()` (بدون `env()` fallback في الكود — يعمل صحيحاً تحت `config:cache`).

---

## التثبيت (Setup)

### المتطلبات

- PHP 8.2+ مع extensions: `pdo_mysql`, `mbstring`, `xml`, `bcmath`, `gd`, `fileinfo`
- Composer 2
- MySQL 8 أو MariaDB 10.11+
- Node.js 18+ (لجسر واتساب — اختياري)

### الخطوات

```bash
# 1) clone
git clone https://github.com/RaafatAraby/wafaa.git
cd wafaa

# 2) dependencies
composer install

# 3) env
cp .env.example .env
php artisan key:generate
# عدّل DB_* + WHATSAPP_* + MAIL_* في .env

# 4) schema + seeders
php artisan migrate --seed
# الـ seeder يزرع: دول + roles/permissions + مستخدم system_admin افتراضي

# 5) رابط التخزين (للملفات المرفوعة)
php artisan storage:link

# 6) تشغيل
php artisan serve
# افتح http://localhost:8000/admin
```

### حساب افتراضي

بعد أول `db:seed`، حساب مدير النظام مُعرَّف في `DatabaseSeeder` — راجع <ref_file file="/home/ubuntu/repos/wafaa/database/seeders/DatabaseSeeder.php" />.

---

## الأوامر (Artisan)

| الأمر | الغرض |
|---|---|
| `php artisan projects:auto-delay` | تحويل المشاريع المتجاوزة تاريخ الانتهاء إلى `delayed`. يُوصى بجدولته يومياً. |
| `php artisan assign:user-roles` | أداة تفاعلية لإسناد/إزالة أدوار المستخدمين. |
| `php artisan permission:cache-reset` | مسح كاش صلاحيات Spatie — اضغط هذا بعد تغيير `config/permissions.php` + seeder. |
| `php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force` | مزامنة DB مع `config/permissions.php`. |
| `php artisan migrate --force` | تنفيذ migrations في production. |
| `php artisan optimize:clear` | مسح كل أنواع الكاش (views, config, routes, events). |
| `php artisan config:cache && php artisan view:cache` | تحسين الأداء في production. |

### جدولة `auto-delay`

أضف إلى `app/Console/Kernel.php` أو (في Laravel 11) في `routes/console.php`:

```php
Schedule::command('projects:auto-delay')->dailyAt('00:15');
```

ثم على الخادم:
```bash
* * * * * cd /path/to/wafaa && php artisan schedule:run >> /dev/null 2>&1
```

---

## النشر (Deployment)

### نمط النشر المُستخدم حالياً

- الخادم: cPanel (shared hosting).
- النشر يدوي عبر tarball (يُنتَج خارج الـ repo) يُستخرج فوق المسار `~/system.ihvider.org`.
- بعد أي نشر:
  ```bash
  php artisan migrate --force
  php artisan optimize:clear
  php artisan config:cache
  php artisan view:cache
  ```

### نصائح

- **لا** تستخدم `env()` في كود التطبيق — استخدم `config()`. في production `config:cache` يجعل `env()` ترجع `null`.
- إذا غيّرت `config/permissions.php`: شغّل الـ seeder + `permission:cache-reset`.
- إذا أضفت حدث إشعار جديد: أضفه إلى `config/notifications-recipients.php` + مفاتيح الترجمة `notifications.<event>.title` / `.body` في `lang/ar/notifications.php`.

---

## التدقيق والتتبع (Audit)

النظام يسجّل ثلاث طبقات من التدقيق:

1. **`project_state_histories`** — صف لكل تحول حالة لكل مشروع (`from_state`, `to_state`, `changed_by`, `notes`, `created_at`).
2. **`activity_log`** (spatie/activitylog) — كل `create`/`update`/`delete` على الموديلات + الأحداث المخصّصة التي تُسجَّلها `ActivityLogger::log()`.
3. **`InternalActionNotification`** — كل الإشعارات مخزّنة في `notifications` table (Laravel default) وقابلة للاستعلام.

صفحات مخصصة:
- `/admin/activity-logs` — سجل النشاط
- `/admin/project-state-histories` — تحولات الحالة

---

## مراجع الملفات الأساسية

| الموضوع | الملف |
|---|---|
| تعريف الأدوار | `app/Enums/Role.php` |
| تعريف الصلاحيات | `app/Enums/Permission.php` |
| مصفوفة الدور → صلاحيات | `config/permissions.php` |
| مصفوفة الحدث → مستلمين | `config/notifications-recipients.php` |
| دورة حياة + history | `app/Observers/ProjectObserver.php` |
| سير المشروع (UI actions) | `app/Filament/Resources/Projects/Tables/ProjectsTable.php` |
| إغلاق تلقائي | `app/Services/ProjectAutoClose.php` |
| تأخير تلقائي | `app/Services/ProjectAutoDelay.php` |
| إشعارات | `app/Services/InternalNotifier.php` |
| قناة WhatsApp | `app/Notifications/Channels/WhatsAppChannel.php` |
| تقييد الدول | `app/Concerns/ScopesByCountry.php` |
| Gate::before | `app/Providers/AppServiceProvider.php` |
| Filament Panel | `app/Providers/Filament/AdminPanelProvider.php` |
| seeder الصلاحيات | `database/seeders/RolesAndPermissionsSeeder.php` |
| الترجمات | `lang/ar/` |

---

## قواعد التطوير

### نمط الإشعارات

**افعل:**
- استخدم `InternalNotifier::dispatch($event, $subject, $context)` دائماً.
- عرّف الحدث في `config/notifications-recipients.php`.
- لو `context['state']` متاح — مرّره صراحة (لا تعتمد على `$subject->state` الذي قد يكون قديم الذاكرة بعد تحديث DB على fresh instance).

**لا تفعل:**
- لا تستخدم `notifyRoles()` لأحداث فيها أدوار مقيّدة بدولة.
- لا تستخدم `env()` في Controllers/Services — فقط في ملفات `config/`.

### نمط Observers المتزامن

للعمليات الحرجة (تغيير حالة + إدراج audit + إشعار):
```php
DB::transaction(function () use ($project) {
    $fresh = Project::whereKey($project->getKey())->lockForUpdate()->first();
    // إعادة فحص الحالة — إن تغيّرت، ارجع false
    // ثم update + insert history
});
```

هذا يمنع duplicate audit rows في حال طلبَين متزامنَين. راجع `ProjectAutoClose` + `ProjectAutoDelay` كنماذج.

### قواعد Migrations

- استخدم `Schema::hasColumn()` قبل أي تعديل على عمود موجود — يجعل الـ migration idempotent.
- لا تعدّل migration مُنشورة — أنشئ migration جديدة للتعديل.
- سمّ الـ migrations حسب التاريخ الحالي (`YYYY_MM_DD_HHMMSS`).

### اللغة

- جميع نصوص الواجهة الموجهة للمستخدم **بالعربية** عبر `__('...')` + ملفات `lang/ar/`.
- لا تُضمّن عربية مباشرة في الكود إلا في الـ fallback داخل `ProjectObserver` / state-history notes.

---

## الترخيص والمساهمة

> هذا مستودع خاص. المساهمات عبر pull request مع وصف واضح وتوثيق للتغييرات. لا تدمج مباشرة في `main`.

للأسئلة: افتح [issue على GitHub](https://github.com/RaafatAraby/wafaa/issues).
