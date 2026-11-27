# RFQ: استعلام قیمت و ثبت سفارش تابلو برق

This is the quote-request system of psb.co.ir. It has two parts:

- A multi-step wizard on `/request-quote/`, plus a quick form for product pages and the home page.
- Storage in WordPress as private `psb_rfq` posts.

Every new request is then sent to the sales team by Bale, Telegram, WhatsApp, a webhook or email. A confirmation SMS to the customer is optional.

## Install

Add this line to `theme/functions.php`, after `design-helpers.php` and `stability.php` are loaded:

```php
require get_template_directory() . '/inc/rfq/rfq.php';
```

The code still works if `design-helpers.php`, `stability.php`, WooCommerce or ACF are missing.

## Files

| File | Role |
|---|---|
| `theme/inc/rfq/rfq.php` | Entry point: constants and requires |
| `inc/rfq/helpers.php` | Digit normalisation, Iranian mobile, Jalali date, HMAC, rate limit, circuit-breaker reset |
| `inc/rfq/options.php` | Defaults, editable lists, field definitions, visibility, channel config (secrets) |
| `inc/rfq/cpt.php` | CPT `psb_rfq`, capabilities, tracking code `PSB-1405-0012`, data accessors |
| `inc/rfq/files.php` | Private upload storage, nonce'd download, cleanup cron |
| `inc/rfq/rest.php` | REST `psb/v1/rfq/session`, `/rfq/upload` and `/rfq`, anti-spam, validation |
| `inc/rfq/notify.php` | Message builder and channels (bale, telegram, whatsapp, webhook, email, sms), background dispatch |
| `inc/rfq/admin.php` | List columns, filters, search, status views, bulk status, detail boxes, notes, resend, CSV, dashboard widget, menu badge |
| `inc/rfq/settings.php` | «تنظیمات» and «اتصال بات‌ها» pages, test buttons, Chat ID finder |
| `inc/rfq/frontend.php` | Assets, JS config, shortcodes, field renderer, product → type mapping, template for slug `request-quote` |
| `theme/page-request-quote.php` | Page template «استعلام قیمت» |
| `theme/template-parts/rfq/wizard.php` | Wizard markup |
| `theme/template-parts/rfq/quick-form.php` | Quick form markup |
| `theme/js/rfq.js` | Readable JS source, no dependencies |
| `theme/js/rfq.min.js` | Minified copy that is actually loaded (about 23 KB, about 8 KB gzip) |
| `theme/css/rfq.css` | Plain CSS that uses the theme tokens with fallbacks |

After editing `rfq.js`, regenerate the minified file:

```bash
cd themes/PSB-CO && npx esbuild theme/js/rfq.js --minify --target=es2017 --legal-comments=inline --outfile=theme/js/rfq.min.js
```

If `rfq.min.js` is missing, or `SCRIPT_DEBUG` is on, the readable `rfq.js` is loaded instead.

## Usage

