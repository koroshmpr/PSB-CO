# PSB-CO — guide for Claude

The site is psb.co.ir, for Pars Switch Board («شرکت تولیدی و صنعتی پارس سوئیچ بورد»), an LV/MV switchboard manufacturer founded in ۱۳۵۵. It runs WordPress with WooCommerce as a catalogue only: no prices, no cart.
Reply to the owner in **Persian**. Code, paths and identifiers stay in English.

## Fast orientation (read these, not the whole tree)
- `themes/PSB-CO/theme/functions.php` is the module loader. Search it for `require` to see what is loaded.
- Feature docs are in `themes/PSB-CO/`. Each one is the source of truth for its feature:
  - `DESIGN.md`: tokens, components, icons, Latin-code rules.
  - `RFQ.md`: the quote wizard, notification channels and REST.
  - `AI-VISIBILITY.md`: llms.txt and the AI crawler rules.
  - `PWA-PANEL.md`: the PWA and the `/panel/` owner panel.
  - `PERFORMANCE.md`: the Perfmatters replacement, the login guard and the DB cleanup.
- Local working tools, notes and data are in `.dev/` (gitignored; see `.dev/README.md`).
- **Never read** built or vendor files: `theme/style.css`, `theme/js/*.min.js`, `node_modules/`, `plugins/`, `uploads/`. Search with `grep -rn` inside `themes/PSB-CO/{theme,tailwind,javascript}` instead.

## Layout
```
wp-content/                 ← git repo root (github koroshmpr/PSB-CO, branch main)
  CLAUDE.md  README.md  .github/workflows/deploy.yml
  themes/PSB-CO/            ← theme SOURCE (package.json, tailwind/, javascript/)
    tailwind/custom/components/*.css   (primitives, layout, inner, content, motion, placeholders…)
    javascript/script.js (+ modules/) → bundled to theme/js/script.min.js
    theme/                  ← the deployable WordPress theme (live slug "PSB-CO/theme")
      inc/                  modules (below)
      template-parts/{global,homepage,blog,product,rfq,services,letters,project-records,…}
      woocommerce/          Woo overrides (archive-product.php, single-product parts)
  mu-plugins/zz-local-*     LOCAL ONLY (live media proxy, drafts in catalogue) — gitignored, never deploy
  .dev/                     local tools/notes/data (gitignored, holds the MCP key)
```

## Theme modules (`theme/inc/`)
| Module | Purpose |
|---|---|
| `stability.php` | HTTP circuit breaker (non-essential hosts), 8s timeout, heartbeat, revisions, DONOTCACHEPAGE, real-cron throttle |
| `optimize.php`, `performance.php` | Asset trims; `performance.php` replaces Perfmatters (jQuery Migrate, Woo attribution, Google Fonts guard, weekly DB cleanup). It only runs when Perfmatters is inactive |
| `login-guard.php` | Hidden login `/secret-door/`, 404s `wp-login`/`wp-admin` for guests. Off while Perfmatters owns the login. Kill switch: `PSB_LOGIN_GUARD_DISABLE` |
| `antispam.php` | Gravity Forms and comments: honeypot, signed time token, content rules. Spam goes to the spam folders. Kill switch: `PSB_ANTISPAM_DISABLE` |
| `otp-login-form.php` | Customer OTP login (`/login/`). `psb_otp_verify()` has attempt caps; admins can't use OTP |
| `seo.php` | JSON-LD on top of Rank Math (Organization, Product with no offers, FAQ, Breadcrumb) |
| `ai-visibility/` | `/llms.txt`, `/llms-full.txt`, `/llms/<id>.md`, `/ai.txt`, robots AI rules, dashboard, bot log |
| `rfq/` | Quote wizard (`page-request-quote.php`), CPT `psb_rfq`, REST `psb/v1/rfq*`, Bale/Telegram/WhatsApp/email/SMS, tracking code `PSB-<jalali year>-NNNN` |
| `pwa/` | `/manifest.webmanifest`, `/sw.js`, `/pwa-offline/` |
| `owner-panel/` | `/panel/` mobile admin with 180-day sessions |
| `project-records/` | CPT `project_record` («سوابق اجرایی»), CSV importer that drops restricted projects, `/project-references/` |
| `placeholders.php` | Type-matched illustrations: `psb_best_image()`, `psb_single_image()`, `psb_image_blocked()` |
| `nav.php`, `walker.php`, `template-parts/global/*` | Header, mega panels, drawer, floating mobile tab bar, call sheet |
| `post-type-functions/` | Series taxonomy, TOC, letters CPT `customer_letter`, inner helpers (`psb_lat*`, catalogue statuses) |
| `product/` | Woo tweaks, shop actions, `psb_product_quote_url()` |

