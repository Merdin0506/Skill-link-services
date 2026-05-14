# SkillLink System Flow and Integrations

## Purpose
This document explains how the SkillLink system works end to end:

- how the Desktop app connects to the backend
- how data moves between frontend, backend, and database
- the main business process flow
- what external services and APIs are used
- what was added in the current implementation

This is based on the actual code in the repository, not just the intended design.

## 1. High-Level Architecture

The project has 4 main parts:

1. `Desktop/`
   Electron desktop application used by end users.

2. `Backend/`
   CodeIgniter 4 PHP backend that exposes REST APIs and also serves web dashboard pages.

3. Database
   MySQL/MariaDB database defined in `skilllink_services (1).sql` and backend migrations.

4. External services
   OTP email delivery, map/location services, and optional AI-generated OTP email wording.

## 2. Overall Connection Flow

### A. Desktop to Backend

The desktop app does not connect directly to MySQL.

The flow is:

`Desktop Renderer UI -> Electron Preload Bridge -> Electron Main Process -> Backend API -> Models -> Database`

Detailed path:

1. The user clicks a button in the Desktop UI.
2. The renderer calls `window.desktopApp.*`.
3. `preload.js` safely exposes IPC methods to the renderer.
4. Electron `ipcMain.handle(...)` in the main process receives the request.
5. The main process calls the backend REST API such as `/api/auth/login` or `/api/dashboard/data`.
6. The backend controller validates the request.
7. The backend model reads/writes MySQL tables.
8. JSON is returned back through Electron to the Desktop UI.

This means the desktop app acts as a client layer only. The backend owns the business logic and database access.

### B. Backend Web Pages

The backend also has its own browser-based interface.

The flow there is:

`Browser/Web View -> CodeIgniter Route -> Controller -> Model -> Database -> HTML View`

So the system currently supports 2 frontends:

- Electron desktop frontend
- CodeIgniter server-rendered web dashboard

## 3. Desktop App Flow

The main desktop app entry is in `Desktop/src/main/index.js`.

Important desktop pieces:

- `Desktop/src/preload/index.js`
  Exposes safe methods like `login`, `register`, `getProfile`, `getDashboardData`.

- `Desktop/src/main/features/auth/auth.handlers.js`
  Sends auth requests from Electron to backend API.

- `Desktop/src/main/features/dashboard/dashboard.handlers.js`
  Sends dashboard, booking, and worker actions to backend API.

- `Desktop/src/main/services/apiClient.js`
  Shared fetch wrapper with timeout, JSON parsing, and friendly error messages.

- `Desktop/src/main/config/appConfig.js`
  Uses `SKILLLINK_API_BASE_URL` or defaults to `http://127.0.0.1:8080`.

### Desktop startup behavior

When the desktop app starts:

1. Electron opens the local desktop HTML/UI.
2. The app checks backend availability using `/api/health`.
3. If the backend is reachable, the desktop UI can log in and load data.
4. After login, the desktop dashboard requests role-based dashboard data from the backend.

### Important note

Some navigation handlers can load backend web pages directly into the Electron window:

- `/auth/login`
- `/dashboard`

So the desktop project is a hybrid setup:

- most data work uses backend APIs
- some navigation can still open backend-rendered pages

## 4. Authentication Flow

Authentication is not just email and password. It uses OTP verification too.

### Login flow

1. User enters email and password in Desktop or web UI.
2. Backend `AuthController::login` validates credentials.
3. If correct, backend generates a 6-digit OTP.
4. OTP is stored in the `users` table with expiry and attempt counters.
5. OTP is emailed to the user.
6. User submits the OTP.
7. Backend `AuthController::verifyOtp` validates the code.
8. Backend creates an API session record.
9. Backend returns a JWT token.
10. Desktop stores and uses the JWT for future API requests.

### Security details in auth

- passwords are hashed
- failed login attempts are counted
- accounts can be temporarily locked
- OTP expires after 5 minutes
- OTP attempts are limited
- JWT includes a session identifier
- API requests are checked by `JWTAuthFilter`
- active sessions are tracked in `user_sessions`

### Session validation

The JWT is not trusted by itself.

The backend also checks whether the related session is still active using `SessionTracker`.

That means the flow is:

`JWT token -> decode token -> read session key -> verify active session -> load user -> allow API access`

## 5. Role-Based Access Flow

The system uses multiple roles:

- `super_admin`
- `admin`
- `finance`
- `worker`
- `customer`

Routes are protected using filters such as:

- `jwtauth`
- `roleapi`
- `permissionapi`
- `dashboardauth`
- `role`
- `permission`

This means each user only sees actions allowed for that role.

Examples:

- customers can create bookings and reviews
- workers can accept/start/complete jobs
- finance can process payments and payouts
- admins can manage users, bookings, services, records, and security