- **Page:** a page with slug `request-quote` uses `page-request-quote.php` automatically, even if «قالب» is left on default. Choosing the template «استعلام قیمت» by hand also works. The draft page has id **1826**.
- **Deep links:** `/request-quote/?product=<slug|id>` preselects the board type of that product. `/request-quote/?type=lv_mcc` preselects a type key. The theme helper `psb_quote_url( $slug )` builds these links.
- **Deep links start a new request.** Arriving through a deep link (a product's «استعلام قیمت») starts a fresh request with only that board: items, project data, files and step are reset; the contact details are kept. If the old draft (`localStorage` `psb_rfq_v1`) had any work, it is parked under `psb_rfq_prev_v1` and a notice offers «بازگرداندن پیش‌نویس قبلی» / «حذف آن». A draft that already holds only that same board is kept as it is (the visitor is continuing it). Without a deep link the draft is restored as before («شروع از نو» clears it).
- **Shortcodes:**
  - `[psb_rfq]` renders the full wizard.
  - `[psb_rfq_quick product="slug-or-id" title="…" text="…"]` renders the quick form. On a single product page, the current product is used when `product` is not given.
- **Template part:** `get_template_part( 'template-parts/rfq/quick-form', null, array( 'product' => get_the_ID(), 'tag' => 'h3' ) );`
- **Product → type mapping:** an optional post meta `_psb_rfq_type` (a type key) wins. Otherwise, keywords in the title and categories decide: فشار ضعیف → `lv_mdb`, فشار متوسط → `mv_fixed`, فرمان/کنترل → `control`, کنتور → `meter`, کمپکت/RMU → `rmu`, ایزوله → `hospital`. Filter: `psb_rfq_type_for_product`.

## How it works

1. The page HTML contains no nonce, so it is safe for the page cache. On load (idle time), the JS fetches `GET /wp-json/psb/v1/rfq/session`. That call returns an HMAC-signed token (and the math question, if enabled) and is never cached.
2. Files are uploaded one by one with a progress bar (`POST /rfq/upload`). They are stored in `wp-content/uploads/psb-rfq/<24-hex bucket>/<random>.<ext>`.
   - The folder gets `.htaccess` (deny all), `index.php` and `web.config`.
   - Allowed extensions come from settings; executable types are always blocked, and magic bytes are checked.
   - Staff download files through `admin-post.php?action=psb_rfq_file` (nonce plus capability check).
   - A daily cron deletes unclaimed uploads after 2 days. Files are deleted when their request is permanently deleted.
3. On submit (`POST /rfq`), the server runs these checks:
   - honeypot `website`
   - time-trap (minimum seconds since the token was issued)
   - token max age of 12 h, single use (a second submit with the same token returns the same code)
   - per-IP rate limit
   - optional math challenge
   - full validation that mirrors the JS
4. The request is saved first. Then notifications are sent **after** the HTTP response has been flushed (`fastcgi_finish_request` / `litespeed_finish_request`). If neither exists, a single WP-Cron event is spawned. A post-meta lock prevents double sending.
   - Every HTTP call has a timeout of 8 s at most.
   - Each channel's result is logged in `_psb_rfq_log` and shown in the box «وضعیت ارسال اعلان‌ها», with «ارسال مجدد» per channel or for all channels.

**nginx:** `.htaccess` is ignored there, so add this to the server block:

```nginx
location ^~ /wp-content/uploads/psb-rfq/ { deny all; }
```

File names are random either way, so the files cannot be listed or guessed.

**Circuit breaker** (`inc/stability.php`): a failed host is skipped for 6 hours. The «ارسال پیام تست» button and «ارسال مجدد» clear the breaker first. A failed `sendDocument` (for example, a large file timing out) also clears it, so text messages are not blocked.

## Data and capabilities

- **CPT:** `psb_rfq` (not public, `show_ui`). The menu is «استعلام‌ها و سفارش‌ها», with submenus «همه درخواست‌ها», «تنظیمات» and «اتصال بات‌ها».
- **Statuses** (meta `_psb_rfq_status`): `new` جدید, `review` در حال بررسی, `quoted` پیش‌فاکتور ارسال شد, `won` برنده, `lost` از دست رفته.
- **Meta:** `_psb_rfq_data` holds the full structured request. There are also flat keys for the list and filters: code, mobile, company, city, name, one `_psb_rfq_type` row per type, mode, files, log, notes, ip, ua.
- **Search:** the admin search box finds a request by tracking code, mobile (Persian digits and spaces are fine), name, company, city or project name.
- **Viewing requests** needs capability `edit_psb_rfqs`. It is granted once to administrator, editor and shop_manager (filter `psb_rfq_roles`). **Settings** need `manage_options`.
- **Options:**
  - `psb_rfq_settings` (autoload) holds lists, field visibility, limits and texts. It contains no secrets.
  - `psb_rfq_channels` (**autoload = no**) holds channel config including tokens. Secrets are masked in the UI: a blank field keeps the stored value, and «حذف» clears it.
  - Secrets can also be set in `wp-config.php`, which overrides the UI: `PSB_RFQ_BALE_TOKEN`, `PSB_RFQ_TELEGRAM_TOKEN`, `PSB_RFQ_TELEGRAM_RELAY_SECRET`, `PSB_RFQ_WHATSAPP_TOKEN`, `PSB_RFQ_WHATSAPP_RELAY_SECRET`, `PSB_RFQ_WEBHOOK_SECRET`, `PSB_RFQ_SMS_API_KEY`.
- **Hooks:**
  - `psb_rfq_created( $post_id, $data )`
  - `psb_rfq_notified( $post_id, $log )`
  - `psb_rfq_status_changed( $post_id, $new, $old )`
  - filter `psb_rfq_client_ip` (use it behind a trusted proxy or CDN)

---

## راهنمای راه‌اندازی (برای مدیر سایت)

### ۱. پس از بارگذاری قالب

1. در پیشخوان وردپرس منوی «استعلام‌ها و سفارش‌ها» ظاهر می‌شود.
2. برگه پیش‌نویس «استعلام قیمت تابلو برق» (شناسه ۱۸۲۶) را باز کنید.
3. مطمئن شوید «نامک» (slug) آن `request-quote` است. می‌توانید در «ویژگی‌های برگه» قالب «استعلام قیمت» را هم انتخاب کنید (اختیاری است، چون برای این نامک خودکار اعمال می‌شود).
4. برگه را منتشر کنید.
5. در «استعلام‌ها و سفارش‌ها ← تنظیمات» این موارد را بررسی کنید: تلفن و واتساپ فروش، متن زمان پاسخ‌گویی، فهرست انواع تابلو و گزینه‌ها، و فیلدهای الزامی یا پنهان.
6. اگر حجم مجاز فایل سرور کمتر از ۲۰ مگابایت باشد، صفحه تنظیمات هشدار می‌دهد. در این صورت `upload_max_filesize` و `post_max_size` را در کنترل‌پنل هاست بیشتر کنید.

### ۲. بات بله (کانال اصلی و پیشنهادی؛ از سرور ایران در دسترس است)

1. در پیام‌رسان بله، حساب **@botfather** را باز کنید و دستور `/newbot` را بفرستید.
2. یک نام و یک نام کاربری برای ربات انتخاب کنید (نام کاربری باید به `bot` ختم شود).
3. **توکن** ربات را کپی کنید. شکل آن `123456789:AbC...` است.
4. یک گروه برای تیم فروش بسازید و ربات را عضو آن کنید. بهتر است ربات را ادمین گروه کنید. اگر اعلان را فقط برای خودتان می‌خواهید، به ربات پیام «سلام» بدهید.
5. در گروه یک پیام بفرستید.
6. در سایت به «استعلام‌ها و سفارش‌ها ← اتصال بات‌ها ← بات بله» بروید و این کارها را انجام دهید:
   - توکن را وارد کنید.
   - «فعال» را بزنید.
   - دکمه **«ذخیره و پیدا کردن Chat ID»** را بزنید. شناسه گروه (یا شما) در پیام بالای صفحه نمایش داده می‌شود.
   - شناسه را در کادر «Chat IDها» بگذارید. برای چند گیرنده، هر شناسه را در یک خط بنویسید.
   - **«ذخیره و ارسال پیام تست»** را بزنید.
7. اگر خطا دیدید، متن دقیق خطا نمایش داده می‌شود. نمونه: `chat not found` یعنی ربات عضو گروه نیست، یا شناسه اشتباه است.
8. «ارسال فایل‌ها» را روشن بگذارید تا نقشه‌ها هم در گروه ارسال شوند. فایل‌های بزرگ‌تر از سقف تعیین‌شده (پیش‌فرض ۱۰ مگابایت) فقط در پنل قابل دانلودند.
9. اگر برچسب‌هایی مثل `<b>` به‌صورت متن دیده شد، «قالب متن» را روی Markdown یا «متن ساده» بگذارید.

### ۳. بات تلگرام (از طریق رله)

سرور ایران معمولاً به `api.telegram.org` دسترسی ندارد. برای همین یک رله رایگان روی Cloudflare Workers بسازید:

1. در Cloudflare به **Workers & Pages ← Create ← Worker** بروید و کد زیر را جایگزین کنید.
2. در **Settings ← Variables** یک Secret به نام `RELAY_SECRET` با یک رمز طولانی و تصادفی بسازید.
3. Worker را Deploy کنید. اگر دامنه `workers.dev` در ایران در دسترس نبود، در Settings ← Domains & Routes یک **دامنه یا زیردامنه سفارشی** وصل کنید، مثلاً `tg.example.com`.
4. ربات تلگرام را با **@BotFather** بسازید (`/newbot`) و توکن را بردارید. ربات را به گروه فروش اضافه کنید و یک پیام در گروه بفرستید.
5. در «اتصال بات‌ها ← بات تلگرام» این مقادیر را وارد کنید:
   - توکن
   - «آدرس API / رله» = آدرس Worker، مثلاً `https://tg.example.com`
   - «نام هدر رمز رله» = `X-Relay-Secret`
   - «رمز مشترک رله» = همان RELAY_SECRET
6. سپس «پیدا کردن Chat ID» و بعد «ارسال پیام تست» را بزنید.

**Cloudflare Worker (کپی و جای‌گذاری):**

```js
// PSB relay: forwards /bot<token>/<method> to the Telegram Bot API.
// Set the secret RELAY_SECRET in Worker settings. Optional var TARGET (default https://api.telegram.org),
// e.g. TARGET=https://graph.facebook.com to relay the WhatsApp Cloud API through a second worker.
export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const secret = request.headers.get('X-Relay-Secret') || '';
    if (!env.RELAY_SECRET || secret !== env.RELAY_SECRET) {
      return new Response('forbidden', { status: 403 });
    }
    const target = (env.TARGET || 'https://api.telegram.org').replace(/\/+$/, '');
    const isTelegram = target.includes('telegram.org');
    const allowed = isTelegram
      ? /^\/bot\d+:[A-Za-z0-9_-]+\/[A-Za-z]+$/.test(url.pathname)
      : /^\/v\d+(\.\d+)?\/\d+\/messages$/.test(url.pathname);
    if (!allowed || !['POST', 'GET'].includes(request.method)) {
      return new Response('not found', { status: 404 });
    }
    const headers = new Headers();
    for (const h of ['content-type', 'authorization']) {
      const v = request.headers.get(h);
      if (v) headers.set(h, v);
    }
    const res = await fetch(target + url.pathname + url.search, {
      method: request.method,
      headers,
      body: request.method === 'GET' ? undefined : request.body,
    });
    return new Response(res.body, {
      status: res.status,
      headers: { 'content-type': res.headers.get('content-type') || 'application/json', 'cache-control': 'no-store' },
    });
  },
};
```

### ۴. واتساپ

**الف) WhatsApp Cloud API (رسمی):**

