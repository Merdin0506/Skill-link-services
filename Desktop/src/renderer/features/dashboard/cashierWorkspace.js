import { getElementById } from '../../core/dom.js';

function formatPersonName(firstName, lastName) {
  const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
  return fullName || '-';
}

function buildMethodOptions(methods = {}) {
  const entries = Object.entries(methods || {});
  if (!entries.length) {
    return '<option value="">No payment methods available</option>';
  }

  return entries.map(([value, label]) => `
    <option value="${value}">${label}</option>
  `).join('');
}

function createBookingOptionLabel(booking, amountKey = 'total_fee') {
  const amount = Number(booking?.[amountKey] || 0);
  const title = booking?.title ? ` - ${booking.title}` : '';
  return `${booking.booking_reference || booking.id}${title} (${amount.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })})`;
}

function createCandidateOptions(bookings = [], amountKey = 'total_fee') {
  if (!bookings.length) {
    return '<option value="">No eligible bookings available</option>';
  }

  return bookings.map((booking) => `
    <option value="${booking.id}" data-amount="${Number(booking?.[amountKey] || 0).toFixed(2)}">${createBookingOptionLabel(booking, amountKey)}</option>
  `).join('');
}

async function fetchBookingDetailsMap(session, bridge, performAuthenticatedRequest, bookingIds = []) {
  const uniqueBookingIds = [...new Set((bookingIds || []).map((id) => Number(id || 0)).filter(Boolean))];
  const entries = await Promise.all(uniqueBookingIds.map(async (bookingId) => {
    try {
      const response = await performAuthenticatedRequest(session, bridge, `/api/bookings/${bookingId}`, { method: 'GET' });
      return [bookingId, response?.data || null];
    } catch {
      return [bookingId, null];
    }
  }));

  return Object.fromEntries(entries);
}

function mergeBookingDetailsIntoPayments(payments = [], bookingDetailsMap = {}) {
  return (payments || []).map((payment) => {
    const details = bookingDetailsMap[Number(payment.booking_id || 0)] || null;
    if (!details) {
      return payment;
    }

    return {
      ...payment,
      booking_reference: payment.booking_reference || details.booking_reference,
      booking_title: payment.booking_title || details.title,
      service_name: payment.service_name || details.service_name,
      customer_first_name: payment.customer_first_name || details.customer_first_name,
      customer_last_name: payment.customer_last_name || details.customer_last_name,
      worker_first_name: payment.worker_first_name || details.worker_first_name,
      worker_last_name: payment.worker_last_name || details.worker_last_name,
      commission_amount: details.commission_amount ?? payment.commission_amount,
      worker_earnings: details.worker_earnings ?? payment.worker_earnings
    };
  });
}

function createReportAnalytics(state) {
  const completedBookings = state.routeReportCompletedBookings || [];
  const completedCustomerPayments = state.routeReportCustomerPayments || [];
  const paymentMethodCounts = completedCustomerPayments.reduce((accumulator, payment) => {
    const methodKey = String(payment.payment_method || 'unknown');
    accumulator[methodKey] = (accumulator[methodKey] || 0) + 1;
    return accumulator;
  }, {});

  return {
    completedJobs: completedBookings.length,
    totalCommission: completedBookings.reduce((sum, booking) => sum + Number(booking.commission_amount || 0), 0),
    paymentMethods: Object.entries(paymentMethodCounts)
      .map(([method, count]) => ({ method, count }))
      .sort((left, right) => right.count - left.count)
  };
}

function getDefaultCashierFilters() {
  return {
    payments: { q: '', status: '', method: '', date_from: '', date_to: '' },
    payouts: { q: '', status: '', method: '', date_from: '', date_to: '' }
  };
}

function ensureCashierFilters(state) {
  state.cashierFilters = {
    ...getDefaultCashierFilters(),
    ...(state.cashierFilters || {}),
    payments: {
      ...getDefaultCashierFilters().payments,
      ...((state.cashierFilters || {}).payments || {})
    },
    payouts: {
      ...getDefaultCashierFilters().payouts,
      ...((state.cashierFilters || {}).payouts || {})
    }
  };
}

function buildFilterMethodOptions(methods = {}, currentValue = '') {
  const baseOption = '<option value="">All methods</option>';
  const entries = Object.entries(methods || {});
  return baseOption + entries.map(([value, label]) => `
    <option value="${value}" ${currentValue === value ? 'selected' : ''}>${label}</option>
  `).join('');
}

