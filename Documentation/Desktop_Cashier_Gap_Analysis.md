# Desktop Cashier Gap Analysis

## Scope
This file focuses only on the `cashier` flow in the Desktop app.

For this project, `cashier` maps to the `finance` role in the backend and Desktop UI.

## What The Desktop Cashier Already Uses

The Desktop cashier currently calls these backend endpoints:

- `GET /api/payments?limit=50`
- `GET /api/payments/statistics`
- `GET /api/payments/revenue-report`
- `GET /api/payments?payment_type=worker_payout&limit=50`

This means the Desktop cashier already supports:

- payment list view
- payout list view
- payment statistics
- revenue report display

## Backend Cashier Features Missing From Desktop

These backend finance features exist but are not currently exposed as full Desktop cashier workflows.

### 1. Payment Detail View
- Backend endpoint: `GET /api/payments/{id}`
- Current Desktop gap:
  the cashier can see payment rows, but cannot open a dedicated payment detail screen or side panel.

### 2. Process Payment Action
- Backend endpoint: `PUT /api/payments/{id}/process`
- Current Desktop gap:
  the cashier cannot mark a payment as processed, paid, or completed from the Desktop UI.

### 3. Create Worker Payout
- Backend endpoint: `POST /api/payments/worker`
- Current Desktop gap:
  the cashier can view worker payout records, but cannot create a new payout from the Desktop app.

### 4. Payment Methods Lookup
- Backend endpoint: `GET /api/payments/methods`
- Current Desktop gap:
  the cashier UI does not load supported payment methods for forms or processing actions.

### 5. Customer Payment Creation
- Backend endpoint: `POST /api/payments/customer`
- Current Desktop gap:
  there is no Desktop cashier flow for creating a direct customer payment record.

## Desktop UI Gaps

Even where backend support exists, the Desktop cashier is still missing these interface behaviors:

- clickable payment detail action
- payment processing button
- payout creation form
- payment method selector
- customer payment entry form
- status update actions for payment rows

## Web-Only Cashier Workflows Not Yet In Desktop

The backend web module still has finance workflows that are not represented as Desktop-native cashier flows:

- `/finance/payments/record/{id}`
- `/finance/payouts/record/{id}`
- `/finance/reports`

## Plain Summary

The Desktop cashier currently works as a read-focused finance dashboard.

It can:

- list payments
- list worker payouts
- show stats
- show reports

It cannot yet:

- open a payment detail view
- process a payment
- create a worker payout
- load and use payment methods
- create a customer payment record

## Recommended Next Cashier Build Steps

1. Add payment detail UI using `GET /api/payments/{id}`
2. Add process payment action using `PUT /api/payments/{id}/process`
3. Add create payout form using `POST /api/payments/worker`
4. Load payment methods using `GET /api/payments/methods`
5. Add customer payment entry using `POST /api/payments/customer`