1. در developers.facebook.com یک اپ از نوع Business بسازید و محصول WhatsApp را اضافه کنید.
2. **Phone number ID** و یک **Access token** دائمی (System User) بگیرید.
3. شماره‌های گیرنده تیم فروش را در کادر «شماره‌های گیرنده» بنویسید.
4. پیام متنی آزاد فقط تا ۲۴ ساعت پس از آخرین پیام گیرنده به شماره کسب‌وکار تحویل می‌شود. برای اطمینان، در WhatsApp Manager یک **Message Template** (دسته Utility) با ۴ متغیر بسازید و نامش را در «نام Template» وارد کنید. متغیرها به این ترتیب پر می‌شوند:
   - `{{1}}` کد پیگیری
   - `{{2}}` نام
   - `{{3}}` موبایل
   - `{{4}}` اقلام
5. اگر سرور به `graph.facebook.com` دسترسی ندارد، یک Worker دوم با همان کد و متغیر `TARGET=https://graph.facebook.com` بسازید. آدرس آن را در «آدرس API / رله» و رمز را در «رمز مشترک رله» وارد کنید.

**ب) درگاه‌های دیگر (واتساپ غیررسمی، پیامک، n8n، CRM):** از «وب‌هوک عمومی» استفاده کنید.

- برای هر درخواست، یک POST با بدنه JSON ارسال می‌شود. بدنه همه فیلدها را دارد، به‌اضافه `text` (متن ساده) و `text_wa` (متن با قالب واتساپ).
- اگر «رمز امضا» را پر کنید، هدر `X-PSB-Signature: sha256=<HMAC بدنه>` هم ارسال می‌شود.
- در «هدرهای اضافه» هر هدر را در یک خط بنویسید. نمونه: `Authorization: Bearer xxx`.

