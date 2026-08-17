# PSB-CO Theme (Pars Switch Board Co.)

**PSB-CO** is a high-performance, custom WordPress and WooCommerce corporate B2B theme engineered specifically for **High & Medium Voltage Electrical Panel Manufacturing (تابلوسازی برق فشار ضعیف، متوسط، کشویی و پست‌های پاساژ)**. Built on top of a modern tech stack, this theme emphasizes speed, modularity, technical compliance with international standards (IEC / ISO), and an exceptional industrial B2B user experience.

---

## 🚀 Tech Stack & Core Technologies

* **WordPress & WooCommerce:** Core CMS engine powering custom post types, taxonomy structures, and e-commerce components.
* **Tailwind CSS (v4):** Utility-first styling framework customized for responsive B2B industrial layouts.
* **Alpine.js:** Lightweight reactive JavaScript framework powering live search modals, filters, and dynamic components without heavy runtime dependencies.
* **Vite & Node.js:** Modern asset bundle compiler (`vite.config.js`) for Tailwind and JavaScript optimization on the fly.
* **Swiper.js & AOS:** Responsive product/portfolio touch sliders and scroll animation effects.

---

## 🏢 Custom Post Types & Industrial Features

The theme features a specialized architecture to highlight corporate credentials, technical laboratory validations, and engineering projects:

### 1. `type-test` (تایپ‌تست‌ها و استانداردهای تجهیزات برق)
* **Purpose:** Laboratory test reports and technical validations issued by accredited testing institutes (e.g. Niroo Research Institute, EPIL, KEMA, CESI) in accordance with IEC standards (IEC 61439-1/2, IEC 62271-200, IEC 60529).
* **ACF Custom Fields:**
  * `issuing_body`: Testing laboratory / accredited institute name.
  * `standard_number`: Standard reference or test report number (e.g., IEC 61439-2 / TR-2024-884).
  * `tested_equipment`: Equipment model / electrical panel type (e.g., MV Withdrawable Switchgear 36kV, MCC 400V).
  * `test_parameters`: Short circuit withstand current ($I_{cw}$), IP rating, internal arc parameters.
  * `validity_date`: Test execution date or validity year.
  * `type_test_file`: High-resolution PDF document or certificate image file.
* **Templates & Components:**
  * Reusable slider & grid component: `template-parts/type-test/recently-type-tests.php`
  * Archive Template: `archive-type-test.php`
  * Single Post Template with PDF Viewer iframe: `single-type-test.php`

### 2. `certificates` (گواهی‌نامه‌های کیفیت ISO)
* **Purpose:** Corporate quality management certificates (ISO 9001, ISO 14001, ISO 45001).
* **ACF Custom Fields:** `issuing_body`, `standard_number`, `validity_date`, `certificate_file`.
* **Templates:** `archive-certificates.php`, `single-certificates.php`, `template-parts/certificates/recently-certificates.php`.

### 3. `confirmation` (تأییدیه‌ها و رضایت‌نامه‌ها)
* **Purpose:** Official approvals and vendor list registrations from Tavanir, regional electricity distribution companies, MAPNA, and petrochemical complexes.
* **ACF Custom Fields:** `issuing_organization`, `letter_number`, `approval_scope`, `letter_file`.
* **Templates:** `archive-confirmation.php`, `single-confirmation.php`, `template-parts/confirmation/recently-confirmations.php`.

### 4. `portfolio` (نمونه‌کارها و پروژه‌ها)
* **Purpose:** Turnkey electrical substation projects, industrial installations, and switchboard manufacturing records.
* **Taxonomies:** `portfolio_categories`, `portfolio_tag`, `voltage_level`, `industry_sector`.

### 5. `services` (خدمات مهندسی تابلوسازی)
* **Purpose:** Design, assembly, testing, commissioning, and retrofit engineering services.
* **Taxonomies:** `services_categories`, `services_tag`.

---

## 🛠 Advanced Theme Modules & Engineering Integrations

### Live Multi-CPT Search Engine (`theme/inc/ajax-search.php` & `search-modal.php`)
* Real-time AJAX live search modal built with Alpine.js.
* Searches across `services`, `portfolio`, `certificates`, `confirmation`, `type-test`, and standard blog posts simultaneously.
* Live count indicators per CPT with dynamic tab filtering.