function createCashierFiltersCard(routeKey, state) {
  ensureCashierFilters(state);
  const filters = state.cashierFilters?.[routeKey] || getDefaultCashierFilters()[routeKey];
  const methods = state.routePaymentMethods || {};

  return `
    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> Filter Ledger
      </div>
      <div class="card-body desktop-card-body">
        <form data-cashier-filter-form="${routeKey}">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Search</label>
              <input
                type="search"
                class="form-control"
                name="q"
                value="${String(filters.q || '').replace(/"/g, '&quot;')}"
                placeholder="Reference, booking, customer, or worker"
              />
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="">All statuses</option>
                <option value="pending" ${filters.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="completed" ${filters.status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="failed" ${filters.status === 'failed' ? 'selected' : ''}>Failed</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Payment Method</label>
              <select class="form-select" name="method">
                ${buildFilterMethodOptions(methods, filters.method || '')}
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">From Date</label>
              <input type="date" class="form-control" name="date_from" value="${filters.date_from || ''}" />
            </div>
            <div class="col-md-3">
              <label class="form-label">To Date</label>
              <input type="date" class="form-control" name="date_to" value="${filters.date_to || ''}" />
            </div>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" data-cashier-filter-reset="${routeKey}">Reset</button>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function applyCashierFilters(payments = [], filters = {}, routeKey = 'payments') {
  const query = String(filters.q || '').trim().toLowerCase();
  const status = String(filters.status || '').trim().toLowerCase();
  const method = String(filters.method || '').trim().toLowerCase();
  const dateFrom = String(filters.date_from || '').trim();
  const dateTo = String(filters.date_to || '').trim();

  return (payments || []).filter((payment) => {
    const matchesStatus = !status || String(payment.status || '').toLowerCase() === status;
    const matchesMethod = !method || String(payment.payment_method || '').toLowerCase() === method;

    const searchableText = [
      payment.payment_reference,
      payment.transaction_id,
      payment.booking_reference,
      payment.booking_title,
      routeKey === 'payments'
        ? formatPersonName(payment.customer_first_name, payment.customer_last_name)
        : formatPersonName(payment.worker_first_name, payment.worker_last_name)
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    const matchesQuery = !query || searchableText.includes(query);
    const rawDateValue = payment.payment_date || payment.created_at || '';
    const recordDate = rawDateValue ? new Date(rawDateValue) : null;
    const recordDateKey = recordDate && !Number.isNaN(recordDate.getTime())
      ? recordDate.toISOString().slice(0, 10)
      : '';
    const matchesFromDate = !dateFrom || (recordDateKey && recordDateKey >= dateFrom);
    const matchesToDate = !dateTo || (recordDateKey && recordDateKey <= dateTo);

    return matchesStatus && matchesMethod && matchesQuery && matchesFromDate && matchesToDate;
  });
}

function createPaymentRecordingCard(state, helpers) {
  const { createInfoBanner } = helpers;
  const methods = state.routePaymentMethods || {};
  const candidates = state.cashierPendingCustomerBookings || [];

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-plus-circle"></i> Record Customer Collection
      </div>
      <div class="card-body desktop-card-body">
        ${createInfoBanner('Use this when a worker has already collected payment for a completed booking, but finance still needs to record it in the ledger.')}
        <form data-cashier-create-customer-payment="true">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Completed Booking</label>
              <select class="form-select" name="booking_id" ${candidates.length ? '' : 'disabled'}>
                ${createCandidateOptions(candidates, 'total_fee')}
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Payment Method</label>
              <select class="form-select" name="payment_method" ${Object.keys(methods).length ? '' : 'disabled'}>
                ${buildMethodOptions(methods)}
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Amount</label>
              <input
                type="number"
                class="form-control"
                name="amount"
                min="0"
                step="0.01"
                value="${candidates.length ? Number(candidates[0]?.total_fee || 0).toFixed(2) : ''}"
                ${candidates.length ? '' : 'disabled'}
              />
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="3" maxlength="500" placeholder="Optional note about the customer collection." ${candidates.length ? '' : 'disabled'}></textarea>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">${candidates.length} completed booking(s) still need a recorded customer collection.</small>
            <button type="submit" class="btn btn-primary" ${candidates.length && Object.keys(methods).length ? '' : 'disabled'}>
              Record Collection
            </button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createPayoutRecordingCard(state, helpers) {
  const { createInfoBanner } = helpers;
  const candidates = state.cashierPendingPayoutBookings || [];

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-hand-holding-usd"></i> Create Worker Payout Record
      </div>
      <div class="card-body desktop-card-body">
        ${createInfoBanner('Only completed bookings with a completed customer collection can move to worker payout processing.')}
        <form data-cashier-create-worker-payout="true">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Eligible Booking</label>
              <select class="form-select" name="booking_id" ${candidates.length ? '' : 'disabled'}>
                ${createCandidateOptions(candidates, 'worker_earnings')}
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Payment Method</label>
              <select class="form-select" name="payment_method" ${(candidates.length && Object.keys(state.routePaymentMethods || {}).length) ? '' : 'disabled'}>
                ${buildMethodOptions(state.routePaymentMethods || {})}
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Amount</label>
              <input
                type="number"
                class="form-control"
                name="amount"
                min="0"
                step="0.01"
                value="${candidates.length ? Number(candidates[0]?.worker_earnings || 0).toFixed(2) : ''}"
                ${candidates.length ? '' : 'disabled'}
              />
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="3" maxlength="500" placeholder="Optional note about this worker payout." ${candidates.length ? '' : 'disabled'}></textarea>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">${candidates.length} booking(s) are ready for worker payout processing.</small>
            <button type="submit" class="btn btn-success" ${candidates.length ? '' : 'disabled'}>
              Create Payout
            </button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createCashierProcessForm(selectedPayment) {
  if (!selectedPayment || String(selectedPayment.status || '') !== 'pending') {
    return '';
  }

  const isWorkerPayout = String(selectedPayment.payment_type || '') === 'worker_payout';
  const heading = isWorkerPayout ? 'Process Worker Payout' : 'Process Customer Collection';
  const buttonLabel = isWorkerPayout ? 'Complete Payout' : 'Complete Recording';

  return `
    <div class="mt-4">
      <h6 class="text-muted mb-3">${heading}</h6>
      <form data-cashier-process-payment="true" data-payment-id="${selectedPayment.id}">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Outcome</label>
            <select class="form-select" name="status">
              <option value="completed">Completed</option>
              <option value="failed">Failed</option>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Transaction ID</label>
            <input
              type="text"
              class="form-control"
              name="transaction_id"
              placeholder="Optional receipt, GCash reference, bank reference, or transfer code"
            />
          </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
          <button type="submit" class="btn btn-primary">${buttonLabel}</button>
        </div>
      </form>
    </div>
  `;
}

function createCashierPaymentDetails(state, helpers) {
  const { escapeHtml, formatCurrency, formatDate } = helpers;
  const selectedPayment = state.selectedPaymentDetails || null;

  if (!selectedPayment) {
    return `
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-eye"></i> Payment Details
        </div>
        <div class="card-body desktop-card-body">
          <div class="chart-placeholder desktop-placeholder-card">Select a payment row to view its full details and process any pending record.</div>
        </div>
      </div>
    `;
  }

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <span><i class="fas fa-eye"></i> Payment Details</span>
          <button type="button" class="ghost-button" data-cashier-print-receipt="true">Print Receipt</button>
        </div>
      </div>
      <div class="card-body desktop-card-body">
        <div class="desktop-insight-grid">
          <div class="desktop-insight-tile">
            <span>Reference</span>
            <strong>${escapeHtml(selectedPayment.payment_reference || selectedPayment.id || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Status</span>
            <strong>${escapeHtml(String(selectedPayment.status || 'pending').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Type</span>
            <strong>${escapeHtml(String(selectedPayment.payment_type || 'payment').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Amount</span>
            <strong>${formatCurrency(selectedPayment.amount || 0)}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Method</span>
            <strong>${escapeHtml(String(selectedPayment.payment_method || 'unassigned').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Booking</span>
            <strong>${escapeHtml(selectedPayment.booking_reference || selectedPayment.booking_id || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Booking Title</span>
            <strong>${escapeHtml(selectedPayment.booking_title || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Transaction ID</span>
            <strong>${escapeHtml(selectedPayment.transaction_id || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Paid By</span>
            <strong>${escapeHtml(formatPersonName(selectedPayment.paid_by_first_name, selectedPayment.paid_by_last_name))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Paid To</span>
            <strong>${escapeHtml(formatPersonName(selectedPayment.paid_to_first_name, selectedPayment.paid_to_last_name))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Processed By</span>
            <strong>${escapeHtml(formatPersonName(selectedPayment.processed_by_first_name, selectedPayment.processed_by_last_name))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Payment Date</span>
            <strong>${formatDate(selectedPayment.payment_date || selectedPayment.created_at)}</strong>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Notes</h6>
          <div class="border rounded p-3">${escapeHtml(selectedPayment.notes || 'No notes recorded for this payment.')}</div>
        </div>

        ${createCashierProcessForm(selectedPayment)}
      </div>
    </div>
  `;
}

