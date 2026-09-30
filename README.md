# HanbellShop

A multi-vendor fashion marketplace for **indigenous Nigerian brands and independent
creators** — quality pieces at fair, affordable prices. Built on Laravel 13,
Livewire 4 and Tailwind CSS 4.

> **This is a working demo, not a live shop.** The brands, products, prices and
> stock are invented. The photography is real Unsplash work, hotlinked with the
> attribution its licence requires. The storefront says so in the footer.

---

## What is here

| Area | State |
|---|---|
| Storefront (home, shop, product, brand pages, policies) | Built |
| Cart, wishlist, guest→account merge | Built |
| Checkout + Paystack / Flutterwave / Stripe + offline rails | Built (needs your API keys) |
| Auth: verification, EOTP and TOTP two-factor, recovery codes | Built |
| Customer account area | Built |
| Admin panel (28 screens) with charts | Built |
| Vendor panel (catalogue, stock, fulfilment) | Built |
| Advertising engine: ranking, pacing, viewability tracking, reports | Built |
| i18n: English, Hausa, Igbo, Yoruba, French, Arabic | Built |
| SEO + LLM (JSON-LD, sitemap, robots.txt, llms.txt, admin editor) | Built |
| Reviews, coupons, newsletter | Built (moderation + apply only) |
| PDF documents: invoice, receipt, packing slip, credit note, vendor statement, terms | Built |

---

## Requirements

- PHP **8.3+** with `pdo_sqlite`, `gd`, `intl`, `zip`, `mbstring`
- Composer 2
- Node 20+ / npm

MySQL 8 works too — set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`.

---

## Setup

```bash
composer install
npm install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

SQLite is the default and needs no server. The database file is created by the
first migration.

### Demo accounts

Seeded by `DatabaseSeeder`, with the password `password`:

| Role | Email | Notes |
|---|---|---|
| Administrator | `admin@hanbellshop.demo` | Lands in `/admin`, has no cart |
| Customer | `customer@hanbellshop.demo` | Has two saved addresses |
| Customer | `secured@hanbellshop.demo` | Two-factor (email code) already on |

**Change or remove these before any public deployment**, and set
`HANBELL_DEMO_DATA=false`.

### What the seeder creates

10 fictional Nigerian brands · 4 departments · 14 categories · ~72 products ·
~676 variants · ~750 inventory rows · 170 attributed Unsplash images ·
15 ad placements · 7 live ad campaigns · 10 CMS pages (about, terms, privacy,
returns, shipping, cookies, acceptable use, size guide, FAQ, sell-with-us).

---

## Running things

**Read this before running anything.** This machine injects a complete foreign
application's configuration into the process environment:

```
APP_NAME=Templatr          CACHE_STORE=file        DB_CONNECTION=mysql
APP_URL=localhost:8000     SESSION_DRIVER=file     DB_DATABASE=templatr
MAIL_MAILER=smtp           QUEUE_CONNECTION=database
```

Laravel's Dotenv **never overrides a variable that is already present in the
environment**, and `phpunit.xml`'s `<env>` entries are subject to the same rule.
So the injected values beat both this project's `.env` and its test
configuration. The symptoms look unrelated to each other:

| Symptom | Cause |
|---|---|
| `migrate` tries to reach MySQL database `templatr` | injected `DB_*` |
| `artisan about` reports "Templatr" | injected `APP_NAME` |
| Tests use the **file** cache, not the array cache `phpunit.xml` asks for | injected `CACHE_STORE` |
| Recurring `Failed to open stream: Permission denied` in `storage/framework/cache` | a side effect of the above |
| Mail goes to a foreign SMTP host | injected `MAIL_*` |

`art.ps1` clears all of them, so `.env` and `phpunit.xml` are authoritative:

```powershell
. .\art.ps1
art migrate --seed      # or: art serve
tst                     # php artisan test
npmrun build
```

Dropping the wrapper and running `php artisan test` directly is what produces the
intermittent cache failures — they are not flakiness in the suite.

### Useful commands

```bash
php artisan ads:aggregate-stats --days=30   # roll ad events into daily reports
php artisan test                            # 53 feature + unit tests
npm run build                               # compile production assets
npm run dev                                 # Vite dev server with HMR
```

---

