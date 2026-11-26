# PSB-CO theme

Custom WordPress/WooCommerce theme for psb.co.ir. The architecture map, module list, conventions and commands live in the repository root `CLAUDE.md`; feature docs sit next to this file:

- `DESIGN.md` — design system (colours, type, components, icons, Latin-code handling)
- `RFQ.md` — quote/order wizard and Bale/Telegram/WhatsApp/email notifications
- `AI-VISIBILITY.md` — llms.txt, AI crawler rules, dashboard
- `PWA-PANEL.md` — installable PWA and the `/panel/` owner panel
- `PERFORMANCE.md` — Perfmatters replacement, hidden login, DB cleanup (Persian guide for the owner)

Build: `npm ci && npm run production` (outputs into `theme/`). Only `theme/` is deployed.
