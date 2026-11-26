# PSB-CO design system (v2: red and graphite)

This file follows `SCRATCH/design-spec.md`. That spec is authoritative; the mocks live in `SCRATCH/mock/`.

The look is a light-dominant engineering brand. Corners are crisp (4/6/8px), rules are hairlines, photography is real, and proof is presented like data. Red does one job: **one primary action per viewport plus small accents**. The logo stays `#FF0000`, while the UI red is `#D60F1A`.

Where things live:
- Tokens: `tailwind/tailwind-theme.css`. The token names are kept from v1, so the old `primary`, `cta`, `dark` and `slate-*` classes re-skin automatically.
- Components: `tailwind/custom/components/*.css`, all inside `@layer components`, so a utility on the same element always wins.
- Utilities: `tailwind/custom/utilities.css`.
- PHP helpers: `theme/inc/design-helpers.php` and `theme/inc/nav.php`.
- Build: run `npm run production` and commit `theme/style.css` and `theme/js/*`.

> **Rules (from spec §10)**
> - **Headings**
>   - One `<h1>` per page. Inner pages get it from `global/page-hero.php`.
>   - Card titles, headings and stat values are `text-foreground`, never red.
> - **Colour**
>   - At most one red button per viewport. Card CTAs use `btn-secondary`.
>   - White text on red. Red text on light backgrounds is `text-primary-700`; on dark it is `text-primary-400`.
>   - No navy, sky blue or amber. No gradient blobs, no tech-grid on light sections, no offset photo frames, no glows.
>   - No `slate-400` text on light backgrounds; use `slate-600` (`slate-500` is allowed on pure white at 14px or larger).
> - **Typography**
>   - No `font-black`. No letter-spacing on Persian.
>   - Nothing smaller than 12px.
> - **Shapes and stats**
>   - No pills, except filter chips (`.chip`).
>   - Facts go in plain text or ruled rows. No boxed stat cards, no "+" signs, no count-ups.
> - **Numbers and codes**
>   - Persian digits everywhere except inside Latin codes.
>   - Wrap codes with `.lat`, or print text through `psb_lat( $text )`. It escapes the text and wraps tokens like `IEC 61439`, `IP54`, `SYStem6` and `24 kV`.
>   - Phone numbers use Persian digits inside `dir="ltr"`.
> - **Data and images**
>   - Facts only from `SCRATCH/company-facts.md`, plus the owner-confirmed `PSB_WARRANTY_MONTHS` (18) and `PSB_ISO_9001`.
>   - Real photos only, never AI images, stock or Unsplash. With no photo, use `.media-placeholder` (steel-50 box with the PSB mark).
>   - Fixed ratios: product 4:3, project 3:2, article 16:9, document 3:4.
> - **Links and forms**
>   - Internal links end with `/`. No author links.
>   - Every quote CTA points to `psb_quote_url()` (`/request-quote/`); product CTAs use `psb_quote_url( $slug )`. The wizard is the only form, so never embed a form in the CTA band.
>   - Do not add JSON-LD in templates; `inc/seo.php` owns schema.
> - **Motion**: `data-reveal` goes on section heads and card groups only, never on the hero, the LCP image or stats.

---

## 1. Tokens

| Group | Tokens / utilities |
|---|---|
| Surfaces | `bg-surface` #FFF, `bg-background` #F4F5F7 (alternate sections and page heads), `bg-slate-100` (chips, hover rows); `text-foreground` #14171C; `border-line` #D9DEE5, `border-line-strong` #BAC2CD |
| Red (UI) | `primary-50 … 950`. `primary` = `primary-600` #D60F1A (CTA, accents, focus). `primary-700` #B30C16 is hover, red text on light and the CTA band. `primary-400` is red text on dark. `brand-logo` #FF0000 is for the logo only |
| CTA | `cta` = the same red. **Text on it is white** |
| Steel | `slate-50 … 950` are overridden with steel greys. Use `slate-600` for muted text, `slate-700` for strong secondary text, and `slate-400` only for icons and decoration |
| Graphite | `dark` #111418, `dark-800`, `dark-700`, `dark-600` (hero overlay, factory band, footer) |
| Status | `success` #15803D, `warning` #B45309, `danger` #B91C1C |
| Radii | `rounded-control` 4px, `rounded-card` 6px, `rounded-panel` 8px |
| Shadows | None at rest. `shadow-lift` for hover; `--shadow-header` and `--shadow-panel` |
| Type | `text-display` (home H1), `text-h1` (inner H1), `text-h2`, `text-h3`, `text-lead`, `text-eyebrow`. Weights: 400 body, 500 nav, 600 buttons and labels, 700 headings |
| Fonts | `font-sans` = Peyda (FaNum). Use `lat` (utility) or `--font-latin` for Latin codes |
| Layout | `container` (76rem plus fluid gutter), `py-section` (64 to 112px), `py-section-sm`. `nav:` / `max-nav:` is the 1100px breakpoint where the desktop nav switches to the drawer. `--header-h` is 84px on desktop and 60px below `nav` |