## Styles and the asset pipeline (read this if a page looks unstyled)

Tailwind v4 compiles from `resources/css/app.css` — the theme tokens, DM Sans
`@font-face` rules and the `hb-*` component classes all live there. There is no
`tailwind.config.js`; configuration is CSS-first via `@theme`.

Laravel serves styles in one of two modes, decided by whether `public/hot`
exists:

| `public/hot` | Mode | Assets come from |
|---|---|---|
| absent | **production build** | `public/build/` (from `npm run build`) |
| present | **Vite dev server** | whatever URL is written in that file |

**A stale `public/hot` is the single most common cause of an unstyled page.** If
it points at a dev server that is not running — or at an address the browser
cannot reach — the stylesheet 404s and the site renders as bare HTML with no
error anywhere obvious. In dev mode Vite also serves CSS *as a JavaScript
module*, so the failure is silent rather than a visible broken-page error.

Two defences are in place:

```bash
# 1. If you are not actively running `npm run dev`, delete the marker:
rm public/hot            # Windows: Remove-Item public/hot

# 2. If you are, make sure the dev server is actually reachable:
npm run dev              # then load the URL it prints
```

`vite.config.js` pins the dev host and the advertised origin to
`VITE_DEV_HOST` (default `127.0.0.1`) so the two cannot disagree. This matters on
Windows, where Node resolves `localhost` to IPv6 `::1` first — which previously
produced `http://[::1]:5173` in `public/hot` and broke any browser reaching the
site over IPv4. Override if you need to expose the dev server on your LAN:

```bash
VITE_DEV_HOST=0.0.0.0 npm run dev
```

Fonts are copied into `public/fonts/` and referenced as `/fonts/dm-sans-*.woff2`,
so the URL is stable in both modes. During `npm run build` Vite prints
`/fonts/dm-sans-*.woff2 ... didn't resolve at build time` — **this warning is
expected and harmless**: the files are served from `public/` rather than through
the bundler. If fonts 404, check that `public/fonts/` exists.

---

## PDF documents

HanbellShop is a trading name of **Bellah Options**, and every financial and
legal document names that entity — an invoice has to identify who is actually
charging, or it is of little use to a customer's accountant.

Six documents are generated on demand:

| Document | Route | Available when |
|---|---|---|
| Tax invoice | `documents.orders.{order}.invoice` | Order is **paid** |
| Receipt (A5) | `.../receipt` | Order is **paid** |
| Packing slip | `.../packing-slip` | As soon as the order exists |
| Credit note | `.../credit-note` | Something has been **refunded** |
| Vendor statement | `.../vendor/{vendorOrder}/statement` | Owning vendor or admin |
| Terms of Service | `documents.terms` | Public |

Every link takes `?download=1` to force a download instead of opening in a tab.
Links appear on the order page, the order-confirmation page and the admin order
screen.

### Design decisions worth knowing

**Invoice only once paid.** An invoice is a demand for payment; issuing one for an
unpaid order invites a payment we would then have to refund.

**The packing slip carries no prices.** It travels inside the parcel and is often
handed to a courier or left with a neighbour.

**Numbers are derived, not counted.** An invoice for order `HB-2026-000042` is
always `INV-HB-2026-000042`. Re-downloading a six-month-old invoice produces the
identical document rather than one that depends on how many invoices happened to
be issued before it.

**A vendor sees only their own slice.** The vendor statement is scoped to a
`VendorOrder` and resolved from the order's own sub-orders, so a client-supplied
id cannot reach another brand's figures — and a vendor never sees the customer's
whole basket.

**Nigerian names render correctly.** DejaVu Sans covers the Naira sign (U+20A6)
and combining diacritics, so `Ọ̀rẹ́ Studio` prints intact. This was verified by
rendering and reading the PDFs back as text; the tests assert it, because a
missing glyph degrades to a silent tofu box with no error. **If you change the
PDF font, re-run those tests.**

**The terms document is built from the CMS page.** The clauses come from
`/pages/terms-of-service`, so there is one source of truth; the document adds
what a web page does not carry — a preamble naming the operating entity, a
contents list, governing law, a complaints procedure and a document-control
block with a version derived from the page's last edit.

### RC number and TIN

