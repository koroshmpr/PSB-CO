# psb.co.ir — Pars Switch Board website

Corporate B2B site for **شرکت تولیدی و صنعتی پارس سوئیچ بورد** (LV/MV switchboards, since 1976).
WordPress + WooCommerce (catalogue only) with a custom theme. This repository is the `wp-content` folder.

| | |
|---|---|
| Theme source | `themes/PSB-CO/` (Tailwind v4, esbuild, Alpine.js) |
| Deployable theme | `themes/PSB-CO/theme/` |
| Deploy | push to `main` → GitHub Actions → FTP → smoke test (`.github/workflows/deploy.yml`) |
| Docs | `themes/PSB-CO/{DESIGN,RFQ,AI-VISIBILITY,PWA-PANEL,PERFORMANCE}.md` |
| Architecture map | `CLAUDE.md` |

## Build
```bash
cd themes/PSB-CO
npm ci
npm run production   # theme/style.css + theme/js/*.min.js
```

## Deploy secrets (GitHub → Settings → Secrets and variables → Actions)
`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`. Optional: `FTP_SERVER_DIR` (default `wp-content/themes/PSB-CO/theme/`; the FTP root is already public_html), `FTP_PORT`, `FTP_PROTOCOL` (`ftps` | `ftp`).

## Emergency switches (wp-config.php)
- `define('PSB_LOGIN_GUARD_DISABLE', true);` — restore `/wp-login.php` if locked out of `/secret-door/`.
- `define('PSB_ANTISPAM_DISABLE', true);` — turn off the form/comment spam filter.