### Navigation Active Menu Fix (`psb_fix_cpt_menu_active_classes`)
* Located in `theme/functions.php`. Automatically resolves WordPress nav menu active state highlighting for CPT archives and singular pages while preventing false active highlights on the blog page.

### ACF Local JSON Synchronization (`acf-json/`)
* Field definitions and post type structures are committed to version control in JSON format:
  * `post_type_type_test.json`, `group_type_test_fields.json`, `taxonomy_type_test_category.json`
  * `group_certificates_fields.json`, `group_confirmation_fields.json`
  * `group_about_page_fields.json`, `group_portfolio_fields.json`, `group_services_fields.json`

### E-Commerce & WooCommerce Integrations (`theme/inc/product/`)
* **AJAX Cart Modal:** Alpine.js cart drawer updated via WooCommerce cart fragments (`cart-fragments.php`).
* **Stock Sorting:** Moves out-of-stock items to the end of shop loops automatically (`out-stock-product.php`).
* **Product Comparison:** Native product comparison engine (`compare-function.php`).
* **SEO & Return Policy Schema:** Structured JSON-LD metadata injection for search engine rich results (`return-policy-schema.php`).

### Localized Editorial Utilities (`theme/inc/post-type-functions/`)
* **Persian Date Formatting:** Jalali calendar converter (`persian-date.php`).
* **Reading Time & Post Views:** Estimated reading time calculator (`reading-time.php`) and view counters (`post-view.php`).
* **Table of Contents (TOC):** Auto-generated TOC for technical documentation articles (`toc.php`).
* **Performance Cleanup:** Disables default emoji scripts, block styles bloat, and optimizes asset preloading (`optimize.php`).

---

## 📁 Directory Structure

```
PSB-CO/
├── acf-json/                     # Auto-synchronized ACF field groups & CPT definitions
├── javascript/                   # Source JavaScript files (script.js, block-editor.js)
├── tailwind/                     # Tailwind CSS source files (tailwind-theme.css)
├── theme/                        # WordPress Theme Root
│   ├── archive-type-test.php     # Type-Test Archive page
│   ├── single-type-test.php      # Type-Test Single page with PDF viewer
│   ├── archive-certificates.php  # ISO Certificates Archive
│   ├── single-certificates.php   # ISO Certificates Single page
│   ├── archive-confirmation.php  # Confirmations Archive
│   ├── single-confirmation.php  # Confirmations Single page
│   ├── archive-portfolio.php     # Portfolio Archive
│   ├── single-portfolio.php      # Portfolio Single page
│   ├── front-page.php            # Main Industrial Front Page
│   ├── homepage.php             # Alternative Home Page Template
│   ├── about-us.php              # Corporate About Us page
│   ├── contact-us.php            # Contact page
│   ├── functions.php             # Core bootstrap file
│   ├── inc/                      # Theme function modules
│   │   ├── custom-post-type.php  # Native CPT & Taxonomy registrations
│   │   ├── ajax-search.php       # Live AJAX search query handler
│   │   ├── search-route.php      # Custom REST search routes
│   │   ├── optimize.php          # WordPress cleanup & speed tweaks
│   │   ├── product/              # WooCommerce custom features
│   │   └── post-type-functions/  # TOC, Jalali dates, view counters
│   └── template-parts/           # Modular reusable template parts
│       ├── type-test/            # recently-type-tests.php (Slider & Grid layout)
│       ├── certificates/         # recently-certificates.php
│       ├── confirmation/         # recently-confirmations.php
│       ├── homepage/             # Hero, AboutUs, Blog sliders
│       └── layout/               # Header, Footer, Search Modal
├── package.json                  # Node.js dependencies & build scripts
├── vite.config.js                # Vite build system config
└── README.md                     # Theme documentation
```

---

## 💻 Development & Build Workflow

To work on the PSB-CO theme locally:

1. **Navigate to the theme root directory:**
   ```bash
   cd wp-content/themes/PSB-CO
   ```

2. **Install Node dependencies:**
   ```bash
   npm install
   ```

3. **Start development watcher (compiles Tailwind CSS and JavaScript assets on change):**
   ```bash
   npm run dev
   ```

4. **Build production bundle (minifies styles and scripts for production deployment):**
   ```bash
   npm run build
   ```

---
*Documentation maintained for PSB-CO High & Medium Voltage Electrical Switchboard Engineering.*