## 6. Main Business Process Flow

## 6.1 User Registration

1. User submits registration form.
2. Backend validates user data.
3. User row is inserted into `users`.
4. For workers, skills and experience are also saved.
5. OTP is generated.
6. OTP email is sent.
7. User verifies OTP.
8. Account becomes verified and can be used for login.

## 6.2 Booking Creation

This is the core customer-to-service process.

1. Customer selects a service.
2. Customer enters title, description, location, date, time, priority, and optional notes.
3. Customer submits booking.
4. Backend validates customer and service.
5. Backend creates a booking reference.
6. Total fee is computed from labor fee + materials fee.
7. A new row is added to `bookings`.
8. Backend automatically syncs the booking into `service_records`.

Important result:

- `bookings` is the live workflow table
- `service_records` is the operational/history record that mirrors booking progress

## 6.3 Worker Assignment

There are 2 supported assignment paths:

1. Admin assigns a worker manually.
2. Worker accepts an available pending job.

When assignment happens:

1. Backend validates booking and worker.
2. Booking status changes from `pending` to `assigned`.
3. Commission amount is calculated from worker commission rate.
4. Worker earnings are calculated.
5. `service_records` is synced again.

## 6.4 Job Start

1. Assigned worker starts the job.
2. Booking status changes to `in_progress`.
3. `started_at` is recorded.
4. `service_records` is updated.

## 6.5 Job Completion

There are 2 completion options in the backend:

1. Complete job only
2. Complete job and record customer payment at the same time

When completed:

1. Booking status changes to `completed`.
2. `completed_at` is stored.
3. `service_records` is updated.
4. If payment is collected on-site, a customer payment record is created too.

## 6.6 Payment Flow

The payment design separates customer payment from worker payout.

### Customer payment

1. Booking must already be completed.
2. Backend creates a `customer_payment` record in `payments`.
3. Payment can later be processed to `completed` or `failed`.

### Worker payout

1. Booking must be completed.
2. Customer payment must already be completed.
3. Backend creates a `worker_payout` record in `payments`.
4. Worker payout amount uses the computed `worker_earnings`.

This creates a clear financial flow:

`Customer pays company -> company records payment -> company creates worker payout`

## 6.7 Review Flow

1. Customer completes a booking.
2. Customer submits a review for the worker.
3. Review is stored in `reviews`.
4. Admin can review statistics or flagged items.

## 7. Database Flow and Main Tables

The key database tables are:

- `users`
  Stores customer, worker, admin, finance, and super admin accounts.

- `services`
  Stores service catalog entries like electrician, plumbing, mechanic, technician.

- `bookings`
  Main live workflow table for service requests.

- `payments`
  Stores both customer payments and worker payouts.

- `reviews`
  Stores customer ratings and comments.

- `service_records`
  Stores synchronized service history and reporting-friendly service entries.

- `user_sessions`
  Tracks active web and API sessions.

### Important table relationships

- one customer can create many bookings
- one worker can be assigned many bookings
- one booking belongs to one service
- one booking can have customer payment and worker payout records
- one completed booking can produce one review
- one booking syncs into one service record

## 8. Why `service_records` Exists

`service_records` is important because it is not just a duplicate table.

It acts as a normalized business record for:

- service history
- payment status tracking
- admin reporting
- operational review

The backend keeps it synced automatically from `BookingModel::syncServiceRecordFromBooking()`.

Status mapping used by the system:

- `bookings.pending` -> `service_records.pending`
- `bookings.assigned` -> `service_records.scheduled`
- `bookings.in_progress` -> `service_records.in_progress`
- `bookings.completed` -> `service_records.completed`
- `bookings.cancelled` or `rejected` -> `service_records.cancelled`

## 9. External APIs and Integrations

This part is especially important because some integrations are full APIs and some are lightweight URL integrations only.

## 9.1 Google Maps

Google Maps is used in a lightweight way.

What the code does:

- for worker job details, the backend generates Google Maps links:
  - open map
  - get directions
- the link uses either:
  - saved coordinates (`latitude`, `longitude`), or
  - plain text address

What this means:

- the system does not currently call Google Maps Web Services directly
- there is no Google Maps API key logic in the current booking/job flow
- it mainly opens Google Maps in the browser using a URL

So this is best described as:

`Google Maps link integration`, not a full `Google Maps backend API integration`

## 9.2 OpenStreetMap / Nominatim / Leaflet

The actual location picker in the backend web booking form uses:

- `Leaflet`
  for the interactive map UI
- `OpenStreetMap` tiles
  for map rendering
- `Nominatim`
  for forward geocoding and reverse geocoding

Actual behavior:

- customer can use current device location
- address can be reverse-geocoded from coordinates
- typed address can be searched
- marker can be dragged to refine the location
- latitude and longitude are stored with the booking

This is the real mapping stack for booking input.

