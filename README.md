<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11" />
  <img src="https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white" alt="Vue 3" />
  <img src="https://img.shields.io/badge/bKash-Tokenized_API-E2136E?style=for-the-badge" alt="bKash API" />
  <img src="https://img.shields.io/badge/Bilingual-EN_↔_BN-5DCAA5?style=for-the-badge" alt="Bilingual" />
  <img src="https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker" />
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="MIT" />
</p>

<h1 align="center">bKash Wallet</h1>

<p align="center">
  A production-ready, bilingual (English / বাংলা) full-stack digital wallet<br/>
  integrating the <strong>bKash Tokenized Checkout API v1.2.0</strong><br/><br/>
  <strong>Laravel 11</strong> · <strong>Vue 3</strong> · <strong>Redis</strong> · <strong>Gotenberg PDF</strong> · <strong>Docker</strong>
</p>

---

## 📋 Table of Contents

1. [Overview](#-overview)
2. [Features](#-features)
3. [Architecture](#-architecture)
4. [Project Structure](#-project-structure)
5. [Tech Stack](#-tech-stack)
6. [Module Details](#-module-details)
7. [API Reference](#-api-reference)
8. [Bilingual Support](#-bilingual-support)
9. [Validation Rules](#-validation-rules)
10. [Security Design](#-security-design)
11. [Quick Start — Docker](#-quick-start--docker-recommended)
12. [Manual Setup](#-manual-setup)
13. [Environment Variables](#-environment-variables)
14. [Running Tests](#-running-tests)
15. [Production Deployment](#-production-deployment)
16. [Troubleshooting](#-troubleshooting)

---

## 🌐 Overview

**bKash Wallet** is a full-stack web application that lets users:

- Register and authenticate with secure API tokens
- Link their bKash account once via the **Tokenized Checkout Agreement** flow
- Top up their wallet **without OTP re-entry** on every subsequent transaction
- Request **partial or full refunds** on any completed payment
- Browse a **paginated, filterable transaction history** (credits / debits / refunds)
- Download a **bilingual PDF statement** (English or বাংলা) for any date range

The entire UI switches seamlessly between English and Bangla — every label, error message, badge, date format, and validation hint is translated. The language preference is saved to the database and synced across sessions.

---

## ✨ Features

| Feature | Details |
|---|---|
| 🔐 **Auth** | Laravel Sanctum stateless Bearer tokens — register, login, logout |
| 🌍 **Bilingual** | English ↔ বাংলা with persistent user preference |
| 🔗 **Agreement** | One-time bKash agreement → OTP-free future top-ups |
| 💳 **Top-up** | `payment-with-agreement/create` — no OTP required |
| 🛡️ **Double-submit lock** | Redis `SETNX` atomic lock per user (60 s TTL) |
| 💰 **Partial refunds** | Cumulative tracking — refund any portion of any transaction |
| 📄 **PDF statements** | Gotenberg Chromium renderer — dompdf is explicitly excluded |
| ✅ **Full validation** | Client-side composable + server-side Laravel Form Requests |
| 🐳 **Docker ready** | One command spins up the entire stack |
| 🧪 **Tests** | PHPUnit Feature + Unit tests with in-memory SQLite |

---

## 🏗 Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│           Vue 3 SPA  (Vite · Pinia · vue-i18n · Tailwind)       │
│                                                                  │
│  LoginView   RegisterView   DashboardView   TransactionsView     │
│  WalletCard  AgreementCard  TopUpForm                            │
│  TransactionTable  RefundModal  StatsSummary                     │
│  AppNavbar (EN/BN toggle)  AppPagination  ConfirmDialog          │
│                                                                  │
│  Stores: auth · bkash · transactions   (Pinia)                  │
│  Composables: useValidation.js          (11 rule factories)      │
│  i18n: 9 namespaces × 2 locales        (EN + BN)                │
└───────────────────────────┬──────────────────────────────────────┘
                            │  REST / JSON  (Bearer Token)
┌───────────────────────────▼──────────────────────────────────────┐
│                  Laravel 11 API Backend                           │
│                                                                   │
│  Controllers (5)  ·  Middleware: SetLocale                       │
│  Form Requests (4) · API Resources (2)                           │
│  Models (3)  ·  Services (3)  ·  Providers (1)                   │
│  Routes (13 endpoints)  ·  Lang files (EN + BN)                  │
└──────────┬──────────────────┬──────────────────┬─────────────────┘
           │                  │                  │
      ┌────▼────┐       ┌─────▼──────┐    ┌──────▼──────────┐
      │  MySQL  │       │   Redis    │    │   Gotenberg      │
      │  8.0    │       │  7 Alpine  │    │   8 (Chromium)   │
      │  3 tables│      │  token cache│   │   PDF service    │
      └─────────┘       │  SETNX lock│    └──────────────────┘
                        └────────────┘
                                │
                     ┌──────────▼───────────┐
                     │  bKash Tokenized API  │
                     │  v1.2.0-beta sandbox  │
                     │  tokenized.bka.sh     │
                     └──────────────────────┘
```

### Payment flow

```
POST /api/bkash/payment/create  { amount: 500 }
  ├─ validate TopupRequest
  ├─ check active agreement
  ├─ Redis SET bkash:payment_lock:{uid} 1 EX 60 NX  ← atomic lock
  ├─ BkashService::createPaymentWithAgreement()
  └─ returns { bkash_url }  → frontend redirects user

User approves on bKash page
  └─ bKash → GET /api/bkash/payment/callback?status=success&paymentID=...
       ├─ BkashService::executePayment()
       ├─ DB::transaction() + User::lockForUpdate()
       ├─ wallet_balance += amount
       ├─ Transaction::create(type='credit')
       ├─ Redis DEL lock key
       └─ redirect /dashboard?payment=success
```

---

## 📁 Project Structure

```
bkash-wallet/
│
├── backend/                                  Laravel 11 API
│   ├── app/
│   │   ├── Exceptions/
│   │   │   └── Handler.php                  JSON error responses
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php        register · login · logout · me · locale
│   │   │   │   ├── BkashAgreementController.php  create · callback · status
│   │   │   │   ├── BkashPaymentController.php    create · callback (SETNX lock)
│   │   │   │   ├── RefundController.php      partial refund with ownership check
│   │   │   │   └── TransactionController.php paginated list · PDF download
│   │   │   ├── Middleware/
│   │   │   │   └── SetLocale.php             user_pref → Accept-Language → 'en'
│   │   │   ├── Requests/
│   │   │   │   ├── LoginRequest.php
│   │   │   │   ├── RegisterRequest.php       phone regex + unique
│   │   │   │   ├── TopupRequest.php          numeric, min:1, max:25000
│   │   │   │   └── RefundRequest.php         max: refundable amount
│   │   │   └── Resources/
│   │   │       ├── UserResource.php
│   │   │       └── TransactionResource.php   refundable_amount, is_refundable
│   │   ├── Models/
│   │   │   ├── User.php                      wallet_balance, preferred_locale
│   │   │   ├── BkashAgreement.php            AES-256-CBC encrypted agreementId
│   │   │   └── Transaction.php               credit · debit · refund + scopes
│   │   ├── Providers/
│   │   │   └── AppServiceProvider.php        singletons for services
│   │   └── Services/
│   │       ├── BkashService.php              full bKash API client + token cache
│   │       ├── WalletService.php             atomic credit · debit · refund
│   │       └── PdfStatementService.php       Gotenberg HTTP integration
│   ├── bootstrap/
│   │   ├── app.php                           SetLocale middleware registered
│   │   └── providers.php
│   ├── config/
│   │   ├── bkash.php                         bKash-specific configuration
│   │   ├── cors.php                          restricted to FRONTEND_URL
│   │   └── ...                              app, auth, cache, database, etc.
│   ├── database/
│   │   ├── factories/                        User · Transaction · BkashAgreement
│   │   ├── migrations/                       4 migrations
│   │   └── seeders/DatabaseSeeder.php        demo@bkash.test / password
│   ├── lang/
│   │   ├── en/  auth.php · bkash.php · validation.php
│   │   └── bn/  auth.php · bkash.php · validation.php
│   ├── resources/views/pdf/
│   │   └── statement.blade.php               bilingual PDF template
│   ├── routes/
│   │   ├── api.php                           13 endpoints
│   │   ├── web.php
│   │   └── console.php
│   ├── tests/
│   │   ├── Feature/
│   │   │   ├── AuthTest.php                  4 tests
│   │   │   └── TransactionTest.php           3 tests
│   │   ├── Unit/
│   │   │   └── BkashServiceTest.php          3 tests
│   │   └── TestCase.php
│   ├── Dockerfile                            PHP 8.2-cli + Composer
│   ├── phpunit.xml                           SQLite in-memory
│   └── composer.json
│
├── frontend/                                 Vue 3 SPA
│   ├── src/
│   │   ├── api/
│   │   │   └── index.js                      Axios + Bearer interceptor + 401 redirect
│   │   ├── assets/
│   │   │   └── main.css                      Tailwind + btn-primary, card, input classes
│   │   ├── composables/
│   │   │   └── useValidation.js              11 reusable rule factories
│   │   ├── components/
│   │   │   ├── dashboard/
│   │   │   │   ├── WalletCard.vue            gradient balance display
│   │   │   │   ├── AgreementCard.vue         status + create button + locale-aware date
│   │   │   │   └── TopUpForm.vue             presets + validation + i18n errors
│   │   │   ├── transactions/
│   │   │   │   ├── TransactionTable.vue      compact + full mode, locale-aware dates
│   │   │   │   ├── RefundModal.vue           partial refund, max cap, i18n validation
│   │   │   │   ├── StatsSummary.vue          credit / debit / refund totals
│   │   │   │   ├── TypeBadge.vue             i18n badge
│   │   │   │   └── StatusBadge.vue           i18n badge
│   │   │   └── shared/
│   │   │       ├── AppNavbar.vue             EN/BN toggle + balance chip + logout
│   │   │       ├── AppPagination.vue         windowed pages, i18n total label
│   │   │       ├── ConfirmDialog.vue         Teleport modal, i18n labels
│   │   │       └── LoadingSpinner.vue        fullPage prop, i18n default label
│   │   ├── i18n/
│   │   │   └── index.js                      294 lines · 9 namespaces · EN + BN
│   │   ├── router/
│   │   │   └── index.js                      auth guards, guest guards
│   │   ├── store/
│   │   │   ├── index.js
│   │   │   └── modules/
│   │   │       ├── auth.js                   user, token, locale sync
│   │   │       ├── bkash.js                  agreement + payment flows
│   │   │       └── transactions.js           paginate, refund, PDF download
│   │   ├── views/
│   │   │   ├── LoginView.vue                 i18n validation + server error
│   │   │   ├── RegisterView.vue              5 fields + strength bar + i18n
│   │   │   ├── DashboardView.vue             balance + agreement + topup + recent txns
│   │   │   └── TransactionsView.vue          filter tabs + date-range download
│   │   ├── App.vue                           global toast + locale bootstrap
│   │   └── main.js
│   ├── Dockerfile                            Node 20 → Nginx Alpine production
│   ├── nginx.conf                            SPA history mode + gzip + asset caching
│   ├── tailwind.config.js
│   ├── vite.config.js
│   └── package.json
│
├── docker-compose.yml                        MySQL + Redis + Gotenberg + Backend + Frontend
├── .gitignore
└── README.md
```

---

## 🛠 Tech Stack

### Backend

| Package | Version | Purpose |
|---|---|---|
| PHP | ^8.2 | Runtime |
| Laravel | ^11.0 | API framework |
| Laravel Sanctum | ^4.0 | Stateless Bearer token auth |
| Predis | ^2.2 | Redis client — token cache + SETNX locks |
| GuzzleHTTP | ^7.8 | bKash API + Gotenberg HTTP calls |
| MySQL | 8.0 | Primary relational database |
| Redis | 7 Alpine | Cache, session, queue, atomic locks |
| Gotenberg | 8 | Chromium-based PDF generation |
| PHPUnit | ^11.0 | Automated testing |

### Frontend

| Package | Version | Purpose |
|---|---|---|
| Vue | ^3.4 | Reactive UI framework |
| Vite | ^5.2 | Build tool with HMR |
| Pinia | ^2.1 | State management (auth, bkash, transactions) |
| Vue Router | ^4.3 | Client-side routing with auth guards |
| Vue i18n | ^9.13 | EN ↔ BN localization (294 keys) |
| Axios | ^1.7 | HTTP client with Bearer token interceptor |
| Tailwind CSS | ^3.4 | Utility-first CSS framework |

---

## 📦 Module Details

### Module 00 — Identity & Localization

**Authentication** uses Laravel Sanctum stateless Bearer tokens. Users register with name, email, phone (validated as Bangladeshi `01[3-9]XXXXXXXX`), and a strong password. Tokens are revoked on logout.

**Wallet balance** is stored as `decimal(12,2)` on the `users` table. Every mutation runs inside `DB::transaction()` + `lockForUpdate()` to prevent race conditions under concurrent requests.

**SetLocale middleware** resolves the app locale on every API request:
1. `auth()->user()->preferred_locale` (DB column) for authenticated users
2. `Accept-Language` HTTP header
3. Default `'en'`

**Language toggle** in the frontend Navbar switches between EN and BN instantly via `vue-i18n`. The selection is persisted to `localStorage` (immediate) and synced to the DB via `PATCH /api/auth/locale` (durable).

---

### Module 01 — Agreement Binding

```
POST /api/bkash/agreement/create
  → revoke existing active agreements
  → BkashService::createAgreement(payerReference)
  → returns { bkash_url, payment_id }

Frontend redirects user → bKash agreement page

GET /api/bkash/agreement/callback?paymentID=...&status=success
  → BkashService::executeAgreement(paymentID)
  → BkashAgreement::create([
      user_id,
      agreement_id  ← triggers AES-256-CBC encryption mutator,
      payer_reference,
      status = 'active'
    ])
```

The `agreementID` returned by bKash is **never stored in plaintext**. The `BkashAgreement` model uses an Eloquent mutator/accessor pair:
- Write: `Crypt::encryptString($plainId)` → stored as `agreement_id_encrypted`
- Read: `Crypt::decryptString($encrypted)` → returned as `agreement_id`

---

### Module 02 — Balance Injection

```
POST /api/bkash/payment/create  { amount, description }
  → TopupRequest validation (min:1, max:25000, integer)
  → agreement must be active
  → Redis: SET bkash:payment_lock:{user_id} 1 EX 60 NX
      if false → 429 Too Many Requests
  → BkashService::createPaymentWithAgreement(agreementId, amount, invoice, payerRef)
  → return { bkash_url }

GET /api/bkash/payment/callback?status=success&paymentID=...
  → BkashService::executePayment(paymentID)
  → WalletService::credit(user, amount, response)
      DB::transaction() {
        User::lockForUpdate() → balance += amount
        Transaction::create(type='credit', status='completed')
      }
  → Redis DEL bkash:payment_lock:{user_id}
  → redirect to /dashboard?payment=success
```

**Cancel / failure callbacks** release the Redis lock and redirect with appropriate query params which the Dashboard reads to show toast notifications.

---

### Module 03 — Refund Logic

```
POST /api/transactions/{id}/refund  { amount, reason }
  → check transaction.user_id === auth.id  (403 if mismatch)
  → check transaction.isRefundable()
      status='completed', type='credit', refundable_amount > 0
  → RefundRequest: amount numeric, min:1, max:refundable_amount
  → BkashService::refund(trxId, paymentId, amount, reason)
  → WalletService::refund(user, transaction, amount, response)
      DB::transaction() {
        User::lockForUpdate() → balance -= amount
        transaction.refunded_amount += amount
        if refunded_amount >= amount → status = 'refunded'
        Transaction::create(type='refund', original_transaction_id)
      }
```

**Partial refunds** are fully supported. `refunded_amount` tracks the cumulative amount already returned, so the client and server both enforce `max = original.amount − refunded_amount`.

---

### Module 04 — History & Statements

**Transaction list:**
```
GET /api/transactions?type=credit&page=2&per_page=15
→ TransactionResource collection with pagination
→ Includes: refundable_amount, is_refundable, formatted_date
```

**PDF Statement** (Gotenberg — dompdf banned):
```
GET /api/transactions/statement/download?from=2025-01-01&to=2025-05-31&locale=bn
  → validate: both dates required, from ≤ to, range ≤ 365 days
  → fetch transactions in range
  → view('pdf.statement', [user, transactions, from, to, locale])->render()
  → POST Gotenberg /forms/chromium/convert/html  { multipart: index.html }
  → stream PDF bytes as Content-Disposition: attachment
```

The `statement.blade.php` template renders fully bilingual output — all labels, dates, and currency symbols adapt based on the `locale` variable.

---

## 📡 API Reference

All authenticated routes require: `Authorization: Bearer {token}` and `Accept: application/json`

### Auth (public)

| Method | Endpoint | Body | Response |
|---|---|---|---|
| `POST` | `/api/auth/register` | `name, email, phone, password, password_confirmation` | `{ user, token }` |
| `POST` | `/api/auth/login` | `email, password` | `{ user, token }` |

### Auth (protected)

| Method | Endpoint | Body | Response |
|---|---|---|---|
| `POST` | `/api/auth/logout` | — | `{ message }` |
| `GET` | `/api/auth/me` | — | `UserResource` with `active_agreement` |
| `PATCH` | `/api/auth/locale` | `locale: en\|bn` | `{ locale }` |

### bKash Agreement

| Method | Endpoint | Auth | Response |
|---|---|---|---|
| `POST` | `/api/bkash/agreement/create` | ✓ | `{ bkash_url, payment_id }` |
| `GET` | `/api/bkash/agreement/callback` | — | redirect to frontend |
| `GET` | `/api/bkash/agreement/status` | ✓ | `{ has_agreement, created_at }` |

### bKash Payment

| Method | Endpoint | Auth | Response |
|---|---|---|---|
| `POST` | `/api/bkash/payment/create` | ✓ | `{ bkash_url, payment_id }` |
| `GET` | `/api/bkash/payment/callback` | — | redirect to frontend |

### Transactions

| Method | Endpoint | Auth | Query / Body | Response |
|---|---|---|---|---|
| `GET` | `/api/transactions` | ✓ | `type, page, per_page` | Paginated `TransactionResource` |
| `GET` | `/api/transactions/statement/download` | ✓ | `from, to, locale` | PDF binary |
| `POST` | `/api/transactions/{id}/refund` | ✓ | `amount, reason` | `{ message, refunded, bkash_trx_id }` |

### Example: `GET /api/transactions` response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 42,
      "trx_id": "TRX8KJ2XQ1R",
      "payment_id": "PAY9LM3WR4Z",
      "type": "credit",
      "amount": "500.00",
      "charge": "7.50",
      "balance_after": "12450.00",
      "currency": "BDT",
      "status": "completed",
      "description": "Wallet top-up via bKash",
      "refunded_amount": "0.00",
      "refundable_amount": "500.00",
      "is_refundable": true,
      "created_at": "2025-05-15T10:32:00.000Z",
      "formatted_date": "15 May 2025, 10:32 AM"
    }
  ],
  "last_page": 3,
  "per_page": 15,
  "total": 42
}
```

---

## 🌍 Bilingual Support

The entire application is available in **English** and **বাংলা** with zero hardcoded strings in any Vue component.

### Frontend i18n (`src/i18n/index.js`)

| Namespace | Keys | Covers |
|---|---|---|
| `nav` | 3 | Navigation labels |
| `auth` | 13 | Login, register, field labels, hints |
| `validation` | 24 | Every client-side error message |
| `strength` | 4 | Password strength bar labels |
| `dashboard` | 7 | Dashboard cards and headings |
| `topup` | 4 | Top-up form labels |
| `transactions` | 22 | Table headers, refund modal, filters |
| `status` | 5 | Transaction status badges |
| `type` | 3 | Transaction type badges |
| `toast` | 7 | Success / error notifications |
| `confirm` | 4 | Confirm dialog buttons |
| `pagination` | 1 | Pagination total label |
| `loading` | 1 | Default loading text |

**Total: 98 unique keys × 2 locales = 196 translations**

### Backend i18n (`lang/en/` + `lang/bn/`)

| File | Keys | Covers |
|---|---|---|
| `auth.php` | 3 | failed, password, throttle |
| `bkash.php` | 8 | no_agreement, payment_failed, refund_success, etc. |
| `validation.php` | 30+ | All Laravel validation messages |

### How locale switching works

```
User clicks toggle in AppNavbar
  → locale.value = 'bn'
  → localStorage.setItem('locale', 'bn')
  → PATCH /api/auth/locale { locale: 'bn' }
  → users.preferred_locale = 'bn'
  → Next API request: SetLocale middleware reads preferred_locale
  → All server error messages return in Bangla
  → All client labels switch instantly (reactive)
```

### Locale-aware features

- **Date formatting**: `toLocaleDateString('bn-BD')` vs `toLocaleDateString('en-BD')`
- **PDF statements**: `statement.blade.php` renders full Bangla when `locale=bn`
- **Validation messages**: `useValidation` factories accept i18n strings
- **Toast notifications**: All toasts use `t('toast.*')` keys
- **Badges**: `TypeBadge` and `StatusBadge` both use `t('type.*')` and `t('status.*')`

---

## ✅ Validation Rules

### Client-side — `useValidation.js` composable

| Factory | Signature | Validates |
|---|---|---|
| `required(msg?)` | `(v) =>` | Non-empty string or number |
| `email(msg?)` | `(v) =>` | RFC-compliant email format |
| `minLength(n, msg?)` | `(v) =>` | `v.length >= n` |
| `maxLength(n, msg?)` | `(v) =>` | `v.length <= n` |
| `bdPhone(msg?)` | `(v) =>` | `/^01[3-9]\d{8}$/` |
| `passwordStrong(msg?)` | `(v) =>` | 8+ chars + uppercase + digit |
| `confirmed(field, msg?)` | `(v, form) =>` | `v === form[field]` |
| `numericMin(n, msg?)` | `(v) =>` | `Number(v) >= n` |
| `numericMax(n, msg?)` | `(v) =>` | `Number(v) <= n` |
| `integer(msg?)` | `(v) =>` | `Number.isInteger(Number(v))` |
| `dateNotAfter(field, msg?)` | `(v, form) =>` | `v <= form[field]` |

All factories accept an optional message string, which is passed from `t('validation.*')` so the error appears in the user's current locale.

### Server-side — Laravel Form Requests

| Request | Rules |
|---|---|
| `LoginRequest` | `email:required,email` · `password:required` |
| `RegisterRequest` | `name:required,max:255` · `email:required,email,unique:users` · `phone:required,regex:/^01[3-9]\d{8}$/,unique:users` · `password:required,min:8,confirmed` |
| `TopupRequest` | `amount:required,numeric,min:1,max:25000` · `description:nullable,max:255` |
| `RefundRequest` | `amount:required,numeric,min:1,max:{refundable}` · `reason:nullable,max:255` |

---

## 🔐 Security Design

| Risk | Mitigation |
|---|---|
| Unauthenticated access | Sanctum Bearer tokens required on all private routes |
| `agreementID` exposure | AES-256-CBC encryption at rest via `Crypt::encryptString()` |
| Double top-up submission | Redis `SET key 1 EX 60 NX` — one inflight request per user |
| Concurrent wallet mutations | `DB::transaction()` + `User::lockForUpdate()` on every credit/debit |
| stale bKash token | Redis `Cache::remember` with 3500 s TTL (bKash expires at 3600 s) |
| Cross-origin requests | CORS restricted to `FRONTEND_URL` in `config/cors.php` |
| Refund by wrong user | `transaction.user_id !== auth()->id` → `abort(403)` |
| SQL injection | Eloquent ORM exclusively — zero raw queries |
| XSS | Vue template compiler auto-escapes all output |
| Mass assignment | `$fillable` explicitly defined on all models |

---

## 🐳 Quick Start — Docker (Recommended)

**Prerequisite:** [Docker Desktop](https://www.docker.com/products/docker-desktop) installed.

```bash
# 1. Unzip and enter project folder
cd bkash-wallet

# 2. Configure backend environment
cp backend/.env.example backend/.env
# Open backend/.env and fill in bKash credentials (see Environment Variables below)

# 3. Configure frontend environment
cp frontend/.env.example frontend/.env

# 4. Launch everything
docker compose up --build
```

Services that start automatically:

| Service | URL | Credentials |
|---|---|---|
| **Frontend** | http://localhost:5173 | — |
| **Backend API** | http://localhost:8000 | — |
| **Gotenberg** | http://localhost:3000/health | — |
| **MySQL** | localhost:3306 | bkash / secret |
| **Redis** | localhost:6379 | — |

**Demo login:** `demo@bkash.test` / `password`

```bash
# Stop all services
docker compose down

# Rebuild after code changes
docker compose up --build --force-recreate

# View backend logs
docker compose logs -f backend
```

---

## 🔧 Manual Setup

### Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `zip`, `bcmath`
- Composer 2.x
- Node.js 20+ and npm 10+
- MySQL 8.0
- Redis 7+
- Docker (for Gotenberg only)

### Step 1 — Create database

```bash
mysql -u root -p
```
```sql
CREATE DATABASE bkash_wallet
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Step 2 — Install backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### Step 3 — Configure `backend/.env`

See [Environment Variables](#-environment-variables) below. Minimum required:
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `BKASH_APP_KEY`, `BKASH_APP_SECRET`, `BKASH_USERNAME`, `BKASH_PASSWORD`

### Step 4 — Migrate and seed

```bash
php artisan migrate
php artisan db:seed    # creates demo@bkash.test / password
```

### Step 5 — Start Gotenberg

```bash
docker run --rm -p 3000:3000 gotenberg/gotenberg:8
```

### Step 6 — Start backend

```bash
php artisan serve
# → http://localhost:8000
```

### Step 7 — Install and start frontend

```bash
cd ../frontend
cp .env.example .env
npm install
npm run dev
# → http://localhost:5173
```

---

## ⚙️ Environment Variables

### `backend/.env`

```env
# Application
APP_NAME="bKash Wallet"
APP_ENV=local
APP_KEY=                              # generated by php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173    # used for CORS + redirect URLs

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bkash_wallet
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# bKash Tokenized Checkout API
BKASH_BASE_URL=https://tokenized.sandbox.bka.sh/v1.2.0-beta
BKASH_APP_KEY=your_app_key_here
BKASH_APP_SECRET=your_app_secret_here
BKASH_USERNAME=your_username_here
BKASH_PASSWORD=your_password_here
BKASH_CALLBACK_URL=http://localhost:8000/api/bkash/payment/callback
BKASH_AGREEMENT_CALLBACK_URL=http://localhost:8000/api/bkash/agreement/callback

# Gotenberg PDF
GOTENBERG_URL=http://localhost:3000
```

### `frontend/.env`

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

> **Production note:** Register at [developer.bkash.com](https://developer.bkash.com) for sandbox credentials. Switch `BKASH_BASE_URL` to the live endpoint for production. Always use HTTPS for callback URLs in production.

---

## 🧪 Running Tests

```bash
cd backend

# Run all tests (uses in-memory SQLite — no MySQL needed)
php artisan test

# With detailed output
php artisan test --verbose

# With code coverage (requires Xdebug or PCOV)
php artisan test --coverage

# Single file
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/TransactionTest.php
php artisan test tests/Unit/BkashServiceTest.php

# Single test method
php artisan test --filter test_user_can_login
```

### Test suite

| File | Tests | Coverage |
|---|---|---|
| `AuthTest.php` | 4 | Register, login, profile, locale update |
| `TransactionTest.php` | 3 | List history, type filter, ownership enforcement |
| `BkashServiceTest.php` | 3 | Invoice format, amount precision, refundable calc |
| **Total** | **10** | — |

Tests run against **in-memory SQLite** (`phpunit.xml` overrides `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`). No external services required.

---

## 🚀 Production Deployment

### Pre-flight checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_KEY` generated and securely stored
- [ ] `BKASH_BASE_URL` switched to live bKash endpoint
- [ ] All callback URLs use your public HTTPS domain
- [ ] `FRONTEND_URL` set to your frontend HTTPS domain (CORS)
- [ ] Redis protected with `REDIS_PASSWORD`
- [ ] MySQL user has limited privileges (not root)
- [ ] Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] PHP `expose_php = Off` in `php.ini`

### Build production frontend

```bash
cd frontend
VITE_API_BASE_URL=https://api.yourdomain.com/api npm run build
# Output → frontend/dist/
```

### Production Docker build

```bash
docker build \
  --build-arg VITE_API_BASE_URL=https://api.yourdomain.com/api \
  -t bkash-wallet-frontend:latest \
  ./frontend
```

---

## 🔍 Troubleshooting

### CORS error in browser
```env
# backend/.env — must match frontend origin exactly, including port
FRONTEND_URL=http://localhost:5173
```

### `php artisan migrate` fails — table already exists
```bash
php artisan migrate:fresh --seed
```

### Redis `connection refused`
```bash
redis-server --daemonize yes
redis-cli ping   # PONG = OK
```

### Storage permission error
```bash
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
# macOS/Linux with www-data:
chown -R www-data:www-data backend/storage backend/bootstrap/cache
```

### Composer out of memory
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### bKash callback not received locally
Use [ngrok](https://ngrok.com) to expose your local backend publicly:
```bash
ngrok http 8000
# Then update .env:
# BKASH_CALLBACK_URL=https://xxxx.ngrok-free.app/api/bkash/payment/callback
# BKASH_AGREEMENT_CALLBACK_URL=https://xxxx.ngrok-free.app/api/bkash/agreement/callback
```

### PDF download returns 500 or empty
```bash
# Verify Gotenberg is running
docker run --rm -p 3000:3000 gotenberg/gotenberg:8
curl http://localhost:3000/health
# Expected: {"status":"up"}
```

### Language toggle not persisting after refresh
```bash
# Check localStorage in browser devtools
localStorage.getItem('locale')   # should be 'en' or 'bn'
# Check DB
SELECT preferred_locale FROM users WHERE id = 1;
```

---

## 📄 License

This project is licensed under the **MIT License** — free to use, modify, and distribute.

---

<p align="center">
  Built with ❤️ using <strong>Laravel 11</strong>, <strong>Vue 3</strong>, <strong>vue-i18n</strong>, and the <strong>bKash Tokenized Checkout API</strong>
</p>
