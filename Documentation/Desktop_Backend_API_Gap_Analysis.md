# Desktop Backend API Gap Analysis

## Summary
The Desktop app is connected to the Backend API, but it currently implements only the authentication flow and a role-aware dashboard overview.

This means:
- Desktop can authenticate against the Backend.
- Desktop can load profile and dashboard data from the Backend.
- Desktop does not yet expose most operational Backend modules as native Desktop screens.

## Current Desktop Coverage

### Implemented in Desktop
- `POST /api/auth/login`
- `POST /api/auth/register`
- `POST /api/auth/verify-otp`
- `POST /api/auth/resend-otp`
- `GET /api/auth/profile`
- `POST /api/auth/logout`
- `GET /api/dashboard/data`
- `GET /api/dashboard/stats`
- `GET /api/dashboard/analytics`
- `GET /api/dashboard/bookings`

### Present in Desktop UI but not functionally complete
- Role-based sidebar links for:
  - users
  - bookings
  - payments
  - records
  - available jobs
  - my jobs
  - earnings
  - payouts
  - reports
  - services
- Dashboard tables with placeholder `View` buttons
- Profile and settings navigation entries

These items are visual only right now. They are not backed by dedicated Desktop screens or complete interaction flows.

## Backend API Surface

### Auth and Account
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/verify-otp`
- `POST /api/auth/resend-otp`
- `GET /api/auth/profile`
- `PUT /api/auth/profile`
- `POST /api/auth/change-password`
- `POST /api/auth/logout`

### Dashboard
- `GET /api/dashboard/data`
- `GET /api/dashboard/stats`
- `GET /api/dashboard/analytics`
- `GET /api/dashboard/bookings`

### Services
- `GET /api/services`
- `GET /api/services/categories`
- `GET /api/services/popular`
- `GET /api/services/category/{category}`
- `GET /api/services/{id}`
- `POST /api/services`
- `PUT /api/services/{id}`
- `DELETE /api/services/{id}`

### Reviews
- `GET /api/reviews`
- `GET /api/reviews/{id}`
- `POST /api/reviews`
- `GET /api/reviews/worker/{id}`
- `GET /api/reviews/top-workers`
- `GET /api/reviews/recent`
- `GET /api/reviews/can-review`
- `PUT /api/reviews/{id}/status`
- `GET /api/reviews/flagged`
- `GET /api/reviews/statistics`

### Bookings
- `POST /api/bookings`
- `PUT /api/bookings/{id}/cancel`
- `PUT /api/bookings/{id}/start`
- `PUT /api/bookings/{id}/complete`
- `GET /api/bookings`
- `GET /api/bookings/{id}`
- `POST /api/bookings/assign-worker`
- `GET /api/bookings/available-workers/{id}`
- `GET /api/bookings/statistics`

### Payments
- `POST /api/payments/customer`
- `GET /api/payments/worker-earnings/{id}`
- `GET /api/payments`
- `GET /api/payments/{id}`
- `PUT /api/payments/{id}/process`
- `POST /api/payments/worker`
- `GET /api/payments/methods`
- `GET /api/payments/statistics`
- `GET /api/payments/revenue-report`

### Users
- `GET /api/users`
- `POST /api/users`
- `GET /api/users/{id}`
- `PUT /api/users/{id}`
- `DELETE /api/users/{id}`
- `GET /api/users/workers`
- `GET /api/users/customers`
- `GET /api/users/admin-staff`
- `GET /api/users/dashboard/{id}`
- `GET /api/users/statistics`
- `GET /api/users/search`
- `PUT /api/users/{id}/profile-image`

### Records
- `GET /api/records`
- `GET /api/records/{id}`
- `POST /api/records`
- `PUT /api/records/{id}`
- `DELETE /api/records/{id}`

## Gap Matrix

### Fully available in Desktop
- Authentication
- OTP flow
- Logout
- Profile read
- Dashboard summary read

### Partially available in Desktop
- Role-based navigation exists, but target modules are not implemented
- Dashboard shows backend data, but action buttons do not open detailed workflows

### Missing from Desktop
- Profile update
- Change password
- Customer bookings workflows
- Worker job actions
- Finance payment processing
- Finance payout creation
- Services browsing and detail screens
- Admin user management
- Admin bookings management
- Records CRUD
- Reviews workflows
- Search, filters, pagination, and detail drawers/forms

## Best Build Order

### Phase 1: Complete account and navigation basics
- Add profile screen using `GET/PUT /api/auth/profile`
- Add change-password flow using `POST /api/auth/change-password`
- Make sidebar navigation switch real Desktop views instead of hash-only links

### Phase 2: Customer and worker core workflows
- Customer:
  - list services
  - service details
  - create booking
  - cancel booking
  - submit review
- Worker:
  - list available jobs
  - list assigned jobs
  - start booking
  - complete booking
  - earnings view

### Phase 3: Finance workflows
- payments list
- payment details
- process payment
- payout creation
- revenue report

### Phase 4: Admin workflows
- users list/search/detail
- create/update/delete user
- bookings list/detail/assign worker
- records list/detail/create/update/delete
- statistics screens

## Recommendation
The next best implementation step is:

1. build a real Desktop view router
2. implement profile/settings screens
3. implement customer services and bookings flows
4. implement worker jobs flows

That sequence gives the Desktop app useful end-to-end functionality quickly without jumping straight into the heavier admin modules.