**CPTs:** `type-test`, `certificates`, `confirmation`, `portfolio`, `services`, `customer_letter`, `project_record`, `psb_rfq`. **Taxonomy:** `series`.

## Commands
```bash
cd themes/PSB-CO && npm run production          # build CSS + JS (required after any tailwind/ or javascript/ edit)
find themes/PSB-CO/theme -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v '^No syntax'   # lint
.dev/tools/render/run.sh /some-path/ name       # server-side render one URL → .dev/tools/render/out/name.{html,err}
python3 .dev/mcp.py <tool> '<json>'             # live site via WebSensePro MCP (187 tools; e.g. wsp_get_posts)
node .dev/qa/pw/<script>.js                     # Playwright QA (playwright-core + system Chrome)
```
Local site: http://localhost:10048 (Local by Flywheel). Use the MySQL socket in `.dev/tools/render/run.sh`, with `mysql -uroot -proot local`. CLI `php` can't load WordPress without that `-d mysqli.default_socket=…` flag.

## Deploy
- **A push to `main` deploys to production.** Workflow: build → PHP 8.1 lint → FTP to `public_html/wp-content/themes/PSB-CO/theme/` → smoke test. Push only with the owner's approval.
- Only `themes/PSB-CO/theme/` is uploaded. Files deleted in git stay on the server (no clean-slate).
- Live host: Talashnet cPanel (CloudLinux), PHP 8.2, real cron on wp-cron every 5 minutes, no CDN.

## Conventions
- **Design:**
  - UI red `#D60F1A` (hover `#B30C16`), graphite `#14171C`, muted `#525C6B`, borders `#D9DEE5`, radii 4/6/8.
  - Icons come from `psb_icon()`: monochrome, 1.5 stroke.
  - Minimal and practical (Digikala-like), never colourful.
- **Text:**
  - Wrap Latin codes (IEC 61439, IP54, kV) in `psb_lat()` / `psb_lat_content()` so they don't get Persian digits.
  - Use `psb_fa_num()` for counts.
- **Tone:** formal corporate Persian. Never «ما» or «خودمان».
- **Dates:** the Persian WooCommerce plugin filters `wp_date()` to Jalali. Use `DateTimeImmutable` + `wp_timezone()` (see `psb_rfq_local_dt()`).
- **Catalogue counts:** use `psb_cat_count()` / `psb_catalogue_statuses()`, which count only published products on live. Don't use raw term counts.
- **Product quote links:** use `psb_product_quote_url()`. `?product=` is a Woo query var, so it is stripped on pages by a `request` filter.
- **New modules:** add them to the loader array in `functions.php`. Guard plugin APIs with `function_exists` / `class_exists`.

## Hard rules (owner)
- **Never publish:**
  - government, defence or security projects (`psb_pr_is_restricted()`);
  - national IDs, registration numbers, personal phone numbers, financial, tax, insurance or bank data.

  Managers' names may be shown.
- **Approval gates:**
  - Articles and draft posts are published only after the owner says «تایید انتشار».
  - Pushing, plugin installs and live deletions need the owner's approval.
- **Secrets:**
  - Bot tokens live only in the WP option `psb_rfq_channels`.
  - The MCP key lives only in `.dev/.secrets.env`.
  - Never put either in git, docs or memory.
- **Facts:** company facts are in the memory file `psb-company-facts`. Don't invent numbers or claims.

## Token-saving habits
- Start from this file and the feature doc. Grep for a function name before opening a file, and read only the line range you need.
- After a change, lint and render the affected template (`.dev/tools/render`). Don't open full pages in context: grep the HTML or `.err` file.
- For visual checks, take a Playwright screenshot of just the element (`locator.screenshot`), not the full page.
- Put long-lived notes in `.dev/notes/`, never in the session scratchpad, which gets wiped.