## 2. Utilities
- `lat`: Latin face, LTR, tabular numerals. Use it for standards and codes.
- `container`
- `rail`: scroll-snap row.
- `cv-auto`: content-visibility.
- `ltr-num`
- `bg-tech-grid`: for dark backgrounds only.

## 3. Components

### Section head: `.section-head` (+ `--split`, `--center`, `--invert`), `.eyebrow`, `.section-title`, `.section-lead`, `.link-arrow`
- Eyebrow, then H2, then an optional lead (38rem). One `.link-arrow` sits at the end, aligned to the baseline. There is no button.
- Use `.on-dark` on dark sections; it re-colours the eyebrow, title, lead and link.

PHP:
`get_template_part( 'template-parts/global/section-head', null, [ 'eyebrow'=>'', 'title'=>'', 'lead'=>'', 'id'=>'', 'invert'=>false, 'link'=>[ 'url'=>'', 'label'=>'همه …' ] ] );`

```html
<div class="section-head section-head--split">
  <div><span class="eyebrow">محصولات</span><h2 class="section-title">سه خط تولید، یک استاندارد ساخت</h2><p class="section-lead">…</p></div>
  <a class="link-arrow" href="/shop/">همه محصولات <svg class="btn-arrow">…</svg></a>
</div>
```

### Buttons: `.btn` plus one variant
- Variants:
  - `btn-primary` (= `btn-cta`): the red button, white text, at most one per viewport.
  - `btn-secondary` (= `btn-outline`)
  - `btn-ghost`
  - `btn-on-dark` (= `btn-light`)
  - `btn-white`: used in the red band.
  - `btn-dark`
- Sizes: `btn-sm` 40px, default 48px, `btn-lg` 56px. Also `btn-block` and `btn-icon`.
- Icon buttons in the header and drawer use `.icon-btn` (44px).
- For a forward arrow, add `psb_icon('arrow','btn-arrow')` after the label. It points left in RTL.

### Badges and chips
- `.badge`: a square 3px chip.
- `badge-standard`: Latin face, steel; for codes such as `IEC 61439` and `IP54`.
- Status badges: `badge-success`, `badge-warning`, `badge-cta`.
- `badge-dark`: for dark backgrounds.
- `.chip`: filter pill. The selected state (`aria-pressed`/`aria-current`/`.is-active`) is graphite, not red.
- `.kicker` and `.card-eyebrow`: a 6px red square plus a 12px label.

### Card: `.card` (+ `card-hover`, `card--flat`, `card--dark`)
- Parts: `card-media` (set the ratio with `style="--card-ratio:4/3"`; images scale to 1.03 on hover), `card-body`, `card-eyebrow`, `card-title`, `card-text` (2 lines), `card-meta`, `card-footer`.
- `.card-link` on the title makes the whole card clickable. Give inner links `relative z-10`.
- Placeholder: `card-media media-placeholder`.
- PHP: `template-parts/cards/card.php` takes `post`, `layout` (stack|side|overlay), `eyebrow`, `excerpt`, `badges`, `specs`, `meta`, `cta`, `quote` (secondary «استعلام قیمت»), `ratio`, `size`, `eager`.

### Spec list and spec table
- `.spec-list`: a `<dl>` of `<div><dt/><dd/></div>` hairline rows. The label is at the start, the 600-weight value at the end. Use it inside cards.
- `.spec-table`: a 2px foreground top rule, 1px row lines, `th` 40% in slate-600, `td` in 600-weight foreground. No zebra stripes and no outer box. Codes go in `<span class="lat">`.

```html
<table class="spec-table"><caption>مشخصات فنی</caption><tbody>
  <tr><th scope="row">استاندارد ساخت</th><td><span class="badge badge-standard">IEC 61439</span></td></tr>
  <tr><th scope="row">ولتاژ نامی</th><td class="lat">24 kV</td></tr>
</tbody></table>
```