`HANBELL_OPERATOR_RC`, `HANBELL_OPERATOR_TIN` and the address fields are
**intentionally empty**. A Nigerian invoice is expected to carry them, and
inventing them would produce documents that look authoritative and are wrong.
Any line left blank is omitted from the PDF rather than printed as an empty
label. Fill them in via `.env` or **Admin → Settings → company** before issuing a
real invoice.

### Rendering

`dompdf/dompdf` is used directly rather than `barryvdh/laravel-dompdf`, because
the Laravel wrapper caps at Laravel 11. `App\Services\Pdf\PdfRenderer` is the one
place that touches the engine, so swapping it later means changing one class.

Remote images are disabled — a document must never hang or leak a request to a
third-party host. The brand mark is embedded as a data URI: the primary wordmark
is an SVG and dompdf cannot rasterise it (verified: zero image XObjects
embedded), so the generated square PNG icon is used instead.

---

Deeper notes live next to the code. The decisions worth knowing up front:

**URL tokens, not ids or UUIDs.** Public URLs carry an HMAC-signed, expiring
token that binds the model type, the record's uuid and a revocable
`token_version`. A token minted for a vendor cannot be replayed against a
product route, and bumping one column revokes every link for one record. See
`App\Support\Tokens\UrlToken`.

**Money is integer minor units everywhere.** Nothing multiplies a float.
`App\Support\Money` is the only place formatting happens, and
`Money::allocate()` distributes a discount without losing or inventing kobo.

**A payment is never trusted from the browser or a webhook payload.**
`PaymentService::verifyAndComplete()` re-verifies against the provider's API and
checks the settled amount and currency before an order is marked paid. It is
idempotent, so the callback and the webhook can race harmlessly.

**Stock is reserved at checkout, deducted on payment.** A failed or abandoned
checkout releases its hold instead of consuming stock.

**The ad server paces delivery and bills on viewability.** An impression is
recorded only once a creative has been half visible for a second. Ranking blends
the bid with pacing, targeting relevance, historical CTR and creative fatigue;
the weights live in `config/hanbell.php`. Ad destinations are validated against
an allow-list on save *and* on click, which closes the open-redirect hole an
unvalidated destination would otherwise open.

**Administrators do not shop.** An admin account has no cart, wishlist or
checkout — enforced by middleware and re-checked in the two Livewire actions an
admin could still reach from a public page.

---

## Tests

`php artisan test` — 70 tests, 246 assertions.

- `tests/Unit/Support/UrlTokenTest.php` — signing, tampering, type confusion,
  expiry, key rotation
- `tests/Unit/Support/MoneyTest.php` — formatting, allocation, conversion
- `tests/Feature/StorefrontRoutesTest.php` — every storefront, account, admin and
  vendor screen; authorisation boundaries; security headers; SEO output; ad
  redirect safety; webhook signature rejection; translation completeness
- `tests/Feature/PdfDocumentsTest.php` — every document renders; the Naira sign
  and Nigerian diacritics survive; the packing slip leaks no prices; documents
  carry no escaped markup; availability by order state; ownership checks; vendor
  statement scoping; derived document numbers

The PDF tests **read the generated documents back as text** rather than only
asserting a status code. A 200 with a valid body can still be a document that
prints every Nigerian brand name as tofu boxes, or one that renders its own HTML
tags as visible text because a template emitted its slot with `{{ }}` instead of
`{!! !!}`. Both happened during development; neither would have been caught by a
status assertion.

The feature suite requests all 28 admin screens, because a missing Blade file
fails at runtime rather than at compile time — an unvisited screen can sit broken
indefinitely otherwise.

---

## Before going live

1. `HANBELL_DEMO_DATA=false`, and delete the seeded accounts.
2. Set real `PAYSTACK_*` / `FLUTTERWAVE_*` / `STRIPE_*` keys and webhook secrets.
3. Point `MAIL_*` at a real transport (`MAIL_MAILER=log` by default).
4. Switch `.env` to MySQL, or keep SQLite and let Laravel's `database` queue
   handle mail.
5. Have a qualified adviser review the seeded policy pages. They are a sensible
   starting point, not legal advice, and Nigerian consumer-protection (FCCPA)
   and data-protection (NDPA) duties depend on your specific business.
6. Run `php artisan config:cache route:cache view:cache` and serve over HTTPS.
