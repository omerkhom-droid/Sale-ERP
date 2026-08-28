# Wazin ERP Installation Guide  
# دليل تركيب وازن ERP

---

## العربية

## 1. نبذة عن النظام

وازن ERP هو نظام لإدارة المبيعات، المشتريات، المخزون، الحسابات، السندات، القيود، التقارير، الصلاحيات، والفروع.

هذه النسخة مخصصة للتشغيل على استضافة تحت إدارة مقدم الخدمة، مع دعم فني مباشر، وليست نسخة يتم تسليم الكود الكامل منها للعميل النهائي.

---

## 2. متطلبات التشغيل

قبل التركيب تأكد من توفر التالي:

```text
PHP 8.2 أو أعلى
MySQL أو MariaDB
Composer
Node.js
npm
Apache أو Nginx
SSL Certificate
```

امتدادات PHP المطلوبة:

```text
pdo_mysql
mbstring
openssl
tokenizer
xml
ctype
json
fileinfo
zip
curl
gd
intl
```

---

## 3. رفع ملفات المشروع

ارفع ملفات المشروع إلى مجلد خارج `public_html` إن أمكن، مثال:

```text
/home/username/wazin-erp
```

يجب أن يكون الدومين موجهًا إلى مجلد:

```text
/home/username/wazin-erp/public
```

وليس إلى جذر المشروع.

مثال صحيح:

```text
domain.com  →  /public
```

مثال غير صحيح:

```text
domain.com  →  /wazin-erp
```

---

## 4. إنشاء قاعدة البيانات

أنشئ قاعدة بيانات جديدة من لوحة الاستضافة.

مثال:

```text
Database Name: wazin_erp
Database User: wazin_user
Database Password: strong_password_here
```

يجب إعطاء المستخدم كامل الصلاحيات على قاعدة البيانات.

---

## 5. تجهيز ملف البيئة

انسخ الملف:

```text
.env.example
```

إلى:

```text
.env
```

ثم عدّل القيم الأساسية:

```env
APP_NAME="Wazin ERP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
APP_FAKER_LOCALE=ar_SA

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wazin_erp
DB_USERNAME=wazin_user
DB_PASSWORD=strong_password_here

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-domain.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

---

## 6. تثبيت حزم Laravel

من داخل مجلد المشروع نفذ:

```bash
composer install --no-dev --optimize-autoloader
```

---

## 7. تثبيت ملفات الواجهة

نفذ:

```bash
npm install
npm run build
```

في حالة أن الاستضافة لا تدعم Node.js، يتم تنفيذ هذه الأوامر محليًا، ثم رفع مجلدات البناء الناتجة إلى السيرفر.

---

## 8. توليد مفتاح التطبيق

نفذ:

```bash
php artisan key:generate
```

---

## 9. إنشاء الجداول والبيانات الأساسية

لأول تركيب فقط، نفذ:

```bash
php artisan migrate --seed
```

سيتم إنشاء البيانات الأساسية التالية:

```text
الشركة الرئيسية
الفرع الرئيسي
مستخدم Master
مستخدم مالك الشركة
الأدوار والصلاحيات
دليل الحسابات
إعدادات الحسابات
تسلسل أرقام المستندات
بيانات تشغيل افتراضية
إعدادات الترخيص
```

---

## 10. ربط التخزين

نفذ:

```bash
php artisan storage:link
```

---

## 11. تنظيف وتحسين الكاش

بعد التركيب نفذ:

```bash
php artisan permission:cache-reset
php artisan optimize:clear
php artisan optimize
```

---

## 12. بيانات الدخول الافتراضية

### حساب مدير النظام الرئيسي

```text
Email: admin@wazin.test
Password: 12345678
```

### حساب مالك الشركة

```text
Email: owner@wazin.test
Password: 12345678
```

يجب تغيير كلمات المرور مباشرة بعد أول دخول.

---

## 13. إعدادات مهمة بعد أول دخول

بعد الدخول لأول مرة يجب مراجعة التالي:

```text
بيانات الشركة
بيانات الفرع
الرقم الضريبي
السجل التجاري
العنوان الوطني
شعار الشركة
بيانات المستخدمين
الصلاحيات
إعدادات الترخيص
إعدادات الحسابات
```

---

## 14. أوامر ما بعد أي تحديث

عند رفع تحديث جديد للنظام، نفذ:

```bash
php artisan migrate
php artisan permission:cache-reset
php artisan optimize:clear
php artisan optimize
```

في حالة وجود تحديثات للواجهة:

```bash
npm install
npm run build
```

---

## 15. النسخ الاحتياطي

قبل أي تحديث يجب أخذ نسخة احتياطية من:

```text
قاعدة البيانات
ملف .env
مجلد storage
ملفات المشروع الحالية
```

ويمكن أخذ نسخة من داخل النظام إذا كانت صلاحية النسخ الاحتياطي مفعلة.

---

## 16. إعدادات الأمان

يجب الالتزام بالتالي:

```text
APP_DEBUG=false
عدم رفع ملف .env إلى GitHub
توجيه الدومين إلى public فقط
تغيير كلمات المرور الافتراضية
استخدام SSL
عدم إعطاء العميل صلاحية وصول للكود
تفعيل النسخ الاحتياطي الدوري
مراجعة صلاحيات المستخدمين
```

---

## 17. أوامر مفيدة

عرض المسارات:

```bash
php artisan route:list
```

تنظيف الكاش:

```bash
php artisan optimize:clear
```

إعادة تحميل صلاحيات Spatie:

```bash
php artisan permission:cache-reset
```

تشغيل السيدرز فقط:

```bash
php artisan db:seed
```

إعادة بناء قاعدة البيانات من الصفر، يستخدم في بيئة الاختبار فقط:

```bash
php artisan migrate:fresh --seed
```

تحذير: الأمر السابق يحذف كل البيانات.

---

## 18. ملاحظات تشغيلية

هذه النسخة مصممة لتكون على استضافة تحت إدارة مقدم الخدمة.

العميل يستخدم النظام من خلال الرابط فقط، ولا يتم تسليم ملفات المشروع أو قاعدة البيانات إلا حسب الاتفاق.

الدعم الفني والتحديثات والنسخ الاحتياطي تكون من مسؤولية مقدم الخدمة.

---

## 19. الدعم الفني

لأي مشكلة أثناء التركيب أو التشغيل، يجب إرسال المعلومات التالية للدعم الفني:

```text
رابط النظام
صورة الخطأ
وقت حدوث الخطأ
اسم المستخدم المتأثر
الخطوة التي حدث فيها الخطأ
ملف laravel.log إن وجد
```

ملف السجل يوجد غالبًا في:

```text
storage/logs/laravel.log
```

---