### Register (document rows for approvals, type tests, letters)
- Markup: `.register-title`, then `ol.register` › `li`.
- Each row has three parts:
  1. `.register-logo`: a 56px tile, greyscale. For labs, put the wordmark in `<span class="lat">EPIL</span>`.
  2. `a.register-name.card-link` and `.register-meta` (number · date · scope; hide what is missing).
  3. `a.register-doc`: «مشاهده سند».
- On mobile the logo shrinks to 44px and the doc link wraps under the title.

### Stats: ruled row. `dl.stats` › `div.stat` › `dt.stat-value` (with `<small>` unit) + `dd.stat-label`
- There are no boxes. On desktop, 4 columns with vertical hairlines; on mobile, 2 columns with hairlines in both directions.
- Add `.stats--invert` on dark backgrounds.

### Timeline: `ol.timeline` › `li.timeline-item` (+ `.is-current`, which gets a red node) › `.timeline-year`, `.timeline-title`, `.timeline-text`

### Logo wall (greyscale issuers): `.logo-wall` › `.logo-wall-item`

### CTA band: `global/cta-band.php`
- Full-bleed `primary-700` background, with `btn-white` «ارسال درخواست استعلام» and `btn-on-dark` showing the phone number.
- `footer.php` prints it once per page. It is skipped on contact, quote, login, cart, checkout and account pages.
- To disable it: `add_filter( 'psb_show_cta_band', '__return_false' );`

### Breadcrumb: `global/breadcrumb.php`
- `.breadcrumb`: 13px slate-600, «/» separators.
- `.breadcrumb--invert` for photo headers.
- Markup only; schema comes from `inc/seo.php`.

### Page header (inner pages): `global/page-hero.php`
- `variant`:
  - `light` (default): full-bleed #F4F5F7, a hairline, the breadcrumb, eyebrow, H1 (`text-h1`) and a lead up to 44rem.
  - `split`: 6/6 with a real photo; pass `image`.
  - `photo`: about page only; a dark photo header.
- The old `dark` variant renders as `light`.
- Extra args: `facts` (array of strings) renders a ruled meta row; `actions` takes HTML.

### Accordion / FAQ: `global/faq-list.php`
- Native `<details class="accordion-item">` inside `.accordion`, with hairline rows, a 17px/600 summary and a + / − icon.
- Args: `items`, `title`, `open_first`, `disable_schema`.

### Article prose: `.article-prose`
- 17px with 1.95 line-height; H2 gets a 3px red start border; tables get a 2px top rule; links are primary-700.

### Media
- `.media-frame img`: 6px radius with a hairline border; no offset frame.
- `.media-box`: aspect box (`--ratio`).
- `.media-placeholder`: steel-50 box with the PSB mark.
- `.skeleton` and `.skeleton-text`: shimmer placeholders.

### Form controls
- `.label`
- `.input`: 48px tall (52 on mobile), 16px text, a 4px radius, foreground border on focus and a 2px red outline.

## 4. Global layout (owned by the design agent)

### Header: `layout/header-content.php`
- Sticky and white with a hairline bottom border; `.is-scrolled` adds a shadow. Height is 84px from 1100px up and 60px below.
- Logo:
  - The full lockup (`psb-logo.svg`) shows at 68px from `nav` (1100px) up; the mark (`psb-mark.svg`) shows at 28px below. Brand rules require the full logo to be at least 120px wide.
  - The vector brand kit is in `theme/images/brand/`, and `global/logo.php` takes `variant` = `full`, `white`, `mark` or `mark-white`.
- Actions: search (`.icon-btn`), the phone block (from 1280px), and `btn-primary` «استعلام قیمت» (from 1100px). Below 1100px: phone icon and menu icon.

### Mega menu: `layout/header/mega-menu.php`, `mega-panel.php`, data from `inc/nav.php`
- Content comes from Appearance → Menus (location `menu-1`, see `SCRATCH/menu-plan.md`). `psb_nav_tree()` enriches each item with its photo, a one-line description and counts. A menu item's own Description overrides the automatic one.
- Panel types are detected automatically:
  - `products`: 3 product columns plus a quote card.
  - `credentials`: approvals, type tests, certificates and letters (empty groups are hidden), plus a featured document.
  - `about`: links, a factory photo and contact facts.
  - Every other item has no dropdown on desktop.
- Behaviour follows the disclosure pattern: the chevron button has `aria-expanded` and `aria-controls`. Panels open on hover after 150ms and close after a 300ms grace period. Click or Enter toggles a panel; Esc closes it and returns focus. Panels open in 240ms and close in 180ms.