## 9.3 Gmail SMTP

OTP emails are sent through Gmail SMTP using PHPMailer.

Current setup in code:

- SMTP host: `smtp.gmail.com`
- port: `465`
- secure transport: SMTPS

Used for:

- registration OTP
- login OTP
- resend OTP

Required environment values:

- `SMTP_USER`
- `SMTP_PASS`
- optional `SMTP_FROM_NAME`

## 9.4 Gemini API

The system has an optional Gemini integration for OTP email wording.

What it does:

- if `GEMINI_API_KEY` exists, the backend asks Gemini to generate a short OTP email body
- if Gemini fails or no key exists, the backend falls back to a default email template

Important note:

- Gemini is not required for authentication to work
- it only improves the email message content
- OTP logic still works without it

## 10. What Was Added in This Implementation

Based on the current codebase, these are the major implemented additions and behaviors:

### Security and auth additions

- OTP-based registration/login verification
- JWT authentication
- API session tracking with session key validation
- failed login counting and temporary lockout
- user activity logging
- encrypted sensitive user fields at rest

### Desktop integration additions

- Electron preload bridge
- IPC handlers for auth and dashboard
- backend health check support
- backend URL fallback support
- desktop-friendly API error handling

### Booking and operations additions

- booking lifecycle statuses
- worker self-accept flow
- start and complete job flow
- complete job with on-site payment flow
- automatic service record sync from booking changes
- latitude and longitude storage in bookings

### Finance additions

- separate customer payment and worker payout records
- payment statistics and revenue reporting
- worker earnings computation using commission

### Security admin additions

- security dashboard routes
- audit logs
- blocked IP handling
- security reports and statistics

## 11. Desktop-to-Backend Connection Summary

If you want the simplest explanation of how the desktop connects to the backend, it is this:

1. Desktop UI sends request through Electron bridge.
2. Electron main process calls backend REST API over HTTP.
3. Backend checks JWT/session/role.
4. Backend controller runs the business logic.
5. Backend model reads or writes MySQL.
6. Backend returns JSON.
7. Electron returns that result to the desktop UI.

The desktop app never talks directly to the database.

## 12. Practical Example Flows

## Example A: Customer books a service

1. Customer logs in and verifies OTP.
2. Desktop or web UI loads service list from backend.
3. Customer chooses service and enters location.
4. Backend creates booking.
5. Backend creates matching `service_record`.
6. Admin or worker later assigns/accepts the job.

## Example B: Worker completes a job with cash collection

1. Worker logs in and loads available/assigned jobs.
2. Worker starts job.
3. Worker completes job using the complete-with-payment endpoint.
4. Backend marks booking completed.
5. Backend inserts `customer_payment`.
6. Backend syncs payment reference/status into `service_records`.
7. Finance/admin can later create worker payout.

## 13. Important Current Reality

The project is not only a backend API.

It is currently a combined platform with:

- Electron desktop client
- backend web dashboard
- REST APIs
- MySQL database
- email delivery
- map/location support

Also, the map story is split:

- booking location input uses OpenStreetMap/Nominatim/Leaflet
- navigation to job locations uses Google Maps links

## 14. Recommended Short Explanation for Presentation

If you need to explain the system to a teacher, panel, or teammate, you can say:

> SkillLink is a service booking and workforce management system. The desktop app connects to a CodeIgniter backend through REST APIs. The backend handles authentication, role-based access, booking lifecycle, payment processing, reviews, and security controls. All business data is stored in MySQL. Location input uses Leaflet with OpenStreetMap and Nominatim, while workers can open Google Maps links for directions. OTP emails are sent through Gmail SMTP, and Gemini can optionally generate the OTP email content.

## 15. File References

Main files that define this flow:

- `Desktop/src/main/index.js`
- `Desktop/src/preload/index.js`
- `Desktop/src/main/features/auth/auth.handlers.js`
- `Desktop/src/main/features/dashboard/dashboard.handlers.js`
- `Desktop/src/main/services/apiClient.js`
- `Backend/app/Config/Routes.php`
- `Backend/app/Controllers/API/AuthController.php`
- `Backend/app/Controllers/API/DashboardController.php`
- `Backend/app/Controllers/API/BookingsController.php`
- `Backend/app/Controllers/API/PaymentsController.php`
- `Backend/app/Models/BookingModel.php`
- `Backend/app/Models/PaymentModel.php`
- `Backend/app/Models/ServiceRecordModel.php`
- `Backend/app/Libraries/SessionTracker.php`
- `Backend/app/Filters/JWTAuthFilter.php`
- `Backend/app/Libraries/SensitiveDataCipher.php`
- `Backend/app/Views/dashboard/customer_service_details.php`
- `Backend/app/Views/dashboard/worker_job_details.php`
- `skilllink_services (1).sql`

