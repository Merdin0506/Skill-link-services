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
    <option value="${booking.id}">${createBookingOptionLabel(booking, amountKey)}</option>
  `).join('');
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
            <div class="col-md-7">
              <label class="form-label">Completed Booking</label>
              <select class="form-select" name="booking_id" ${candidates.length ? '' : 'disabled'}>
                ${createCandidateOptions(candidates, 'total_fee')}
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Payment Method</label>
              <select class="form-select" name="payment_method" ${Object.keys(methods).length ? '' : 'disabled'}>
                ${buildMethodOptions(methods)}
              </select>
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
            <div class="col-md-12">
              <label class="form-label">Eligible Booking</label>
              <select class="form-select" name="booking_id" ${candidates.length ? '' : 'disabled'}>
                ${createCandidateOptions(candidates, 'worker_earnings')}
              </select>
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
        <i class="fas fa-eye"></i> Payment Details
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

export function createCashierPaymentsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, formatCurrency, formatDate } = helpers;
  const payments = state.routePayments || [];
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
                    <td>${String(payment.payment_method || 'unassigned').replace(/_/g, ' ')}</td>
                    <td><span class="badge badge-${payment.status || 'pending'}">${String(payment.status || 'pending').replace(/_/g, ' ')}</span></td>
                    <td>${formatCurrency(payment.amount || 0)}</td>
                    <td>${formatDate(payment.payment_date || payment.created_at)}</td>
                    <td>${createPaymentActionButtons(payment)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">No customer collection records have been returned for this page yet.</td>
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
  const payouts = state.routePayments || [];
  const completed = payouts.filter((payment) => String(payment.status || '') === 'completed');

  return `
    ${createInfoBanner(state.routeNotice)}
    ${createMetricGrid([
      { icon: 'fas fa-hand-holding-usd', value: payouts.length, label: 'Payout Records', tone: 'primary' },
      { icon: 'fas fa-check-circle', value: completed.length, label: 'Paid Out', tone: 'success' },
      { icon: 'fas fa-hourglass-half', value: payouts.length - completed.length, label: 'Pending Payouts', tone: 'warning' },
      { icon: 'fas fa-wallet', value: completed.reduce((sum, payout) => sum + Number(payout.amount || 0), 0), label: 'Total Released', tone: 'info' }
    ])}

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
                    <td><span class="badge badge-${payout.status || 'pending'}">${String(payout.status || 'pending').replace(/_/g, ' ')}</span></td>
                    <td>${String(payout.payment_method || 'internal').replace(/_/g, ' ')}</td>
                    <td>${formatCurrency(payout.amount || 0)}</td>
                    <td>${formatDate(payout.payment_date || payout.created_at)}</td>
                    <td>${createPaymentActionButtons(payout)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">No worker payout records have been returned for this page yet.</td>
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

  return `
    ${createInfoBanner(state.routeNotice)}
    ${createMetricGrid([
      { icon: 'fas fa-peso-sign', value: stats.total_revenue ?? 0, label: 'Total Revenue', tone: 'success' },
      { icon: 'fas fa-calendar-day', value: stats.today_payments ?? 0, label: 'Today Payments', tone: 'info' },
      { icon: 'fas fa-chart-line', value: stats.monthly_revenue ?? 0, label: 'Monthly Revenue', tone: 'primary' },
      { icon: 'fas fa-hourglass-half', value: stats.pending_payments ?? 0, label: 'Pending Payments', tone: 'warning' }
    ])}

    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-chart-bar"></i> Revenue Timeline
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

export async function loadCashierRouteData(state, session, bridge, performAuthenticatedRequest) {
  if (state.currentRoute === 'payments') {
    const [paymentsResponse, statisticsResponse, methodsResponse, bookingsResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=customer_payment&limit=100', { method: 'GET' }),
      performAuthenticatedRequest(session, bridge, '/api/payments/statistics', { method: 'GET' }).catch(() => ({ data: {} })),
      performAuthenticatedRequest(session, bridge, '/api/payments/methods', { method: 'GET' }).catch(() => ({ data: {} })),
      performAuthenticatedRequest(session, bridge, '/api/bookings?status=completed&limit=100', { method: 'GET' }).catch(() => ({ data: [] }))
    ]);

    state.routePayments = paymentsResponse?.data || [];
    state.routePaymentStats = statisticsResponse?.data || {};
    state.routePaymentMethods = methodsResponse?.data || {};
    state.routeCompletedBookings = bookingsResponse?.data || [];
    state.cashierPendingCustomerBookings = computeMissingCustomerPaymentBookings(state.routeCompletedBookings, state.routePayments);

    await hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest);
    return true;
  }

  if (state.currentRoute === 'payouts') {
    const [payoutsResponse, bookingsResponse, customerPaymentsResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=worker_payout&limit=100', { method: 'GET' }),
      performAuthenticatedRequest(session, bridge, '/api/bookings?status=completed&limit=100', { method: 'GET' }).catch(() => ({ data: [] })),
      performAuthenticatedRequest(session, bridge, '/api/payments?payment_type=customer_payment&limit=100', { method: 'GET' }).catch(() => ({ data: [] }))
    ]);

    state.routePayments = payoutsResponse?.data || [];
    state.routeCompletedBookings = bookingsResponse?.data || [];
    state.routeCustomerPayments = customerPaymentsResponse?.data || [];
    state.cashierPendingPayoutBookings = computeMissingPayoutBookings(
      state.routeCompletedBookings,
      state.routePayments,
      state.routeCustomerPayments
    );

    await hydrateSelectedPaymentDetails(state, session, bridge, performAuthenticatedRequest);
    return true;
  }

  if (state.currentRoute === 'reports') {
    const [statisticsResponse, revenueResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, '/api/payments/statistics', { method: 'GET' }),
      performAuthenticatedRequest(session, bridge, '/api/payments/revenue-report', { method: 'GET' }).catch(() => ({ data: [] }))
    ]);

    state.routePaymentStats = statisticsResponse?.data || {};
    state.routeRevenueReport = revenueResponse?.data || [];
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
  createCustomerPaymentForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(createCustomerPaymentForm);
    const bookingId = Number(formData.get('booking_id'));
    const paymentMethod = String(formData.get('payment_method') || '');

    if (!bookingId || !paymentMethod) {
      updateInlineStatus('Choose a completed booking and a payment method first.', 'error');
      return;
    }

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/payments/customer', {
        method: 'POST',
        body: {
          booking_id: bookingId,
          payment_method: paymentMethod,
          processed_by: state.profile?.id || session.user?.id || null
        }
      });

      state.selectedPaymentId = response?.data?.id || null;
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
  createWorkerPayoutForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(createWorkerPayoutForm);
    const bookingId = Number(formData.get('booking_id'));

    if (!bookingId) {
      updateInlineStatus('Choose a booking that is ready for payout first.', 'error');
      return;
    }

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/payments/worker', {
        method: 'POST',
        body: {
          booking_id: bookingId,
          processed_by: state.profile?.id || session.user?.id || null
        }
      });

      state.selectedPaymentId = response?.data?.id || null;
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
}