### ۵. ایمیل

- گیرندگان را هر کدام در یک خط بنویسید.
- ایمیل با تابع `wp_mail` ارسال می‌شود. اگر ایمیل نمی‌رسد، یک افزونه SMTP نصب کنید. خطای دقیق در دکمه تست نمایش داده می‌شود.
- با گزینه «پیوست فایل‌ها»، مدارک تا مجموع ۱۵ مگابایت پیوست می‌شوند.

### ۶. پیامک تأیید به مشتری (اختیاری)

این بخش با هر سرویس پیامکی که API از نوع HTTP دارد کار می‌کند.

متغیرهای قابل استفاده: `{name}`، `{code}`، `{mobile}`، `{mobile_intl}`، `{message}`، `{response_time}`، `{api_key}`.

- در آدرس، مقادیر به‌صورت URL-encode جایگزین می‌شوند.
- در بدنه JSON، مقادیر JSON-escape می‌شوند. متغیر را داخل `""` بگذارید.

نمونه GET، برای سرویس‌هایی که کلید را در مسیر آدرس می‌گیرند:

```
روش: GET
آدرس: https://api.example.ir/v1/{api_key}/sms/send.json?receptor={mobile}&message={message}
```

نمونه POST با JSON:

```
روش: POST — نوع بدنه: JSON
آدرس: https://api.example.ir/send
بدنه: {"to":"{mobile}","text":"{message}"}
هدرها: Authorization: Bearer {api_key}
```