### Drawer (below 1100px): `layout/header/menu-modal.php`
- Slides in from the right, 88vw wide up to 400px, over a 50% graphite backdrop.
- Contents, top to bottom:
  - the search entry
  - an accordion built from the same menu tree (`<details name>`, so only one group is open at a time; 56px rows); the current page gets a 3px red start bar
  - a sticky footer with the primary «استعلام قیمت» button, then phone and WhatsApp.
- Esc closes it, focus is trapped inside, and the safe-area insets are respected.

### Mobile tab bar: `layout/sticky-social.php`
- Shown below 768px with 5 tabs: خانه · محصولات · **استعلام** (red rectangle in the centre) · اعتبارنامه‌ها · تماس.
- Hidden on product, quote, cart, checkout, account and login pages; `body.has-tabbar` pads the page.
- To turn it off: `add_filter('psb_show_tabbar','__return_false')`.
- Anything else fixed to the bottom should use `bottom: var(--tabbar-offset)`.

### Footer: `layout/footer-content.php`
- Graphite background, grid 4 + 2 + 2 + 4:
  1. White logo, description, and red-square facts: ISO 9001, 18-month warranty, founded ۱۳۵۵.
  2. محصولات.
  3. اعتبارنامه‌ها.
  4. Addresses, phones (`dir=ltr`), email and «مسیریابی».

### Search: `layout/header/search-modal.php`, engine in `inc/search-route.php`
- Opening: Ctrl/Cmd+K, «/», the header icon or the drawer search field.
- REST endpoint: `/wp-json/psb/v1/search?q=`. Persian-aware matching, with results grouped by type.
- Keyboard: arrow keys move through results, Enter opens one, Esc closes. ARIA is the combobox/listbox pattern.
- The results page (`search.php`) has type tabs and pagination.

### Floating button
`.fab` (back to top) appears after 2 screens of scrolling and sits above the tab bar.

### Motion
- `data-reveal`: once, 12px rise.
- View Transitions: the header, tab bar and logo stay fixed while `#main` fades and slides 8px in the RTL reading direction; the direction reverses on back navigation.
- On click, a 2px red top progress bar appears and buttons scale to 0.98.
- Speculation rules: prerender on moderate eagerness, plus an eager prefetch of the main destinations.
- `prefers-reduced-motion` turns everything off.

## 5. Home sections (`template-parts/homepage/sections.php`)

Each section hides itself when it is empty. In order:

| Section | Background | Content |
|---|---|---|
| `hero` | dark photo | H1 and CTAs, then a ruled trust row |
| `issuers` | white | strip of approval issuers |
| `products` | alt | product cards |
| `approvals` | white | proof registers |
| `certificates` | — | hidden while empty |
| `capabilities` | dark | factory mosaic and facts |
| `projects` | white | project cards |
| `letters` | — | hidden until real letters exist |
| `aboutus` | alt | photo and timeline |
| `blog-slider` | white | article cards (AI images blocked) |
| `faq` | alt | FAQ accordion |

## 6. PHP helpers (`inc/design-helpers.php`, `inc/nav.php`)

- **Fields and brand**: `psb_field( $name, $default, $post_id = 'option' )`, `psb_brand_name()`, `psb_brand_fix( $text )`
- **Contact data**: `psb_phones( $footer_only )`, `psb_primary_phone()`, `psb_format_phone()`, `psb_emails()`, `psb_addresses()`, `psb_socials()`
- **URLs**: `psb_quote_url( $slug = '' )`, `psb_contact_url()`, `psb_shop_url()`, `psb_archive_url( $cpt, $fallback )`, `psb_iri( $url )` (shortens Persian URLs)
- **Images and icons**: `psb_image( $id_or_acf, $size, $attr )`, `psb_icon( $name, $class )` (uses an SVG sprite), `psb_home_hero_image()`
- **Text, numbers and dates**: `psb_lat( $text )`, `psb_fa_num()`, `psb_jalali_year()`, `psb_years_active()`
- **Constants**: `PSB_FOUNDED_JALALI`, `PSB_WARRANTY_MONTHS`, `PSB_ISO_9001`
- **Navigation**: `psb_nav_tree( $location )`, `psb_nav_count( $cpt )`, `psb_show_tabbar()`
- **WooCommerce (null-safe)**: `psb_wc_cart()`, `psb_cart_count()`
