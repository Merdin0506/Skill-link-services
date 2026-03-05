# Service Records Module — Technical Documentation

> **SkillLink Services** · CodeIgniter 4.7.0 · PHP 8.2+  
> Last updated: March 5, 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Database Schema](#2-database-schema)
3. [Model Layer](#3-model-layer)
4. [Dashboard (Web) Controller & Routes](#4-dashboard-web-controller--routes)
5. [REST API Controller & Routes](#5-rest-api-controller--routes)
6. [Views / UI](#6-views--ui)
7. [Soft Delete Workflow](#7-soft-delete-workflow)
8. [Security & Access Control](#8-security--access-control)
9. [File Map](#9-file-map)

---

## 1. Overview

The **Service Records Module** manages the lifecycle of service transactions in the SkillLink platform. Each record tracks:

- Who requested the service (customer)
- Who performs it (provider/worker)
- Which service is being delivered
- Scheduling, status progression, payment tracking
- Free-text notes from customer, provider, and admin

Records are **never permanently deleted** — all deletions are **soft deletes** (via a `deleted_at` timestamp). Archived records can be viewed and restored by admin users.

The module exposes two interfaces:

| Interface | Audience | Base Path |
|-----------|----------|-----------|
| **Dashboard (Web)** | Admin users via browser | `/admin/records` |
| **REST API** | Any authenticated client | `/api/records` |

---

## 2. Database Schema

### 2.1 Table: `service_records`

Created by migration `2026-03-04-053435_CreateServiceRecords.php`, extended by `2026-03-05-060000_AddSoftDeleteToServiceRecords.php`.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | INT(11) UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| `booking_id` | INT(11) UNSIGNED | YES | NULL | FK → `bookings.id` |
| `customer_id` | INT(11) UNSIGNED | NO | — | FK → `users.id` (customer) |
| `provider_id` | INT(11) UNSIGNED | YES | NULL | FK → `users.id` (worker) |
| `service_id` | INT(11) UNSIGNED | NO | — | FK → `services.id` |
| `status` | ENUM | NO | `'pending'` | `pending`, `scheduled`, `in_progress`, `completed`, `cancelled` |
| `scheduled_at` | DATETIME | YES | NULL | When the service is scheduled |
| `started_at` | DATETIME | YES | NULL | When the service started |
| `completed_at` | DATETIME | YES | NULL | When the service completed |
| `address_text` | TEXT | YES | NULL | Service location |
| `labor_fee` | DECIMAL(10,2) | NO | `0.00` | Worker's fee |
| `platform_fee` | DECIMAL(10,2) | NO | `0.00` | SkillLink platform fee |
| `total_amount` | DECIMAL(10,2) | NO | `0.00` | Auto-calculated: `labor_fee + platform_fee` |
| `payment_status` | ENUM | NO | `'unpaid'` | `unpaid`, `partial`, `paid`, `refunded` |
| `payment_ref` | VARCHAR(100) | YES | NULL | Payment reference number |
| `customer_note` | TEXT | YES | NULL | Note from customer |
| `provider_note` | TEXT | YES | NULL | Note from provider |
| `admin_note` | TEXT | YES | NULL | Note from admin |
| `created_at` | DATETIME | NO | — | Auto-managed by CI4 |
| `updated_at` | DATETIME | NO | — | Auto-managed by CI4 |
| `deleted_at` | DATETIME | YES | NULL | Soft-delete timestamp |

### 2.2 Indexes

| Index | Column(s) |
|-------|-----------|
| PRIMARY | `id` |
| INDEX | `customer_id` |
| INDEX | `provider_id` |
| INDEX | `service_id` |
| INDEX | `booking_id` |
| INDEX | `status` |
| INDEX | `payment_status` |
| INDEX | `scheduled_at` |

### 2.3 Foreign Keys

| Column | References | ON UPDATE | ON DELETE |
|--------|------------|-----------|----------|
| `customer_id` | `users.id` | CASCADE | CASCADE |
| `provider_id` | `users.id` | SET NULL | CASCADE |
| `service_id` | `services.id` | CASCADE | CASCADE |
| `booking_id` | `bookings.id` | SET NULL | CASCADE |

### 2.4 Migrations

| File | Purpose |
|------|---------|
| `2026-03-04-053435_CreateServiceRecords.php` | Creates the `service_records` table with all columns, indexes, and foreign keys |
| `2026-03-05-060000_AddSoftDeleteToServiceRecords.php` | Adds the `deleted_at` DATETIME column for soft deletes |

Run migrations:

```bash
php spark migrate
```

---

## 3. Model Layer

**File:** `app/Models/ServiceRecordModel.php`

### 3.1 Configuration

```
Table:            service_records
Primary Key:      id
Return Type:      array
Soft Deletes:     enabled (deleted_at)
Timestamps:       enabled (created_at, updated_at)
```

### 3.2 Allowed Fields

`booking_id`, `customer_id`, `provider_id`, `service_id`, `status`, `scheduled_at`, `started_at`, `completed_at`, `address_text`, `labor_fee`, `platform_fee`, `total_amount`, `payment_status`, `payment_ref`, `customer_note`, `provider_note`, `admin_note`

### 3.3 Validation Rules

| Field | Rules |
|-------|-------|
| `customer_id` | required, integer |
| `service_id` | required, integer |
| `status` | required, in_list[pending, scheduled, in_progress, completed, cancelled] |
| `labor_fee` | permit_empty, numeric, ≥ 0 |
| `platform_fee` | permit_empty, numeric, ≥ 0 |
| `total_amount` | permit_empty, numeric, ≥ 0 |
| `payment_status` | permit_empty, in_list[unpaid, partial, paid, refunded] |
| `payment_ref` | permit_empty, max_length[100] |
| `address_text` | permit_empty, max_length[1000] |

### 3.4 Helper Methods

| Method | Description |
|--------|-------------|
| `getRecordWithDetails(int $id)` | Returns a single record with JOINed customer name, provider name, service name & category |
| `getFilteredRecords(array $filters, int $limit, int $offset)` | List with optional filters (status, payment_status, customer_id, provider_id, service_id, date_from, date_to, q, sort, order) and pagination |
| `countFilteredRecords(array $filters)` | Count matching records for pagination metadata |
| `getCustomerRecords(int $customerId, ?string $status)` | All records for a specific customer, optionally filtered by status |
| `getProviderRecords(int $providerId, ?string $status)` | All records assigned to a specific provider |

### 3.5 Supported Filter Keys

Used by `getFilteredRecords()` and `countFilteredRecords()`:

| Key | Type | Description |
|-----|------|-------------|
| `status` | string | Exact match on `service_records.status` |
| `payment_status` | string | Exact match on `service_records.payment_status` |
| `customer_id` | int | Filter by customer |
| `provider_id` | int | Filter by provider |
| `service_id` | int | Filter by service |
| `date_from` | string (datetime) | `scheduled_at >= value` |
| `date_to` | string (datetime) | `scheduled_at <= value` |
| `q` | string | LIKE search across: payment_ref, address_text, customer first/last name, service name |
| `sort` | string | Column to sort by (whitelist enforced) |
| `order` | string | `ASC` or `DESC` |

**Allowed sort columns:** `service_records.created_at`, `service_records.scheduled_at`, `service_records.total_amount`, `service_records.status`

---

## 4. Dashboard (Web) Controller & Routes

**File:** `app/Controllers/Dashboard.php`  
**Auth Filter:** `dashboardauth` applied to all `admin/*` routes  
**Access:** Admin-only (role check in each method)

### 4.1 Routes

| HTTP Method | URI | Controller Method | Purpose |
|-------------|-----|-------------------|---------|
| GET | `/admin/records` | `records()` | List all records (with filters) |
| GET | `/admin/records/create` | `recordCreate()` | Show create form |
| POST | `/admin/records/store` | `recordStore()` | Save new record |
| GET | `/admin/records/edit/{id}` | `recordEdit($id)` | Show edit form |
| POST | `/admin/records/update/{id}` | `recordUpdate($id)` | Save changes |
| POST | `/admin/records/delete/{id}` | `recordDelete($id)` | Soft delete |
| POST | `/admin/records/restore/{id}` | `recordRestore($id)` | Restore soft-deleted |

### 4.2 Method Details

#### `records()`
- Reads query params: `status`, `payment_status`, `q`, `show_deleted`
- Builds a query with JOINs to `users` (as customers & providers) and `services`
- When `show_deleted=1`, calls `onlyDeleted()` to show archived records only
- Passes `$records` and `$filters` to the view

#### `recordCreate()`
- Loads dropdown data: customers (`user_type = 'customer'`), workers (`user_type = 'worker'`), active services
- Passes `record => null` to indicate create mode

#### `recordStore()`
- Validates: `customer_id` (required), `service_id` (required), `status` (required, ENUM), fees (numeric ≥ 0), `payment_status` (ENUM)
- Calculates `total_amount = labor_fee + platform_fee`
- On failure: redirects back with input and validation errors
- On success: redirects to `/admin/records` with success flash

#### `recordEdit($id)`
- Finds record by ID; redirects with error if not found
- Loads same dropdown data as create, plus the existing record
- Passes `record => [...]` to indicate edit mode

#### `recordUpdate($id)`
- Same validation as store
- Recalculates `total_amount`
- On failure: redirects back with errors
- On success: redirects to list with success flash

#### `recordDelete($id)`
- Finds record; calls `$recordModel->delete($id)` which sets `deleted_at` (soft delete)
- Flash: "Record deleted (archived) successfully."

#### `recordRestore($id)`
- Finds record via `onlyDeleted()->find($id)`
- Clears `deleted_at` by updating it to `null`
- Flash: "Record restored successfully."

---

## 5. REST API Controller & Routes

**File:** `app/Controllers/API/RecordsController.php`  
**Auth:** Session-based via `getAuthUser()` helper  
**Response format:** JSON using CI4's `ResponseTrait`

### 5.1 Routes

| HTTP Method | URI | Controller Method | Purpose |
|-------------|-----|-------------------|---------|
| GET | `/api/records` | `index()` | List / search / paginate |
| GET | `/api/records/{id}` | `show($id)` | Get single record with details |
| POST | `/api/records` | `create()` | Create new record |
| PUT | `/api/records/{id}` | `update($id)` | Update existing record |
| DELETE | `/api/records/{id}` | `delete($id)` | Soft delete record |

### 5.2 Role-Based Access Control (RBAC)

| Action | admin | owner | cashier | worker | customer |
|--------|-------|-------|---------|--------|----------|
| **List (index)** | All records | All records | All records | Own assigned only | Own records only |
| **Show** | Any | Any | Any | Own assigned only | Own records only |
| **Create** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Update** | All fields | All fields | All fields | `provider_note`, `status`, `started_at`, `completed_at` only | ❌ |
| **Delete** | ✅ | ❌ | ❌ | ❌ | ❌ |

### 5.3 Pagination

The `index()` endpoint supports pagination via query parameters:

| Parameter | Default | Max | Description |
|-----------|---------|-----|-------------|
| `page` | 1 | — | Current page number |
| `limit` | 20 | 100 | Records per page |

Response includes a `pagination` object:

```json
{
  "status": "success",
  "data": [...],
  "pagination": {
    "total": 85,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 5
  }
}
```

### 5.4 Error Responses

| Status | When |
|--------|------|
| `401 Unauthorized` | No active session |
| `403 Forbidden` | Role not permitted for the action |
| `404 Not Found` | Record ID does not exist |
| `400 Bad Request` | Validation failure (returns field-level errors) |

---

## 6. Views / UI

Built with **Bootstrap 5.3.0** (CDN). No build tooling required.

### 6.1 Records List — `admin_records.php`

**Path:** `app/Views/dashboard/admin_records.php`

| Feature | Description |
|---------|-------------|
| **Header** | Title with "Archived" badge when viewing deleted records |
| **Action Buttons** | "+ New Record", "View Archived" / "Active Records" toggle, Dashboard link, Logout |
| **Flash Messages** | Green success / red error alerts with dismiss button |
| **Filter Bar** | Text search, status dropdown, payment status dropdown, Filter + Clear buttons |
| **Data Table** | Columns: ID, Customer, Provider, Service, Status (color-coded badge), Payment (color-coded badge), Total (₱), Scheduled, Actions |
| **Row Actions (Active)** | Edit button, Delete button (with confirmation prompt) |
| **Row Actions (Archived)** | Restore button (with confirmation prompt) |
| **Empty State** | "No service records found." spanning full row |

#### Status Badge Colors

| Status | Color |
|--------|-------|
| pending | warning (yellow) |
| scheduled | info (cyan) |
| in_progress | primary (blue) |
| completed | success (green) |
| cancelled | danger (red) |

#### Payment Badge Colors

| Payment Status | Color |
|---------------|-------|
| paid | success (green) |
| refunded | danger (red) |
| unpaid / partial | warning (yellow) |

### 6.2 Create / Edit Form — `admin_record_form.php`

**Path:** `app/Views/dashboard/admin_record_form.php`

Dynamic title: "Create Service Record" or "Edit Service Record" based on whether `$record` is set.

| Section | Fields |
|---------|--------|
| **Row 1** | Customer dropdown (required), Service dropdown (required), Provider/Worker dropdown (optional) |
| **Row 2** | Status select (required), Payment Status select, Scheduled At (datetime-local), Payment Reference (text) |
| **Row 3** | Labor Fee (₱), Platform Fee (₱), Total Amount (read-only, auto-calculated via JS) |
| **Row 4** | Address (text input, max 1000 chars) |
| **Row 5** | Customer Note (textarea), Provider Note (textarea), Admin Note (textarea) |
| **Actions** | Submit button ("Create Record" / "Update Record"), Cancel link |

**JavaScript:** Auto-calculates `Total = Labor Fee + Platform Fee` on keyup in real time.

**Validation errors** display as a bulleted list in a red alert box at the top of the form.

**Form action:**
- Create mode → `POST /admin/records/store`
- Edit mode → `POST /admin/records/update/{id}`

---

## 7. Soft Delete Workflow

### How It Works

1. **Model configuration:** `$useSoftDeletes = true` and `$deletedField = 'deleted_at'` in `ServiceRecordModel`
2. **Delete action:** Calling `$model->delete($id)` does NOT remove the row — it sets `deleted_at = NOW()`
3. **Default queries:** All standard `find()`, `findAll()`, `getFilteredRecords()` automatically exclude rows where `deleted_at IS NOT NULL`
4. **View archived:** `$model->onlyDeleted()` retrieves only soft-deleted rows
5. **Restore:** Update `deleted_at` to `NULL` to make the record active again

### User Flow

```
Active Record
    │
    ├──[Admin clicks "Delete"]──→ Confirmation dialog
    │                                 │
    │                           [Confirm]──→ POST /admin/records/delete/{id}
    │                                         │
    │                                    deleted_at = NOW()
    │                                         │
    │                                    Flash: "Record deleted (archived)"
    │                                         │
    ▼                                         ▼
                                     Archived Record
                                          │
                                    [Admin clicks "View Archived"]
                                          │
                                    GET /admin/records?show_deleted=1
                                          │
                                    Table shows archived records
                                          │
                                    [Admin clicks "Restore"]
                                          │
                                    POST /admin/records/restore/{id}
                                          │
                                    deleted_at = NULL
                                          │
                                    Flash: "Record restored successfully"
                                          │
                                          ▼
                                     Active Record
```

---

## 8. Security & Access Control

### 8.1 Authentication

- **Dashboard:** Session-based. The `dashboardauth` filter (applied to `admin/*` via `app/Config/Filters.php`) requires an active session with `user_id`.
- **API:** Session checked via `getAuthUser()` in each method. Returns 401 if no session.

### 8.2 Authorization

- All Dashboard record routes require `user_role === 'admin'` — enforced in every controller method.
- API routes enforce role-based rules per action (see RBAC table in Section 5.2).

### 8.3 Input Validation

- Server-side validation on **every** create/update operation.
- ENUM values validated via `in_list[]` rules to prevent invalid status/payment values.
- Numeric fields validated with `greater_than_equal_to[0]`.
- Text fields capped at `max_length` to prevent abuse.
- All output escaped with `esc()` in views to prevent XSS.

### 8.4 SQL Injection Prevention

- All database queries use CI4's Query Builder with parameterized bindings.
- Sort column whitelisted against `$allowed_sort` array — arbitrary column names are rejected.

### 8.5 CSRF Protection

- Dashboard forms use POST for all state-changing operations.
- CI4's built-in CSRF filter protects form submissions (if enabled globally).

---

## 9. File Map

```
app/
├── Config/
│   ├── Routes.php                  ← 7 dashboard + 5 API record routes
│   └── Filters.php                 ← dashboardauth filter on admin/*
├── Controllers/
│   ├── Dashboard.php               ← 7 web CRUD methods (records, recordCreate, etc.)
│   └── API/
│       └── RecordsController.php   ← 5 REST API methods (index, show, create, update, delete)
├── Database/
│   └── Migrations/
│       ├── 2026-03-04-053435_CreateServiceRecords.php       ← Table creation
│       └── 2026-03-05-060000_AddSoftDeleteToServiceRecords.php ← Adds deleted_at
├── Models/
│   └── ServiceRecordModel.php      ← Model with soft deletes, validation, helpers
└── Views/
    └── dashboard/
        ├── admin_records.php       ← List view with filters, actions, archive toggle
        ├── admin_record_form.php   ← Create/Edit form with dropdowns, auto-calc total
        └── index.php               ← Sidebar has "Service Records" link
```
