# Wafaa WhatsApp Bridge

خدمة Node.js صغيرة (Express + Baileys) تربط نظام Laravel بحساب واتساب عبر مسح رمز QR — مثل واتساب ويب تماماً، لكن خاصة بالنظام.

---

## التشغيل على Namecheap cPanel — Node.js Selector

> هذي الطريقة الموصى بها لأنك مشترك على نيم تشيب وعندك Node.js Selector.

### 1) تحميل ملفات الجسر إلى السيرفر

ارفع المجلد كاملاً (`whatsapp-bridge/`) إلى مكان خارج `public_html`، مثلاً:

```
/home/ihvihkli/whatsapp-bridge
```

> لا تضعه داخل `public_html` — لا حاجة لذلك ولا نريد أن يكون متاحاً مباشرة على الإنترنت بدون البروكسي.

### 2) إنشاء تطبيق Node في cPanel

1. ادخل cPanel → ابحث عن **«Setup Node.js App»** (أو **«Node.js Selector»**).
2. اضغط **Create Application** وأدخل:
   - **Node.js version**: 18.x أو أحدث
   - **Application mode**: Production
   - **Application root**: `whatsapp-bridge` (المسار النسبي من home)
   - **Application URL**: اختر دومين فرعي مخصص للجسر (مثل `wa.system.wp-ar.net`) أو ضع المسار `/whatsapp-bridge` تحت دومين موجود.
   - **Application startup file**: `server.js`
3. في قسم **Environment Variables** أضِف:
   - `API_KEY` = أي قيمة عشوائية طويلة (احفظها — ستضعها في Laravel `.env` لاحقاً). توليدها مرة:
     ```bash
     openssl rand -hex 32
     ```
   - (اختياري) `LOG_LEVEL=info`

4. اضغط **Create**.

### 3) تنصيب الاعتماديات

في cPanel نفسه، بعد إنشاء التطبيق، تظهر زر **«Run NPM Install»** — اضغطه. لو لم يظهر، افتح Terminal من cPanel:

```bash
source /home/ihvihkli/nodevenv/whatsapp-bridge/18/bin/activate
cd /home/ihvihkli/whatsapp-bridge
npm install --omit=dev
```

(عدّل الإصدار `18` للإصدار الذي اخترته.)

### 4) تشغيل التطبيق

- في صفحة Setup Node.js App اضغط **Start App** (أو **Restart**).
- يجب أن تظهر حالة **Running**.

اختبر بسرعة:
```
GET https://wa.system.wp-ar.net/health
```
يجب أن يرد:
```json
{ "ok": true, "uptime": ..., "connection": "qr" }
```

### 5) ربط رقم الواتساب

1. ادخل لوحة وفاء بحساب **مدير النظام**.
2. اذهب إلى **القائمة الجانبية → النظام → إعدادات واتساب**.
3. سيظهر رمز QR.
4. على الهاتف الذي يحتوي رقم النظام: واتساب → الإعدادات → **الأجهزة المرتبطة** → **ربط جهاز** → امسح الرمز.
5. خلال ثوانٍ تظهر الحالة **«متصل»** مع رقم الهاتف.
6. جرّب «إرسال رسالة اختبار» للتأكد.

---

## الإعداد من جانب Laravel

أضف في ملف `.env` على سيرفر Laravel:

```ini
WHATSAPP_BRIDGE_URL=https://wa.system.wp-ar.net
WHATSAPP_BRIDGE_KEY=القيمة-التي-وضعتها-في-API_KEY
```

ثم:
```bash
php artisan config:clear
php artisan config:cache
```

من لحظة الربط، أي إشعار يصل لمستخدم له `whatsapp_number` محفوظ سيُرسل تلقائياً عبر واتساب أيضاً (إضافة لإيميل وجرس اللوحة).

---

## نقاط النهاية (Endpoints)

كلها تتطلب رأس `X-API-Key: <KEY>` ما عدا `/health`.

| Method | Path | الوصف |
|---|---|---|
| GET | `/health` | عام — اختبار حياة |
| GET | `/status` | حالة الاتصال + QR + الرقم المرتبط |
| GET | `/qr` | صورة PNG لرمز QR الحالي (لو وجد) |
| POST | `/send` | إرسال نص. body: `{ "phone": "966...", "message": "نص" }` |
| POST | `/disconnect` | فصل الجلسة وحذف ملفات المصادقة |

---

## ملاحظات

- ملفات المصادقة تُحفظ في `auth_info/` — احتفظ بنسخة احتياطية لو أردت تجنّب إعادة المسح بعد ترقية.
- لو cPanel يطفئ العملية لخمول — أضف Cron Job يضرب `/health` كل دقيقتين:
  ```
  */2 * * * * curl -fsS https://wa.system.wp-ar.net/health > /dev/null 2>&1
  ```
- الجسر لا يستقبل رسائل من مرسلين عشوائيين — هو فقط يرسل. عدد الإشعارات داخل المؤسسة قليل جداً، فالمخاطر منخفضة.