function createPaymentActionButtons(payment) {
  const isPending = String(payment.status || '') === 'pending';
  const actionLabel = String(payment.payment_type || '') === 'worker_payout' ? 'Pay Out' : 'Record';

  return `
    <div class="d-flex gap-2">
      <button type="button" class="ghost-button table-action" data-payment-view-button="true" data-payment-id="${payment.id}">
        View
      </button>
      ${isPending ? `
        <button type="button" class="ghost-button table-action" data-payment-process-select="true" data-payment-id="${payment.id}">
          ${actionLabel}
        </button>
      ` : ''}
    </div>
  `;
}

function buildCashierReceiptHtml(payment) {
  const receiptTitle = String(payment?.payment_type || '') === 'worker_payout'
    ? 'Worker Payout Receipt'
    : 'Customer Collection Receipt';

  const amount = Number(payment?.amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
  const method = String(payment?.payment_method || 'unassigned').replace(/_/g, ' ');
  const status = String(payment?.status || 'pending').replace(/_/g, ' ');
  const booking = payment?.booking_reference || payment?.booking_id || '-';
  const reference = payment?.payment_reference || payment?.id || '-';
  const paidBy = formatPersonName(payment?.paid_by_first_name, payment?.paid_by_last_name);
  const paidTo = formatPersonName(payment?.paid_to_first_name, payment?.paid_to_last_name);
  const processedBy = formatPersonName(payment?.processed_by_first_name, payment?.processed_by_last_name);
  const paymentDate = payment?.payment_date || payment?.created_at || '';
  const notes = payment?.notes || 'No notes recorded.';

  return `<!DOCTYPE html>
  <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <title>${receiptTitle}</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #1f2937; }
        h1 { margin-bottom: 8px; }
        .muted { color: #6b7280; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .card { border: 1px solid #dbe3f0; border-radius: 12px; padding: 16px; }
        .label { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }
        .value { font-size: 20px; font-weight: 700; }
        .notes { border: 1px solid #dbe3f0; border-radius: 12px; padding: 16px; white-space: pre-wrap; }
      </style>
    </head>
    <body>
      <h1>${receiptTitle}</h1>
      <div class="muted">Generated from SkillLink Desktop Cashier</div>
      <div class="grid">
        <div class="card"><div class="label">Reference</div><div class="value">${reference}</div></div>
        <div class="card"><div class="label">Booking</div><div class="value">${booking}</div></div>
        <div class="card"><div class="label">Amount</div><div class="value">${amount}</div></div>
        <div class="card"><div class="label">Method</div><div class="value">${method}</div></div>
        <div class="card"><div class="label">Status</div><div class="value">${status}</div></div>
        <div class="card"><div class="label">Payment Date</div><div class="value">${paymentDate}</div></div>
        <div class="card"><div class="label">Paid By</div><div class="value">${paidBy}</div></div>
        <div class="card"><div class="label">Paid To</div><div class="value">${paidTo}</div></div>
        <div class="card"><div class="label">Processed By</div><div class="value">${processedBy}</div></div>
      </div>
      <div class="label">Notes</div>
      <div class="notes">${notes}</div>
      <script>window.onload = () => window.print();</script>
    </body>
  </html>`;
}

export function createCashierPaymentsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, formatCurrency, formatDate } = helpers;
  ensureCashierFilters(state);
  const payments = applyCashierFilters(state.routePayments || [], state.cashierFilters?.payments || {}, 'payments');
  const completed = payments.filter((payment) => String(payment.status || '') === 'completed');
  const pending = payments.filter((payment) => String(payment.status || '') === 'pending');

  return `
    ${createInfoBanner(state.routeNotice)}
    ${createMetricGrid([
      { icon: 'fas fa-receipt', value: payments.length, label: 'Customer Collections', tone: 'primary' },
      { icon: 'fas fa-hourglass-half', value: pending.length, label: 'Pending', tone: 'warning' },
      { icon: 'fas fa-check-circle', value: completed.length, label: 'Recorded', tone: 'success' },
      { icon: 'fas fa-peso-sign', value: completed.reduce((sum, payment) => sum + Number(payment.amount || 0), 0), label: 'Collected Amount', tone: 'info' }
    ])}

    ${createCashierFiltersCard('payments', state)}
    ${createPaymentRecordingCard(state, helpers)}

    <div class="desktop-admin-split mt-4">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-credit-card"></i> Customer Collection Ledger
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Reference</th>
                  <th>Booking</th>
                  <th>Customer</th>
                  <th>Method</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${payments.length ? payments.map((payment) => `
                  <tr
                    class="${Number(state.selectedPaymentId || 0) === Number(payment.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-payment-row="true"
                    data-payment-id="${payment.id}"
                  >
                    <td><strong>${payment.payment_reference || payment.transaction_id || payment.id}</strong></td>
                    <td>${payment.booking_reference || payment.booking_title || payment.booking_id || '-'}</td>
                    <td>${formatPersonName(payment.customer_first_name, payment.customer_last_name)}</td>
                    <td>${String(payment.payment_method || 'unassigned').replace(/_/g, ' ')}</td>
                    <td><span class="badge badge-${payment.status || 'pending'}">${String(payment.status || 'pending').replace(/_/g, ' ')}</span></td>
                    <td>${formatCurrency(payment.amount || 0)}</td>
                    <td>${formatDate(payment.payment_date || payment.created_at)}</td>
                    <td>${createPaymentActionButtons(payment)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">No customer collection records have been returned for this page yet.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createCashierPaymentDetails(state, helpers)}
      </div>
    </div>
  `;
}

export function createCashierPayoutsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, formatCurrency, formatDate } = helpers;
  ensureCashierFilters(state);
  const payouts = applyCashierFilters(state.routePayments || [], state.cashierFilters?.payouts || {}, 'payouts');
  const completed = payouts.filter((payment) => String(payment.status || '') === 'completed');
  const recordedPending = payouts.filter((payment) => String(payment.status || '') !== 'completed');
  const readyUnrecorded = state.cashierPendingPayoutBookings || [];
  const totalPendingWork = recordedPending.length + readyUnrecorded.length;

  return `
    ${createInfoBanner(state.routeNotice)}
    ${createMetricGrid([
      { icon: 'fas fa-hand-holding-usd', value: payouts.length, label: 'Payout Records', tone: 'primary' },
      { icon: 'fas fa-check-circle', value: completed.length, label: 'Paid Out', tone: 'success' },
      { icon: 'fas fa-hourglass-half', value: totalPendingWork, label: 'Pending Payouts', tone: 'warning', subtitle: readyUnrecorded.length ? `${readyUnrecorded.length} awaiting record` : 'All pending records shown' },
      { icon: 'fas fa-wallet', value: completed.reduce((sum, payout) => sum + Number(payout.amount || 0), 0), label: 'Total Released', tone: 'info' }
    ])}

    ${createCashierFiltersCard('payouts', state)}
    ${createPayoutRecordingCard(state, helpers)}

    <div class="desktop-admin-split mt-4">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-money-check-alt"></i> Worker Payout Ledger
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Reference</th>
                  <th>Booking</th>
                  <th>Worker</th>
                  <th>Status</th>
                  <th>Method</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${payouts.length ? payouts.map((payout) => `
                  <tr
                    class="${Number(state.selectedPaymentId || 0) === Number(payout.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-payment-row="true"
                    data-payment-id="${payout.id}"
                  >
                    <td><strong>${payout.payment_reference || payout.id}</strong></td>
                    <td>${payout.booking_reference || payout.booking_id || '-'}</td>
                    <td>${formatPersonName(payout.worker_first_name, payout.worker_last_name)}</td>
                    <td><span class="badge badge-${payout.status || 'pending'}">${String(payout.status || 'pending').replace(/_/g, ' ')}</span></td>
                    <td>${String(payout.payment_method || 'internal').replace(/_/g, ' ')}</td>
                    <td>${formatCurrency(payout.amount || 0)}</td>
                    <td>${formatDate(payout.payment_date || payout.created_at)}</td>
                    <td>${createPaymentActionButtons(payout)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">No worker payout records have been returned for this page yet.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createCashierPaymentDetails(state, helpers)}
      </div>
    </div>
  `;
}

export function createCashierReportsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, formatCurrency, formatDate, formatValue } = helpers;
  const stats = state.routePaymentStats || {};
  const report = state.routeRevenueReport || [];
  const analytics = createReportAnalytics(state);

  return `
    ${createInfoBanner(state.routeNotice)}
    ${createMetricGrid([
      { icon: 'fas fa-peso-sign', value: formatCurrency(stats.total_revenue ?? 0), label: 'Total Revenue', tone: 'success' },
      { icon: 'fas fa-calendar-day', value: stats.today_payments ?? 0, label: 'Today Payments', tone: 'info' },
      { icon: 'fas fa-chart-line', value: formatCurrency(stats.monthly_revenue ?? 0), label: 'Monthly Revenue', tone: 'primary' },
      { icon: 'fas fa-hourglass-half', value: stats.pending_payments ?? 0, label: 'Pending Payments', tone: 'warning' },
      { icon: 'fas fa-percentage', value: formatCurrency(analytics.totalCommission), label: 'Total Commission', tone: 'secondary' },
      { icon: 'fas fa-check-circle', value: analytics.completedJobs, label: 'Completed Jobs', tone: 'primary' }
    ])}

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-chart-bar"></i> Revenue Timeline</span>
            <div class="d-flex gap-2">
              <button type="button" class="ghost-button" data-cashier-export-report="csv">Export CSV</button>
              <button type="button" class="ghost-button" data-cashier-print-report="true">Print</button>
            </div>
          </div>
          <div class="card-body desktop-card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Revenue</th>
                    <th>Transactions</th>
                  </tr>
                </thead>
                <tbody>
                  ${report.length ? report.map((entry) => `
                    <tr>
                      <td><strong>${formatDate(entry.date)}</strong></td>
                      <td>${formatCurrency(entry.revenue || 0)}</td>
                      <td>${formatValue(entry.count || 0)}</td>
                    </tr>
                  `).join('') : `
                    <tr>
                      <td colspan="3" class="text-center text-muted py-4">No revenue report rows available yet.</td>
                    </tr>
                  `}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-chart-pie"></i> Payment Method Distribution
          </div>
          <div class="card-body desktop-card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Count</th>
                  </tr>
                </thead>
                <tbody>
                  ${analytics.paymentMethods.length ? analytics.paymentMethods.map((entry) => `
                    <tr>
                      <td>${String(entry.method || 'unknown').replace(/_/g, ' ')}</td>
                      <td>${formatValue(entry.count || 0)}</td>
                    </tr>
                  `).join('') : `
                    <tr>
                      <td colspan="2" class="text-center text-muted py-4">No payment method data yet.</td>
                    </tr>
                  `}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

async function hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest) {
  if (!state.selectedPaymentId) {
    state.selectedPaymentDetails = null;
    return;
  }

  state.paymentDetailsById = state.paymentDetailsById || {};

  if (!state.paymentDetailsById[state.selectedPaymentId]) {
    try {
      const selectedPaymentResponse = await performAuthenticatedRequest(session, bridge, `/api/payments/${state.selectedPaymentId}`, { method: 'GET' });
      state.paymentDetailsById[state.selectedPaymentId] = selectedPaymentResponse?.data || null;
    } catch {
      state.paymentDetailsById[state.selectedPaymentId] = null;
    }
  }

  state.selectedPaymentDetails = state.paymentDetailsById[state.selectedPaymentId] || null;
}

function computeMissingCustomerPaymentBookings(bookings, customerPayments) {
  const bookingIdsWithPayment = new Set(customerPayments.map((payment) => Number(payment.booking_id || 0)));
  return bookings.filter((booking) => !bookingIdsWithPayment.has(Number(booking.id || 0)));
}

function computeMissingPayoutBookings(bookings, payouts, completedCustomerPayments) {
  const bookingIdsWithPayout = new Set(payouts.map((payment) => Number(payment.booking_id || 0)));
  const bookingIdsWithCompletedCustomerPayment = new Set(
    completedCustomerPayments
      .filter((payment) => String(payment.status || '') === 'completed')
      .map((payment) => Number(payment.booking_id || 0))
  );

  return bookings.filter((booking) => (
    Boolean(Number(booking.worker_id || 0)) &&
    bookingIdsWithCompletedCustomerPayment.has(Number(booking.id || 0)) &&
    !bookingIdsWithPayout.has(Number(booking.id || 0))
  ));
}

function syncAmountInputFromSelection(form) {
  if (!form) {
    return;
  }

  const bookingSelect = form.querySelector('select[name="booking_id"]');
  const amountInput = form.querySelector('input[name="amount"]');
  if (!bookingSelect || !amountInput) {
    return;
  }

  const updateAmount = () => {
    const selectedOption = bookingSelect.selectedOptions?.[0];
    if (!selectedOption) {
      return;
    }

    const selectedAmount = selectedOption.getAttribute('data-amount');
    if (selectedAmount) {
      amountInput.value = selectedAmount;
    }
  };

  bookingSelect.addEventListener('change', updateAmount);
  updateAmount();
}

function confirmCashierAction(message) {
  if (typeof window.confirm !== 'function') {
    return true;
  }

  return window.confirm(message);
}

function ensureCashierRouteCache(state) {
  state.cashierRouteCache = state.cashierRouteCache || {
    payments: { dirty: true },
    payouts: { dirty: true },
    reports: { dirty: true }
  };
}

function applyCashierCacheToState(state, routeKey) {
  ensureCashierRouteCache(state);
  const cache = state.cashierRouteCache?.[routeKey] || null;
  if (!cache) {
    return false;
  }

  if (routeKey === 'payments') {
    state.routePayments = cache.routePayments || [];
    state.routePaymentStats = cache.routePaymentStats || {};
    state.routePaymentMethods = cache.routePaymentMethods || {};
    state.routeCompletedBookings = cache.routeCompletedBookings || [];
    state.cashierPendingCustomerBookings = cache.cashierPendingCustomerBookings || [];
    return true;
  }

  if (routeKey === 'payouts') {
    state.routePayments = cache.routePayments || [];
    state.routeCompletedBookings = cache.routeCompletedBookings || [];
    state.routeCustomerPayments = cache.routeCustomerPayments || [];
    state.routePaymentMethods = cache.routePaymentMethods || {};
    state.cashierPendingPayoutBookings = cache.cashierPendingPayoutBookings || [];
    return true;
  }

  if (routeKey === 'reports') {
    state.routePaymentStats = cache.routePaymentStats || {};
    state.routeRevenueReport = cache.routeRevenueReport || [];
    state.routeReportCompletedBookings = cache.routeReportCompletedBookings || [];
    state.routeReportCustomerPayments = cache.routeReportCustomerPayments || [];
    return true;
  }

  return false;
}

function updateCashierRouteCache(state, routeKey, payload) {
  ensureCashierRouteCache(state);
  state.cashierRouteCache[routeKey] = {
    ...(state.cashierRouteCache[routeKey] || {}),
    ...payload,
    dirty: false
  };
}

function markCashierRoutesDirty(state, routeKeys = []) {
  ensureCashierRouteCache(state);
  routeKeys.forEach((routeKey) => {
    state.cashierRouteCache[routeKey] = {
      ...(state.cashierRouteCache[routeKey] || {}),
      dirty: true
    };
  });
}

export async function loadCashierRouteData(state, session, bridge, performAuthenticatedRequest) {
  if (state.currentRoute === 'payments') {
    ensureCashierRouteCache(state);
    if (!state.cashierRouteCache.payments?.dirty && applyCashierCacheToState(state, 'payments')) {
      await hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest);
      return true;
    }

    const [paymentsResponse, statisticsResponse, methodsResponse, bookingsResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=customer_payment&limit=100', { method: 'GET' }),
      performAuthenticatedRequest(session, bridge, '/api/payments/statistics', { method: 'GET' }).catch(() => ({ data: {} })),
      performAuthenticatedRequest(session, bridge, '/api/payments/methods', { method: 'GET' }).catch(() => ({ data: {} })),
      performAuthenticatedRequest(session, bridge, '/api/bookings?status=completed&limit=100', { method: 'GET' }).catch(() => ({ data: [] }))
    ]);

    const paymentRows = paymentsResponse?.data || [];
    state.routePaymentStats = statisticsResponse?.data || {};
    state.routePaymentMethods = methodsResponse?.data || {};
    state.routeCompletedBookings = bookingsResponse?.data || [];
    const bookingDetailsMap = await fetchBookingDetailsMap(
      session,
      bridge,
      performAuthenticatedRequest,
      [
        ...state.routeCompletedBookings.map((booking) => booking.id),
        ...paymentRows.map((payment) => payment.booking_id)
      ]
    );
    state.routeCompletedBookings = state.routeCompletedBookings.map((booking) => ({
      ...booking,
      ...(bookingDetailsMap[Number(booking.id || 0)] || {})
    }));
    state.routePayments = mergeBookingDetailsIntoPayments(paymentRows, bookingDetailsMap);
    state.cashierPendingCustomerBookings = computeMissingCustomerPaymentBookings(state.routeCompletedBookings, state.routePayments);
    updateCashierRouteCache(state, 'payments', {
      routePayments: state.routePayments,
      routePaymentStats: state.routePaymentStats,
      routePaymentMethods: state.routePaymentMethods,
      routeCompletedBookings: state.routeCompletedBookings,
      cashierPendingCustomerBookings: state.cashierPendingCustomerBookings
    });

    await hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest);
    return true;
  }

  if (state.currentRoute === 'payouts') {
    ensureCashierRouteCache(state);
    if (!state.cashierRouteCache.payouts?.dirty && applyCashierCacheToState(state, 'payouts')) {
      await hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest);
      return true;
    }

    const [payoutsResponse, bookingsResponse, customerPaymentsResponse, methodsResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=worker_payout&limit=100', { method: 'GET' }),
      performAuthenticatedRequest(session, bridge, '/api/bookings?status=completed&limit=100', { method: 'GET' }).catch(() => ({ data: [] })),
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=customer_payment&limit=100', { method: 'GET' }).catch(() => ({ data: [] })),
      performAuthenticatedRequest(session, bridge, '/api/payments/methods', { method: 'GET' }).catch(() => ({ data: {} }))
    ]);

    const payoutRows = payoutsResponse?.data || [];
    state.routeCompletedBookings = bookingsResponse?.data || [];
    const customerPaymentRows = customerPaymentsResponse?.data || [];
    const bookingDetailsMap = await fetchBookingDetailsMap(
      session,
      bridge,
      performAuthenticatedRequest,
      [
        ...state.routeCompletedBookings.map((booking) => booking.id),
        ...payoutRows.map((payment) => payment.booking_id),
        ...customerPaymentRows.map((payment) => payment.booking_id)
      ]
    );
    state.routeCompletedBookings = state.routeCompletedBookings.map((booking) => ({
      ...booking,
      ...(bookingDetailsMap[Number(booking.id || 0)] || {})
    }));
    state.routePayments = mergeBookingDetailsIntoPayments(payoutRows, bookingDetailsMap);
    state.routeCustomerPayments = mergeBookingDetailsIntoPayments(customerPaymentRows, bookingDetailsMap);
    state.routePaymentMethods = methodsResponse?.data || {};
    state.cashierPendingPayoutBookings = computeMissingPayoutBookings(
      state.routeCompletedBookings,
      state.routePayments,
      state.routeCustomerPayments
    );
    updateCashierRouteCache(state, 'payouts', {
      routePayments: state.routePayments,
      routeCompletedBookings: state.routeCompletedBookings,
      routeCustomerPayments: state.routeCustomerPayments,
      routePaymentMethods: state.routePaymentMethods,
      cashierPendingPayoutBookings: state.cashierPendingPayoutBookings
    });

    await hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest);
    return true;
  }

  if (state.currentRoute === 'reports') {
    ensureCashierRouteCache(state);
    if (!state.cashierRouteCache.reports?.dirty && applyCashierCacheToState(state, 'reports')) {
      return true;
    }

    const [statisticsResponse, revenueResponse, completedBookingsResponse, completedCustomerPaymentsResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, '/api/payments/statistics', { method: 'GET' }),
      performAuthenticatedRequest(session, bridge, '/api/payments/revenue-report', { method: 'GET' }).catch(() => ({ data: [] })),
      performAuthenticatedRequest(session, bridge, '/api/bookings?status=completed&limit=100', { method: 'GET' }).catch(() => ({ data: [] })),
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=customer_payment&status=completed&limit=100', { method: 'GET' }).catch(() => ({ data: [] }))
    ]);

    state.routePaymentStats = statisticsResponse?.data || {};
    state.routeRevenueReport = revenueResponse?.data || [];
    const completedBookings = completedBookingsResponse?.data || [];
    const completedCustomerPayments = completedCustomerPaymentsResponse?.data || [];
    const bookingDetailsMap = await fetchBookingDetailsMap(
      session,
      bridge,
      performAuthenticatedRequest,
      [
        ...completedBookings.map((booking) => booking.id),
        ...completedCustomerPayments.map((payment) => payment.booking_id)
      ]
    );
    state.routeReportCompletedBookings = completedBookings.map((booking) => ({
      ...booking,
      ...(bookingDetailsMap[Number(booking.id || 0)] || {})
    }));
    state.routeReportCustomerPayments = mergeBookingDetailsIntoPayments(completedCustomerPayments, bookingDetailsMap);
    updateCashierRouteCache(state, 'reports', {
      routePaymentStats: state.routePaymentStats,
      routeRevenueReport: state.routeRevenueReport,
      routeReportCompletedBookings: state.routeReportCompletedBookings,
      routeReportCustomerPayments: state.routeReportCustomerPayments
    });
    return true;
  }

  return false;
}

async function loadPaymentDetails(state, session, bridge, paymentId, actions) {
  const { performAuthenticatedRequest, renderRoute, updateInlineStatus, setStatus } = actions;
  state.selectedPaymentId = paymentId;
  state.paymentDetailsById = state.paymentDetailsById || {};

  try {
    const response = await performAuthenticatedRequest(session, bridge, `/api/payments/${paymentId}`, { method: 'GET' });
    state.paymentDetailsById[paymentId] = response?.data || null;
    state.selectedPaymentDetails = state.paymentDetailsById[paymentId];
    await renderRoute(state, session, bridge);
    updateInlineStatus('Payment details loaded successfully.', 'success');
  } catch (error) {
    state.selectedPaymentDetails = null;
    updateInlineStatus(error.message || 'Failed to load payment details.', 'error');
    setStatus(error.message || 'Failed to load payment details.', 'error');
  }
}

export function bindCashierPaymentsView(state, session, bridge, actions) {
  const { performAuthenticatedRequest, renderRoute, setStatus, updateInlineStatus } = actions;
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement || state.role === 'customer') {
    return;
  }

  ensureCashierFilters(state);

  contentElement.querySelectorAll('[data-cashier-filter-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const routeKey = String(form.getAttribute('data-cashier-filter-form') || '');
      if (!routeKey || !['payments', 'payouts'].includes(routeKey)) {
        return;
      }

      const formData = new FormData(form);
      state.cashierFilters = state.cashierFilters || getDefaultCashierFilters();
      state.cashierFilters[routeKey] = {
        q: String(formData.get('q') || '').trim(),
        status: String(formData.get('status') || '').trim(),
        method: String(formData.get('method') || '').trim(),
        date_from: String(formData.get('date_from') || '').trim(),
        date_to: String(formData.get('date_to') || '').trim()
      };
      await renderRoute(state, session, bridge);
      updateInlineStatus('Cashier filters applied.', 'success');
    });
  });

  contentElement.querySelectorAll('[data-cashier-filter-reset]').forEach((button) => {
    button.addEventListener('click', async () => {
      const routeKey = String(button.getAttribute('data-cashier-filter-reset') || '');
      if (!routeKey || !['payments', 'payouts'].includes(routeKey)) {
        return;
      }

      state.cashierFilters = state.cashierFilters || getDefaultCashierFilters();
      state.cashierFilters[routeKey] = { ...getDefaultCashierFilters()[routeKey] };
      await renderRoute(state, session, bridge);
      updateInlineStatus('Cashier filters reset.', 'success');
    });
  });

  contentElement.querySelectorAll('[data-payment-row]').forEach((row) => {
    row.addEventListener('click', async () => {
      const paymentId = Number(row.getAttribute('data-payment-id'));
      if (!paymentId) {
        return;
      }

      await loadPaymentDetails(state, session, bridge, paymentId, actions);
    });
  });

  contentElement.querySelectorAll('[data-payment-view-button], [data-payment-process-select]').forEach((button) => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const paymentId = Number(button.getAttribute('data-payment-id'));
      if (!paymentId) {
        return;
      }

      await loadPaymentDetails(state, session, bridge, paymentId, actions);
    });
  });

  const createCustomerPaymentForm = contentElement.querySelector('[data-cashier-create-customer-payment]');
  syncAmountInputFromSelection(createCustomerPaymentForm);
  createCustomerPaymentForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(createCustomerPaymentForm);
    const bookingId = Number(formData.get('booking_id'));
    const paymentMethod = String(formData.get('payment_method') || '');
    const amount = Number(formData.get('amount'));
    const notes = String(formData.get('notes') || '').trim();

    if (!bookingId || !paymentMethod || !Number.isFinite(amount) || amount <= 0) {
      updateInlineStatus('Choose a completed booking, payment method, and valid amount first.', 'error');
      return;
    }

    const confirmed = confirmCashierAction(
      `Record this customer collection for ${amount.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}?`
    );
    if (!confirmed) {
      return;
    }

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/payments/customer', {
        method: 'POST',
        body: {
          booking_id: bookingId,
          payment_method: paymentMethod,
          amount,
          notes,
          processed_by: state.profile?.id || session.user?.id || null
        }
      });

      state.selectedPaymentId = response?.data?.id || null;
      markCashierRoutesDirty(state, ['payments', 'payouts', 'reports']);
      if (state.selectedPaymentId) {
        state.paymentDetailsById = state.paymentDetailsById || {};
        state.paymentDetailsById[state.selectedPaymentId] = response.data;
      }
      await renderRoute(state, session, bridge);
      updateInlineStatus(response?.message || 'Customer collection recorded successfully.', 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to record the customer collection.', 'error');
      setStatus(error.message || 'Failed to record the customer collection.', 'error');
    }
  });

  const createWorkerPayoutForm = contentElement.querySelector('[data-cashier-create-worker-payout]');
  syncAmountInputFromSelection(createWorkerPayoutForm);
  createWorkerPayoutForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(createWorkerPayoutForm);
    const bookingId = Number(formData.get('booking_id'));
    const paymentMethod = String(formData.get('payment_method') || '');
    const amount = Number(formData.get('amount'));
    const notes = String(formData.get('notes') || '').trim();

    if (!bookingId || !paymentMethod || !Number.isFinite(amount) || amount <= 0) {
      updateInlineStatus('Choose a ready booking, payout method, and valid amount first.', 'error');
      return;
    }

    const confirmed = confirmCashierAction(
      `Create this worker payout for ${amount.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}?`
    );
    if (!confirmed) {
      return;
    }

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/payments/worker', {
        method: 'POST',
        body: {
          booking_id: bookingId,
          payment_method: paymentMethod,
          amount,
          notes,
          processed_by: state.profile?.id || session.user?.id || null
        }
      });

      state.selectedPaymentId = response?.data?.id || null;
      markCashierRoutesDirty(state, ['payments', 'payouts', 'reports']);
      if (state.selectedPaymentId) {
        state.paymentDetailsById = state.paymentDetailsById || {};
        state.paymentDetailsById[state.selectedPaymentId] = response.data;
      }
      await renderRoute(state, session, bridge);
      updateInlineStatus(response?.message || 'Worker payout record created successfully.', 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to create the worker payout record.', 'error');
      setStatus(error.message || 'Failed to create the worker payout record.', 'error');
    }
  });

  const processPaymentForm = contentElement.querySelector('[data-cashier-process-payment]');
  processPaymentForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const paymentId = Number(processPaymentForm.getAttribute('data-payment-id'));
    if (!paymentId) {
      updateInlineStatus('This payment could not be processed because its ID is missing.', 'error');
      return;
    }

    const formData = new FormData(processPaymentForm);
    const status = String(formData.get('status') || '');
    const transactionId = String(formData.get('transaction_id') || '').trim();
    const selectedPayment = state.selectedPaymentDetails || null;
    const actionTarget = selectedPayment?.payment_type === 'worker_payout'
      ? 'worker payout'
      : 'customer collection';

    const confirmed = confirmCashierAction(
      `Mark this ${actionTarget} as ${status.replace(/_/g, ' ')}?`
    );
    if (!confirmed) {
      return;
    }

    try {
      const response = await performAuthenticatedRequest(session, bridge, `/api/payments/${paymentId}/process`, {
        method: 'PUT',
        body: {
          status,
          transaction_id: transactionId || null,
          processed_by: state.profile?.id || session.user?.id || null
        }
      });

      state.selectedPaymentId = paymentId;
      markCashierRoutesDirty(state, ['payments', 'payouts', 'reports']);
      state.paymentDetailsById = state.paymentDetailsById || {};
      state.paymentDetailsById[paymentId] = response?.data || null;
      state.selectedPaymentDetails = response?.data || null;
      await renderRoute(state, session, bridge);
      updateInlineStatus(response?.message || 'Cashier action completed successfully.', 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to complete this cashier action.', 'error');
      setStatus(error.message || 'Failed to complete this cashier action.', 'error');
    }
  });

  const exportReportButton = contentElement.querySelector('[data-cashier-export-report="csv"]');
  exportReportButton?.addEventListener('click', () => {
    const rows = state.routeRevenueReport || [];
    const header = ['Date', 'Revenue', 'Transactions'];
    const body = rows.map((entry) => [
      entry.date || '',
      Number(entry.revenue || 0),
      Number(entry.count || 0)
    ]);
    const csv = [header, ...body]
      .map((row) => row.map((value) => `"${String(value).replace(/"/g, '""')}"`).join(','))
      .join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'cashier-financial-report.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    updateInlineStatus('Cashier report exported as CSV.', 'success');
  });

  const printReportButton = contentElement.querySelector('[data-cashier-print-report="true"]');
  printReportButton?.addEventListener('click', () => {
    window.print();
    updateInlineStatus('Print dialog opened for the cashier report.', 'success');
  });

  const printReceiptButton = contentElement.querySelector('[data-cashier-print-receipt="true"]');
  printReceiptButton?.addEventListener('click', () => {
    const selectedPayment = state.selectedPaymentDetails || null;
    if (!selectedPayment) {
      updateInlineStatus('Select a payment or payout first before printing a receipt.', 'error');
      return;
    }

    const receiptWindow = window.open('', '_blank', 'width=900,height=700');
    if (!receiptWindow) {
      updateInlineStatus('The receipt window was blocked. Please allow pop-ups and try again.', 'error');
      return;
    }

    receiptWindow.document.open();
    receiptWindow.document.write(buildCashierReceiptHtml(selectedPayment));
    receiptWindow.document.close();
    updateInlineStatus('Receipt print view opened successfully.', 'success');
  });
}