«شماره موبایل تست» را پر کنید و «ذخیره و ارسال پیام تست» را بزنید. پاسخ سرویس در پیام نتیجه نمایش داده می‌شود.

### ۷. کار روزانه با درخواست‌ها

- منوی «استعلام‌ها و سفارش‌ها» تعداد درخواست‌های «جدید» را نشان می‌دهد. ویجت پیشخوان آمار وضعیت‌ها و آخرین درخواست‌ها را دارد.
- **صفحه جزئیات هر درخواست:**
  - مشخصات کامل، لینک تماس و واتساپ مشتری، و دانلود فایل‌ها
  - تغییر وضعیت
  - یادداشت داخلی (همراه با تاریخچه تغییر وضعیت)
  - نتیجه ارسال هر کانال، با «ارسال مجدد»
- **فهرست درخواست‌ها:**
  - فیلتر بر اساس وضعیت و نوع تابلو
  - جستجو با کد پیگیری، موبایل، نام یا شرکت
  - تغییر وضعیت گروهی
  - دکمه «خروجی CSV (اکسل)»، که فیلترهای فعلی را رعایت می‌کند
- **ضد اسپم** بدون سرویس خارجی کار می‌کند: فیلد تله، حداقل زمان پر کردن فرم و سقف تعداد ثبت از هر IP. اگر اسپم زیاد شد، «سؤال امنیتی ساده» را در تنظیمات روشن کنید.
