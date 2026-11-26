# AI visibility module (llms.txt / AI crawlers)

Folder: `theme/inc/ai-visibility/` — loaded from `functions.php`:

```php
require_once get_template_directory() . '/inc/ai-visibility/ai-visibility.php';
```

Dashboard: top-level admin menu **«هوش مصنوعی و llms.txt»** (`admin.php?page=psb-ai-visibility`, capability `manage_options`), tabs وضعیت / تنظیمات / فعالیت خزنده‌ها.

## Endpoints

| URL | Content-Type | Notes |
| --- | --- | --- |
| `/llms.txt` | `text/plain; charset=utf-8` | [llmstxt.org](https://llmstxt.org) index: H1, `>` summary, about + contact, H2 sections (محصولات، خدمات، تاییدیه‌ها، تایپ تست‌ها، گواهینامه‌ها، نمونه کارها، مقالات، صفحات اصلی) and `## Optional` (llms-full, sitemap, categories). Each line `- [Title](url): description`. |
| `/llms-full.txt` | `text/plain; charset=utf-8` | Full content of every exposed item as Markdown (HTML → MD: headings, lists, tables, links, images with alt), per-item header, size cap (default 1500 KB). Pages built with ACF fall back to their ACF text fields. |
| `/llms/<post-id>.md` | `text/markdown; charset=utf-8` | One item; `Link: rel=canonical` to the HTML page. 404 for items that are not exposed. |
| `/ai.txt` | `text/plain; charset=utf-8` | Spawning-style summary derived from the training-bot toggles. |
| `/robots.txt` | (core) | `robots_txt` filter at priority 9999 appends a `# BEGIN PSB AI … # END PSB AI` block. |

All responses send `Cache-Control: public, max-age=3600, stale-while-revalidate=86400`, `ETag`, `Last-Modified`, answer `If-None-Match` / `If-Modified-Since` with 304, support `HEAD`, and send no `X-Robots-Tag`.

Routing: no rewrite rules (nothing to flush). Apache sends unknown paths to `index.php`; `parse_request` (priority 1) compares `$wp->request` to the paths and streams the document before the main query. A **physical** file with the same name in the web root wins — the dashboard warns about that. If Rank Math's own "LLMS Txt" module is ever enabled, this module still answers first while its llms.txt endpoint is on; disable one of them.

Description per item: Rank Math `rank_math_description` (variables resolved via `RankMath\Helper::replace_vars` when available) → excerpt → trimmed content → (pages) ACF text.

Exposed items: published, no password, not Rank Math `noindex`, not in the excluded-ID list, product not catalog-hidden. Articles: newest N (default 20), sticky first. Main pages: IDs from the settings (default `179,1312,1299,1296,1382`) plus the front page.

## robots.txt

A bot with its own `User-agent` group ignores `User-agent: *`. So allowed AI bots get one shared group that **repeats the `*` group's Allow/Disallow lines** (they keep exactly the same restrictions as everyone else, including a site-wide `Disallow: /` if the site is ever closed), and blocked bots get `Disallow: /`. Rank Math / WooCommerce rules and the `Sitemap:` line are left untouched. Works the same for `blog_public` 0 or 1 (with 0 the dashboard shows a warning, because the pages themselves are `noindex`).

Bots: GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-SearchBot, Claude-User, anthropic-ai, PerplexityBot, Perplexity-User, Google-Extended, Applebot-Extended, CCBot, Bytespider, meta-externalagent, cohere-ai, Amazonbot, DuckAssistBot, YouBot, MistralAI-User. Default: all allowed. Googlebot / Bingbot are not touched.

## Cache and invalidation

- `psb_ai_state` (non-autoloaded): revision counter, last invalidation time and reason.
- `psb_ai_doc_llms`, `psb_ai_doc_llms_full` (non-autoloaded): last built document + the revision it was built for, generated time, size, item count, ETag.
- Invalidation only bumps the revision (once per request, at `shutdown`) and schedules one deduplicated `psb_ai_warm_cache` event 60 s later. A stale document is rebuilt by one request (option lock) while parallel requests keep getting the stale copy — crawlers never wait on a cold build except the very first time.
- Triggers: `transition_post_status` (to/from publish), `save_post` (published), `deleted_post` / `trashed_post` / `untrashed_post`, `added|updated|deleted_post_meta` for `rank_math_*` keys only (not view counters), `created_term` / `edited_term` / `delete_term` (category, product_cat), `acf/save_post` (options page and exposed posts), changes to blogname / blogdescription / page_on_front / sticky_posts / module settings. Only post types of enabled sections count.
- «بازسازی فوری» in the dashboard rebuilds everything synchronously.

## AI crawler activity log

`parse_request` priority 0: one case-insensitive regex on the User-Agent. Only a match reads `psb_ai_botlog` (non-autoloaded, last 30 days, per bot per day + total, last seen, last path) and writes it at most once per minute per bot. Logged-in users, admin and cron are skipped. With a persistent object cache, hits inside the minute are accumulated in the cache and added on the next write; without it the numbers are a per-minute sample (the dashboard says so).

## Self-check

«بررسی» fetches `/llms.txt`, `/llms-full.txt` (HEAD), `/robots.txt` and `/ai.txt` from the server itself (5 s timeout) and shows status, Content-Type, size and time. A failed loopback clears the theme's outbound circuit-breaker entry for the site's own host (`inc/stability.php`), so a blocked loopback cannot disable WP-Cron spawning for 6 hours.

## Files

- `ai-visibility.php` — entry, constants, requires
- `settings.php` — defaults, bot catalogue, sections, sanitizer
- `markdown.php` — HTML → Markdown
- `generator.php` — collection, builders, cache
- `endpoints.php` — routing and HTTP headers
- `robots.php` — robots.txt block
- `invalidation.php` — hooks and warm-up
- `bot-log.php` — activity log
- `admin.php` — dashboard (admin only)
