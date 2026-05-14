import { getElementById } from '../../core/dom.js';
import { clearSession, getSession, saveSession } from '../../core/storage.js';
import { setStatus } from '../../core/status.js';
import { requestJson } from '../../services/apiClient.js';
import { getDesktopBridge } from '../../services/desktopBridge.js';
import { requestOtpCode } from '../auth/otpDialog.js';
import { getRoleTemplate } from './roleTemplates.js';
import {
  bindCashierPaymentsView,
  createCashierPaymentsView,
  createCashierPayoutsView,
  createCashierReportsView,
  loadCashierRouteData
} from './cashierWorkspace.js';
import {
  bindAdminBookingsView,
  createAdminBookingsView,
  loadAdminBookingsRouteData
} from './adminBookingsWorkspace.js';
import {
  bindAdminReviewsView,
  createAdminReviewsView,
  loadAdminReviewsRouteData,
  getDefaultAdminReviewFilters
} from './adminReviewsWorkspace.js';
import {
  bindAdminRecordsView,
  createAdminRecordsView,
  loadAdminRecordsRouteData,
  getDefaultAdminRecordFilters
} from './adminRecordsWorkspace.js';
import {
  bindAdminBackupsView,
  createAdminBackupsView,
  loadAdminBackupsRouteData
} from './adminBackupsWorkspace.js';
import {
  bindAdminUserAnalyticsView,
  createAdminUserAnalyticsView,
  loadAdminUserAnalyticsRouteData,
  getDefaultAdminUserAnalyticsFilters
} from './adminUserAnalyticsWorkspace.js';
import {
  bindAdminSecurityView,
  createAdminSecurityView,
  loadAdminSecurityRouteData,
  getDefaultAdminSecurityFilters
} from './adminSecurityWorkspace.js';
import { bindServicesView as bindServicesWorkspaceView, createServicesView as createServicesWorkspaceView, getDefaultServiceFilters } from './servicesWorkspace.js';

function formatValue(value) {
  if (value === null || value === undefined || value === '') {
    return '-';
  }

  if (typeof value === 'number') {
    return value.toLocaleString();
  }

  return String(value);
}

function formatCurrency(value) {
  const numericValue = Number(value || 0);
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(Number.isFinite(numericValue) ? numericValue : 0);
}

function formatDate(value) {
  const date = new Date(value || Date.now());
  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric'
  });
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatRelativeCount(value) {
  const numericValue = Number(value || 0);
  return Number.isFinite(numericValue) ? numericValue.toLocaleString() : '0';
}

function getSeverityTone(value) {
  const severity = String(value || '').toLowerCase();
  if (['critical', 'high', 'danger'].includes(severity)) {
    return 'danger';
  }

  if (['medium', 'warning'].includes(severity)) {
    return 'warning';
  }

  if (['low', 'info'].includes(severity)) {
    return 'info';
  }

  return 'secondary';
}

function getFinancePaymentMethodLabel(record) {
  const paymentMethod = String(record?.payment_method || '').trim();
  if (paymentMethod) {
    return paymentMethod.replace(/_/g, ' ');
  }

  if (Number(record?.payment_id || 0)) {
    return 'Method not set';
  }

  return 'Not recorded';
}

function getFinancePaymentStatusLabel(record) {
  const paymentStatus = String(record?.payment_status || '').trim();
  if (paymentStatus) {
    return paymentStatus.replace(/_/g, ' ');
  }

  if (Number(record?.payment_id || 0)) {
    return 'pending';
  }

  return 'not recorded';
}

function getDefaultFinanceDashboardFilters() {
  return {
    q: '',
    status: '',
    method: '',
    date_from: '',
    date_to: ''
  };
}

function ensureFinanceDashboardFilters(state) {
  state.financeDashboardFilters = {
    ...getDefaultFinanceDashboardFilters(),
    ...(state.financeDashboardFilters || {})
  };
}

function applyFinanceDashboardFilters(rows = [], filters = {}) {
  const query = String(filters.q || '').trim().toLowerCase();
  const status = String(filters.status || '').trim().toLowerCase();
  const method = String(filters.method || '').trim().toLowerCase();
  const dateFrom = String(filters.date_from || '').trim();
  const dateTo = String(filters.date_to || '').trim();

  return (rows || []).filter((row) => {
    const paymentStatus = String(row.payment_status || '').toLowerCase();
    const paymentMethod = String(row.payment_method || '').toLowerCase();
    const matchesStatus = !status || paymentStatus === status;
    const matchesMethod = !method || paymentMethod === method;
    const searchableText = [
      row.payment_reference,
      row.booking_reference,
      row.title,
      row.customer_first_name,
      row.customer_last_name,
      row.worker_first_name,
      row.worker_last_name
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();
    const matchesQuery = !query || searchableText.includes(query);
    const rawDateValue = row.transaction_created_at || row.created_at || '';
    const recordDate = rawDateValue ? new Date(rawDateValue) : null;
    const recordDateKey = recordDate && !Number.isNaN(recordDate.getTime())
      ? recordDate.toISOString().slice(0, 10)
      : '';
    const matchesFromDate = !dateFrom || (recordDateKey && recordDateKey >= dateFrom);
    const matchesToDate = !dateTo || (recordDateKey && recordDateKey <= dateTo);

    return matchesStatus && matchesMethod && matchesQuery && matchesFromDate && matchesToDate;
  });
}

function createFinanceDashboardFilterCard(state) {
  ensureFinanceDashboardFilters(state);
  const filters = state.financeDashboardFilters || getDefaultFinanceDashboardFilters();

  return `
    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> Filter Recent Transactions
      </div>
      <div class="card-body desktop-card-body">
        <form data-finance-dashboard-filter="true">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Search</label>
              <input
                type="search"
                class="form-control"
                name="q"
                value="${escapeHtml(filters.q || '')}"
                placeholder="Reference, booking, customer, or worker"
              />
            </div>
            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="">All statuses</option>
                <option value="pending" ${filters.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="completed" ${filters.status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="failed" ${filters.status === 'failed' ? 'selected' : ''}>Failed</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Method</label>
              <select class="form-select" name="method">
                <option value="">All methods</option>
                <option value="cash" ${filters.method === 'cash' ? 'selected' : ''}>Cash</option>
                <option value="gcash" ${filters.method === 'gcash' ? 'selected' : ''}>GCash</option>
                <option value="paymaya" ${filters.method === 'paymaya' ? 'selected' : ''}>PayMaya</option>
                <option value="bank_transfer" ${filters.method === 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
                <option value="credit_card" ${filters.method === 'credit_card' ? 'selected' : ''}>Credit Card</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">From Date</label>
              <input type="date" class="form-control" name="date_from" value="${escapeHtml(filters.date_from || '')}" />
            </div>
            <div class="col-md-2">
              <label class="form-label">To Date</label>
              <input type="date" class="form-control" name="date_to" value="${escapeHtml(filters.date_to || '')}" />
            </div>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" data-finance-dashboard-filter-reset="true">Reset</button>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createFinanceDashboardSummaryCards(transactions = []) {
  const completed = transactions.filter((item) => String(item.payment_status || '') === 'completed');
  const pending = transactions.filter((item) => !item.payment_id || String(item.payment_status || '') === 'pending');
  const totalAmount = transactions.reduce((sum, item) => sum + Number(item.amount ?? item.total_fee ?? 0), 0);
  const completedAmount = completed.reduce((sum, item) => sum + Number(item.amount ?? item.total_fee ?? 0), 0);

  return `
    <div class="row mb-4">
      ${createStatCard({ icon: 'fas fa-list', value: transactions.length, label: 'Visible Transactions', tone: 'primary' })}
      ${createStatCard({ icon: 'fas fa-check-circle', value: completed.length, label: 'Visible Completed', tone: 'success' })}
      ${createStatCard({ icon: 'fas fa-hourglass-half', value: pending.length, label: 'Visible Pending', tone: 'warning' })}
      ${createStatCard({ icon: 'fas fa-peso-sign', value: formatCurrency(totalAmount), label: 'Visible Amount', tone: 'info' })}
      ${createStatCard({ icon: 'fas fa-wallet', value: formatCurrency(completedAmount), label: 'Completed Amount', tone: 'success' })}
    </div>
  `;
}

function createPageHeading(icon, title) {
  return `
    <div class="row mb-4">
      <div class="col-12">
        <h3 class="mb-0"><i class="${icon}"></i> ${title}</h3>
      </div>
    </div>
  `;
}

function createStatCard({ icon, value, label, tone = 'primary', subtitle = '' }) {
  return `
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="stat-card desktop-stat-card ${tone}">
        <i class="${icon} stat-icon"></i>
        <div class="stat-value">${formatValue(value)}</div>
        <div class="stat-label">${label}</div>
        ${subtitle ? `<small class="text-muted">${subtitle}</small>` : ''}
      </div>
    </div>
  `;
}

function buildRoleStatCards(role, stats) {
  const roleStatMap = {
    super_admin: [
      { icon: 'fas fa-users', value: stats.total_users ?? 0, label: 'Total Users', tone: 'primary' },
      { icon: 'fas fa-calendar-check', value: stats.total_bookings ?? 0, label: 'Total Bookings', tone: 'success' },
      { icon: 'fas fa-peso-sign', value: stats.total_revenue ?? 0, label: 'Total Revenue', tone: 'warning' },
      { icon: 'fas fa-user-tie', value: stats.active_workers ?? 0, label: 'Active Workers', tone: 'info' },
      { icon: 'fas fa-hourglass-half', value: stats.pending_bookings ?? 0, label: 'Pending Bookings', tone: 'danger' },
      { icon: 'fas fa-check-circle', value: stats.completed_bookings ?? 0, label: 'Completed Bookings', tone: 'success' }
    ],
    admin: [
      { icon: 'fas fa-users', value: stats.total_users ?? 0, label: 'Total Users', tone: 'primary' },
      { icon: 'fas fa-calendar-check', value: stats.total_bookings ?? 0, label: 'Total Bookings', tone: 'success' },
      { icon: 'fas fa-peso-sign', value: stats.total_revenue ?? 0, label: 'Total Revenue', tone: 'warning' },
      { icon: 'fas fa-user-tie', value: stats.active_workers ?? 0, label: 'Active Workers', tone: 'info' },
      { icon: 'fas fa-hourglass-half', value: stats.pending_bookings ?? 0, label: 'Pending Bookings', tone: 'danger' },
      { icon: 'fas fa-check-circle', value: stats.completed_bookings ?? 0, label: 'Completed Bookings', tone: 'success' }
    ],
    finance: [
      { icon: 'fas fa-peso-sign', value: stats.total_collected ?? 0, label: 'Total Revenue', tone: 'success', subtitle: 'All completed bookings' },
      { icon: 'fas fa-check-circle', value: stats.completed_payments ?? 0, label: 'Recorded Payments', tone: 'info', subtitle: 'Worker-collected' },
      { icon: 'fas fa-hourglass-half', value: stats.pending_payments ?? 0, label: 'Pending Payments', tone: 'warning', subtitle: 'Needs recording' },
      { icon: 'fas fa-hand-holding-usd', value: stats.pending_payouts ?? 0, label: 'Pending Worker Payouts', tone: 'danger', subtitle: 'Action required' }
    ],
    worker: [
      { icon: 'fas fa-briefcase', value: stats.available_bookings ?? 0, label: 'Available Jobs', tone: 'primary' },
      { icon: 'fas fa-check-circle', value: stats.assigned_bookings ?? 0, label: 'Assigned Jobs', tone: 'success' },
      { icon: 'fas fa-spinner', value: stats.in_progress_bookings ?? 0, label: 'In Progress', tone: 'warning' },
      { icon: 'fas fa-tasks', value: stats.completed_jobs ?? 0, label: 'Completed Jobs', tone: 'info' },
      { icon: 'fas fa-wallet', value: stats.total_earnings ?? 0, label: 'Total Earnings', tone: 'success' },
      { icon: 'fas fa-star', value: `${formatValue(stats.average_rating ?? 0)}/5`, label: 'Average Rating', tone: 'primary' }
    ],
    customer: [
      { icon: 'fas fa-calendar-check', value: stats.active_bookings ?? 0, label: 'Active Bookings', tone: 'primary' },
      { icon: 'fas fa-hourglass-half', value: stats.pending_bookings ?? 0, label: 'Pending Bookings', tone: 'warning' },
      { icon: 'fas fa-check-circle', value: stats.completed_bookings ?? 0, label: 'Completed Bookings', tone: 'success' },
      { icon: 'fas fa-book', value: stats.total_bookings ?? 0, label: 'Total Bookings', tone: 'info' },
      { icon: 'fas fa-peso-sign', value: stats.total_spent ?? 0, label: 'Total Spent', tone: 'success' },
      { icon: 'fas fa-star', value: `${formatValue(stats.average_rating_given ?? 0)}/5`, label: 'Avg. Rating Given', tone: 'primary' }
    ]
  };

  return (roleStatMap[role] || roleStatMap.customer).map((card) => createStatCard(card)).join('');
}

function renderTableRows(bookings, role) {
  const rows = bookings || [];
  if (!rows.length) {
    const colspan = role === 'finance' ? 7 : 6;
    return `
      <tr>
        <td colspan="${colspan}" class="text-center text-muted py-4">
          <i class="fas fa-inbox fa-2x mb-2"></i>
          <p class="mb-0">No records yet.</p>
        </td>
      </tr>
    `;
  }

  return rows.map((booking) => {
    const reference = booking.booking_reference || booking.payment_reference || 'N/A';
    const title = booking.title || 'N/A';
    const status = String(booking.status || booking.payment_status || 'pending');
    const statusClass = `badge-${status}`;

    if (role === 'finance') {
      const financeStatusClass = Number(booking.payment_id || 0)
        ? `badge-${String(booking.payment_status || 'pending')}`
        : 'badge-secondary';

      return `
        <tr>
          <td><strong>#${booking.payment_reference || reference}</strong></td>
          <td>${reference}</td>
          <td>${formatCurrency(booking.amount ?? booking.total_fee ?? 0)}</td>
          <td><span class="badge bg-secondary">${getFinancePaymentMethodLabel(booking)}</span></td>
          <td><span class="badge ${financeStatusClass}">${getFinancePaymentStatusLabel(booking)}</span></td>
          <td>${formatDate(booking.transaction_created_at || booking.created_at)}</td>
          <td>
            <button
              type="button"
              class="ghost-button table-action"
              data-dashboard-payment-view="true"
              data-transaction-id="${booking.id || ''}"
            >
              View
            </button>
          </td>
        </tr>
      `;
    }

    if (role === 'worker') {
      return `
        <tr>
          <td><strong>${reference}</strong></td>
          <td>${title}</td>
          <td><span class="badge ${statusClass}">${status.replace(/_/g, ' ')}</span></td>
          <td>${formatDate(booking.scheduled_date || booking.created_at)}</td>
          <td>${formatCurrency(booking.worker_earnings ?? 0)}</td>
          <td><button type="button" class="ghost-button table-action" disabled>View</button></td>
        </tr>
      `;
    }

    if (role === 'customer') {
      return `
        <tr>
          <td><strong>${reference}</strong></td>
          <td>${title}</td>
          <td><span class="badge ${statusClass}">${status.replace(/_/g, ' ')}</span></td>
          <td>${formatDate(booking.scheduled_date || booking.created_at)}</td>
          <td>${formatCurrency(booking.total_fee ?? 0)}</td>
          <td><button type="button" class="ghost-button table-action" disabled>View</button></td>
        </tr>
      `;
    }

    return `
      <tr>
        <td><strong>${reference}</strong></td>
        <td>${title}</td>
        <td><span class="badge ${statusClass}">${status.replace(/_/g, ' ')}</span></td>
        <td><span class="badge bg-${(booking.priority || 'low') === 'urgent' ? 'danger' : (booking.priority || 'low') === 'high' ? 'warning' : 'secondary'}">${booking.priority || 'low'}</span></td>
        <td>${formatCurrency(booking.total_fee ?? 0)}</td>
        <td>${formatDate(booking.created_at)}</td>
      </tr>
    `;
  }).join('');
}

function createFinanceDashboardDetailCard(state) {
  const selectedTransactionId = Number(state.selectedDashboardTransactionId || 0);
  const transaction = (state.bookings || []).find((item) => Number(item.id) === selectedTransactionId) || null;

  if (!transaction) {
    return '';
  }

  const paymentStatus = getFinancePaymentStatusLabel(transaction);
  const bookingStatus = String(transaction.booking_status || transaction.status || 'pending').replace(/_/g, ' ');
  const paymentMethod = getFinancePaymentMethodLabel(transaction);

  return `
    <div class="row mt-4">
      <div class="col-lg-12">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-eye"></i> Transaction Booking Details</span>
            <button type="button" class="ghost-button" id="financeDashboardDetailCloseButton">Close</button>
          </div>
          <div class="card-body desktop-card-body">
            <div class="desktop-insight-grid">
              <div class="desktop-insight-tile">
                <span>Transaction ID</span>
                <strong>${escapeHtml(transaction.payment_reference || transaction.booking_reference || transaction.id || '-')}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Booking Reference</span>
                <strong>${escapeHtml(transaction.booking_reference || '-')}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Booking Title</span>
                <strong>${escapeHtml(transaction.title || '-')}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Amount</span>
                <strong>${formatCurrency(transaction.amount ?? transaction.total_fee ?? 0)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Payment Method</span>
                <strong>${escapeHtml(paymentMethod)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Payment Status</span>
                <strong>${escapeHtml(paymentStatus)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Booking Status</span>
                <strong>${escapeHtml(bookingStatus)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Priority</span>
                <strong>${escapeHtml(String(transaction.priority || 'low').replace(/_/g, ' '))}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Scheduled Date</span>
                <strong>${formatDate(transaction.scheduled_date || transaction.created_at)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Scheduled Time</span>
                <strong>${escapeHtml(transaction.scheduled_time || '-')}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Labor Fee</span>
                <strong>${formatCurrency(transaction.labor_fee ?? 0)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Materials Fee</span>
                <strong>${formatCurrency(transaction.materials_fee ?? 0)}</strong>
              </div>
            </div>

            <div class="mt-4">
              <h6 class="text-muted mb-2">Location</h6>
              <div class="border rounded p-3">${escapeHtml(transaction.location_address || 'No location recorded.')}</div>
            </div>

            <div class="mt-4">
              <h6 class="text-muted mb-2">Notes</h6>
              <div class="border rounded p-3">${escapeHtml(transaction.notes || transaction.description || 'No additional notes recorded.')}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

function getSidebarLinks(role) {
  if (role === 'admin' || role === 'super_admin') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', route: 'dashboard' },
      { icon: 'fas fa-shield-alt', label: 'Security', route: 'security' },
      { icon: 'fas fa-user-clock', label: 'Pending Workers', route: 'pending-workers' },
      { icon: 'fas fa-users', label: 'Users', route: 'users' },
      { icon: 'fas fa-chart-pie', label: 'User Analytics', route: 'user-analytics' },
      { icon: 'fas fa-list', label: 'Services', route: 'services' },
      { icon: 'fas fa-calendar-check', label: 'Bookings', route: 'bookings' },
      { icon: 'fas fa-database', label: 'Backups', route: 'backups' },
      { icon: 'fas fa-star', label: 'Rates', route: 'rates' },
      { icon: 'fas fa-credit-card', label: 'Payments', route: 'payments' },
      { icon: 'fas fa-file-invoice', label: 'Service Records', route: 'records' }
    ];
  }

  if (role === 'worker') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', route: 'dashboard' },
      { icon: 'fas fa-briefcase', label: 'Available Jobs', route: 'available-jobs' },
      { icon: 'fas fa-tasks', label: 'My Jobs', route: 'my-jobs' },
      { icon: 'fas fa-wallet', label: 'Earnings', route: 'earnings' }
    ];
  }

  if (role === 'finance') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', route: 'dashboard' },
      { icon: 'fas fa-money-bill-wave', label: 'Payments', route: 'payments' },
      { icon: 'fas fa-hand-holding-usd', label: 'Worker Payouts', route: 'payouts' },
      { icon: 'fas fa-chart-bar', label: 'Financial Reports', route: 'reports' }
    ];
  }

  return [
    { icon: 'fas fa-chart-line', label: 'Dashboard', route: 'dashboard' },
    { icon: 'fas fa-calendar-check', label: 'My Bookings', route: 'bookings' },
    { icon: 'fas fa-list', label: 'Services', route: 'services' },
    { icon: 'fas fa-credit-card', label: 'Payments', route: 'payments' }
  ];
}

function getViewTitle(route, role) {
  const titles = {
    dashboard: 'Dashboard',
    security: 'Security',
    'pending-workers': 'Pending Workers',
    profile: 'Profile',
    settings: 'Settings',
    users: 'Users',
    'user-analytics': 'User Analytics',
    bookings: role === 'customer' ? 'My Bookings' : 'Bookings',
    backups: 'Backups',
    rates: 'Rates',
    payments: 'Payments',
    records: 'Service Records',
    'available-jobs': 'Available Jobs',
    'my-jobs': 'My Jobs',
    earnings: 'Earnings',
    payouts: 'Worker Payouts',
    reports: 'Financial Reports',
    services: 'Services'
  };

  return titles[route] || 'Workspace';
}

function createProfileForm(profile) {
  const isWorker = profile.user_type === 'worker';

  return `
    ${createPageHeading('fas fa-user-circle', 'Profile')}
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-user-circle"></i> Edit Profile
      </div>
      <div class="card-body desktop-card-body">
        <form id="profileForm" class="desktop-form-grid">
          <div class="form-row">
            <div class="field">
              <label for="profile_first_name">First Name</label>
              <input id="profile_first_name" name="first_name" type="text" value="${profile.first_name || ''}" required />
            </div>
            <div class="field">
              <label for="profile_last_name">Last Name</label>
              <input id="profile_last_name" name="last_name" type="text" value="${profile.last_name || ''}" required />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="profile_email">Email</label>
              <input id="profile_email" name="email" type="email" value="${profile.email || ''}" required />
            </div>
            <div class="field">
              <label for="profile_phone">Phone</label>
              <input id="profile_phone" name="phone" type="tel" value="${profile.phone || ''}" />
            </div>
          </div>

          <div class="field">
            <label for="profile_address">Address</label>
            <textarea id="profile_address" name="address">${profile.address || ''}</textarea>
          </div>

          ${isWorker ? `
            <div class="field">
              <label for="profile_skills">Skills</label>
              <textarea id="profile_skills" name="skills">${profile.skills || ''}</textarea>
            </div>
            <div class="field">
              <label for="profile_experience_years">Years of Experience</label>
              <input id="profile_experience_years" name="experience_years" type="number" min="0" max="99" value="${profile.experience_years || 0}" />
            </div>
            <div class="field">
              <label for="profile_service_city">Service City / Area</label>
              <input id="profile_service_city" name="service_city" type="text" value="${profile.service_city || ''}" placeholder="e.g., General Santos" />
            </div>
            <div class="field">
              <label for="profile_service_radius_km">Coverage Radius (km)</label>
              <input id="profile_service_radius_km" name="service_radius_km" type="number" min="1" max="500" step="0.1" value="${profile.service_radius_km ?? 20}" />
            </div>
            <div class="form-row">
              <div class="field">
                <label for="profile_work_latitude">Base Latitude</label>
                <input id="profile_work_latitude" name="work_latitude" type="number" min="-90" max="90" step="0.00000001" value="${profile.work_latitude || ''}" />
              </div>
              <div class="field">
                <label for="profile_work_longitude">Base Longitude</label>
                <input id="profile_work_longitude" name="work_longitude" type="number" min="-180" max="180" step="0.00000001" value="${profile.work_longitude || ''}" />
              </div>
            </div>
          ` : ''}

          <div class="desktop-form-actions">
            <button id="profileSaveButton" type="submit" class="action-button">Save Profile</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function formatSettingsActivityDetails(details) {
  if (!details || typeof details !== 'object') {
    return '-';
  }

  if (details.reason) {
    return String(details.reason).replace(/_/g, ' ');
  }

  if (details.method) {
    return String(details.method).replace(/_/g, ' ');
  }

  if (details.changed_fields && typeof details.changed_fields === 'object') {
    const fields = Object.keys(details.changed_fields);
    return fields.length ? fields.join(', ') : '-';
  }

  if (details.created_fields && typeof details.created_fields === 'object') {
    const fields = Object.keys(details.created_fields);
    return fields.length ? `Created: ${fields.join(', ')}` : '-';
  }

  return 'Details available';
}

function formatSettingsUserName(row, prefix) {
  const firstName = String(row?.[`${prefix}_first_name`] || '').trim();
  const lastName = String(row?.[`${prefix}_last_name`] || '').trim();
  const fullName = `${firstName} ${lastName}`.trim();
  return fullName || row?.[`${prefix}_email`] || 'Unknown';
}

function formatSettingsSessionType(value) {
  const normalized = String(value || '').trim().toLowerCase();
  if (normalized === 'api') {
    return 'Desktop App';
  }

  if (normalized === 'web') {
    return 'Website';
  }

  return normalized ? normalized.replace(/_/g, ' ') : '-';
}

function formatSettingsIpAddress(value) {
  const normalized = String(value || '').trim();
  if (!normalized) {
    return '-';
  }

  if (normalized === '::1' || normalized === '127.0.0.1') {
    return 'Localhost';
  }

  return normalized;
}

function createSettingsForm(state) {
  const settings = state.routeSettingsSummary || {};
  const currentSession = settings.currentSession || null;
  const myActiveSessions = settings.myActiveSessions || [];
  const recentActiveSessions = settings.recentActiveSessions || [];
  const myActivityLogs = settings.myActivityLogs || [];
  const recentActivityLogs = settings.recentActivityLogs || [];
  const isAdmin = ['admin', 'super_admin'].includes(String(state.role || '').toLowerCase());

  return `
    ${createPageHeading('fas fa-cog', 'Settings')}
    ${state.routeNotice ? createInfoBanner(state.routeNotice) : ''}

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-sliders-h"></i> Application Settings
      </div>
      <div class="card-body desktop-card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <strong>Environment</strong>
            <div>${escapeHtml(settings.environment || '-')}</div>
          </div>
          <div class="col-md-4">
            <strong>Base URL</strong>
            <div>${escapeHtml(settings.baseUrl || '-')}</div>
          </div>
          <div class="col-md-4">
            <strong>Current Role</strong>
            <div>${escapeHtml(String(settings.role || state.role || '-').replace(/_/g, ' '))}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-user-clock"></i> Current Session
      </div>
      <div class="card-body desktop-card-body">
        ${currentSession ? `
          <div class="desktop-settings-session-grid">
            <div class="desktop-settings-stat">
              <span class="desktop-settings-label">Session Type</span>
              <strong>${escapeHtml(formatSettingsSessionType(currentSession.session_type))}</strong>
            </div>
            <div class="desktop-settings-stat">
              <span class="desktop-settings-label">Logged In At</span>
              <strong>${formatDate(currentSession.logged_in_at)}</strong>
            </div>
            <div class="desktop-settings-stat">
              <span class="desktop-settings-label">Last Activity</span>
              <strong>${formatDate(currentSession.last_activity_at)}</strong>
            </div>
            <div class="desktop-settings-stat">
              <span class="desktop-settings-label">IP Address</span>
              <strong>${escapeHtml(formatSettingsIpAddress(currentSession.ip_address))}</strong>
            </div>
            <div class="desktop-settings-stat desktop-settings-stat-wide">
              <span class="desktop-settings-label">Device</span>
              <strong>${escapeHtml(currentSession.device_label || '-')}</strong>
            </div>
          </div>
        ` : `
          <p class="text-muted mb-0">No tracked session information is available for the current login.</p>
        `}
      </div>
    </div>

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-layer-group"></i> My Active Sessions
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Type</th>
                <th>Logged In</th>
                <th>Last Activity</th>
                <th>IP Address</th>
                <th>Device</th>
              </tr>
            </thead>
            <tbody>
              ${myActiveSessions.length ? myActiveSessions.map((trackedSession) => `
                <tr>
                  <td>${escapeHtml(trackedSession.session_type || '-')}</td>
                  <td>${formatDate(trackedSession.logged_in_at)}</td>
                  <td>${formatDate(trackedSession.last_activity_at)}</td>
                  <td>${escapeHtml(trackedSession.ip_address || '-')}</td>
                  <td>${escapeHtml(trackedSession.device_label || '-')}</td>
                </tr>
              `).join('') : `
                <tr><td colspan="5" class="text-center text-muted py-4">No tracked active sessions were returned.</td></tr>
              `}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    ${isAdmin ? `
      <div class="card desktop-card mb-4">
        <div class="card-header desktop-card-header">
          <i class="fas fa-shield-alt"></i> Active Session Monitor
        </div>
        <div class="card-body desktop-card-body">
          <div class="mb-3"><strong>Total Active Sessions:</strong> ${formatValue(settings.activeSessionCount ?? 0)}</div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Role</th>
                  <th>Type</th>
                  <th>Last Activity</th>
                  <th>IP Address</th>
                  <th>Device</th>
                </tr>
              </thead>
              <tbody>
                ${recentActiveSessions.length ? recentActiveSessions.map((trackedSession) => `
                  <tr>
                    <td>${escapeHtml(`${trackedSession.first_name || ''} ${trackedSession.last_name || ''}`.trim() || trackedSession.email || 'Unknown')}</td>
                    <td>${escapeHtml(trackedSession.user_type || '-')}</td>
                    <td>${escapeHtml(trackedSession.session_type || '-')}</td>
                    <td>${formatDate(trackedSession.last_activity_at)}</td>
                    <td>${escapeHtml(trackedSession.ip_address || '-')}</td>
                    <td>${escapeHtml(trackedSession.device_label || '-')}</td>
                  </tr>
                `).join('') : `
                  <tr><td colspan="6" class="text-center text-muted py-4">No recent active sessions were returned.</td></tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    ` : ''}

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-history"></i> My Account Activity
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive desktop-settings-scroll">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>When</th>
                <th>Category</th>
                <th>Action</th>
                <th>Result</th>
                <th>Source</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              ${myActivityLogs.length ? myActivityLogs.map((activityLog) => `
                <tr>
                  <td>${formatDate(activityLog.created_at)}</td>
                  <td>${escapeHtml(String(activityLog.event_type || '-').replace(/_/g, ' '))}</td>
                  <td>${escapeHtml(String(activityLog.action || '-').replace(/_/g, ' '))}</td>
                  <td>${escapeHtml(String(activityLog.outcome || '-').replace(/_/g, ' '))}</td>
                  <td>${escapeHtml(String(activityLog.source || '-').toUpperCase())}</td>
                  <td>${escapeHtml(formatSettingsActivityDetails(activityLog.details || {}))}</td>
                </tr>
              `).join('') : `
                <tr><td colspan="6" class="text-center text-muted py-4">No account activity has been recorded yet.</td></tr>
              `}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    ${isAdmin ? `
      <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-clipboard-list"></i> Recent System Activity
      </div>
      <div class="card-body desktop-card-body">
          <div class="table-responsive desktop-settings-scroll">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>When</th>
                  <th>Actor</th>
                  <th>Target</th>
                  <th>Category</th>
                  <th>Action</th>
                  <th>Result</th>
                  <th>Source</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                ${recentActivityLogs.length ? recentActivityLogs.map((activityLog) => `
                  <tr>
                    <td>${formatDate(activityLog.created_at)}</td>
                    <td>${escapeHtml(formatSettingsUserName(activityLog, 'actor'))}</td>
                    <td>${escapeHtml(formatSettingsUserName(activityLog, 'target'))}</td>
                    <td>${escapeHtml(String(activityLog.event_type || '-').replace(/_/g, ' '))}</td>
                    <td>${escapeHtml(String(activityLog.action || '-').replace(/_/g, ' '))}</td>
                    <td>${escapeHtml(String(activityLog.outcome || '-').replace(/_/g, ' '))}</td>
                    <td>${escapeHtml(String(activityLog.source || '-').toUpperCase())}</td>
                    <td>${escapeHtml(formatSettingsActivityDetails(activityLog.details || {}))}</td>
                  </tr>
                `).join('') : `
                  <tr><td colspan="8" class="text-center text-muted py-4">No recent system activity was returned.</td></tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    ` : ''}

    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-shield-alt"></i> Change Password
      </div>
      <div class="card-body desktop-card-body">
        <form id="passwordForm" class="desktop-form-grid">
          <div class="field">
            <label for="current_password">Current Password</label>
            <input id="current_password" name="current_password" type="password" required />
          </div>
          <div class="field">
            <label for="new_password">New Password</label>
            <input id="new_password" name="new_password" type="password" minlength="8" required />
          </div>
          <div class="field">
            <label for="confirm_password">Confirm New Password</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" required />
          </div>
          <div class="desktop-form-actions">
            <button id="passwordSaveButton" type="submit" class="action-button">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createServicesView(state) {
  return createServicesWorkspaceView(state, {
    createInfoBanner,
    createMetricGrid,
    createPageHeading,
    escapeHtml,
    formatCurrency,
    formatValue
  });
}

function createBookingsView(state) {
  const bookings = state.routeBookings || [];
  const isCustomer = state.role === 'customer';

  return `
    ${createPageHeading('fas fa-calendar-check', getViewTitle(state.currentRoute, state.role))}
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-list"></i> ${isCustomer ? 'Booking List' : getViewTitle(state.currentRoute, state.role)}
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Reference</th>
                <th>${isCustomer ? 'Service' : 'Title'}</th>
                ${isCustomer ? '<th>Worker</th>' : ''}
                <th>Scheduled Date</th>
                <th>Status</th>
                <th>${isCustomer ? 'Total Fee' : 'Amount'}</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${bookings.length ? bookings.map((booking) => `
                <tr>
                  <td><strong>${booking.booking_reference || booking.id}</strong></td>
                  <td>${booking.service_name || booking.title || 'Untitled booking'}</td>
                  ${isCustomer ? `<td>${[booking.worker_first_name, booking.worker_last_name].filter(Boolean).join(' ') || 'Not assigned yet'}</td>` : ''}
                  <td>${formatDate(booking.scheduled_date || booking.created_at)}</td>
                  <td><span class="badge badge-${booking.status || 'pending'}">${String(booking.status || 'pending').replace(/_/g, ' ')}</span></td>
                  <td>${formatCurrency(booking.total_fee ?? (Number(booking.labor_fee || 0) + Number(booking.materials_fee || 0)))}</td>
                  <td class="desktop-table-actions">
                    ${getBookingActionButtons(booking, state.role, state.currentRoute)}
                  </td>
                </tr>
              `).join('') : `
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No bookings found for this view yet.</td>
                </tr>
              `}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="bookingReviewPanel"></div>
  `;
}

function createEarningsView(state) {
  const earnings = state.earningsData || { total_earnings: 0, payouts: [] };
  const payouts = earnings.payouts || [];

  return `
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="stat-card desktop-stat-card success">
          <i class="fas fa-wallet stat-icon"></i>
          <div class="stat-value">${formatCurrency(earnings.total_earnings ?? 0)}</div>
          <div class="stat-label">Total Earnings</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card desktop-stat-card info">
          <i class="fas fa-money-check-alt stat-icon"></i>
          <div class="stat-value">${payouts.length}</div>
          <div class="stat-label">Payout Records</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card desktop-stat-card primary">
          <i class="fas fa-coins stat-icon"></i>
          <div class="stat-value">${formatCurrency(payouts.reduce((sum, payout) => sum + Number(payout.amount || 0), 0))}</div>
          <div class="stat-label">Recorded Payout Total</div>
        </div>
      </div>
    </div>

    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-list"></i> Worker Payout History
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              ${payouts.length ? payouts.map((payout) => `
                <tr>
                  <td><strong>${payout.payment_reference || payout.id}</strong></td>
                  <td><span class="badge badge-${payout.status || 'pending'}">${String(payout.status || 'pending').replace(/_/g, ' ')}</span></td>
                  <td>${formatCurrency(payout.amount || 0)}</td>
                  <td>${payout.payment_method || 'N/A'}</td>
                  <td>${formatDate(payout.payment_date || payout.created_at)}</td>
                </tr>
              `).join('') : `
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No payout records available yet.</td>
                </tr>
              `}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

function createInfoBanner(message, tone = 'info') {
  if (!message) {
    return '';
  }

  return `
    <div class="desktop-inline-banner ${tone}">
      ${message}
    </div>
  `;
}

function createMetricGrid(metrics) {
  return `
    <div class="row mb-4">
      ${metrics.map((metric) => createStatCard(metric)).join('')}
    </div>
  `;
}

function getDefaultUserFilters() {
  return {
    q: '',
    userType: '',
    status: 'active',
    showDeleted: false,
    page: 1,
    limit: 25
  };
}

function parseSkills(value) {
  if (Array.isArray(value)) {
    return value;
  }

  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value);
      if (Array.isArray(parsed)) {
        return parsed;
      }
    } catch {
      return value.split(',').map((skill) => skill.trim()).filter(Boolean);
    }
  }

  return [];
}

function normalizeUserFormErrors(rawErrors) {
  if (!rawErrors || typeof rawErrors !== 'object') {
    return {};
  }

  return Object.entries(rawErrors).reduce((accumulator, [key, value]) => {
    if (typeof value === 'string' && value.trim()) {
      accumulator[key] = value.trim();
    }
    return accumulator;
  }, {});
}

function getUserFieldError(errors, fieldName) {
  return String(errors?.[fieldName] || '').trim();
}

function getUserFieldClass(errors, fieldName) {
  return getUserFieldError(errors, fieldName) ? 'desktop-field-invalid' : '';
}

function renderUserFieldError(errors, fieldName) {
  const message = getUserFieldError(errors, fieldName);
  if (!message) {
    return '';
  }

  return `<small class="desktop-field-error" role="alert">${escapeHtml(message)}</small>`;
}

function createUserEditorForm(state) {
  const mode = state.userEditorMode || 'create';
  const user = state.selectedUser || {};
  const formError = String(state.userFormError || '').trim();
  const formErrors = state.userFormErrors || {};
  const isEditing = mode === 'edit';
  const isViewing = mode === 'view';
  const isArchivedView = Boolean(state.userFilters?.showDeleted);
  const isArchivedUser = Boolean(user.deleted_at) || isArchivedView;
  const isSuperAdmin = user.user_type === 'super_admin';
  const canArchive = mode !== 'create' && !isSuperAdmin && !isArchivedUser;
  const skills = parseSkills(user.skills).join(', ');
  const locked = isViewing ? 'disabled' : '';
  const title = isArchivedUser
    ? 'Archived User Details'
    : isViewing
      ? 'User Details'
      : isEditing
        ? 'Edit User'
        : 'Create New User';
  const submitLabel = isEditing ? 'Save Changes' : 'Create User';

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-user-gear"></i> ${title}</span>
        ${mode !== 'create' && !isArchivedView ? '<button type="button" class="ghost-button" id="userCreateNewButton">New User</button>' : ''}
      </div>
      <div class="card-body desktop-card-body">
        ${isArchivedUser
          ? createInfoBanner('This user is archived. You can restore the account or permanently delete it from the desktop.', 'warning')
          : isViewing
            ? createInfoBanner('This is a read-only view. Click Edit to update this account.', 'info')
            : ''}
        ${formError ? createInfoBanner(formError, 'danger') : ''}
        <form id="userEditorForm" class="desktop-form-grid">
          ${isEditing ? `<input type="hidden" name="id" value="${escapeHtml(user.id || '')}" />` : ''}
          ${isSuperAdmin ? '<input type="hidden" name="user_type" value="super_admin" />' : ''}
          <div class="form-row">
            <div class="field">
              <label for="admin_user_first_name">First Name</label>
              <input id="admin_user_first_name" class="${getUserFieldClass(formErrors, 'first_name')}" name="first_name" type="text" value="${escapeHtml(user.first_name || '')}" ${locked} required />
              ${renderUserFieldError(formErrors, 'first_name')}
            </div>
            <div class="field">
              <label for="admin_user_last_name">Last Name</label>
              <input id="admin_user_last_name" class="${getUserFieldClass(formErrors, 'last_name')}" name="last_name" type="text" value="${escapeHtml(user.last_name || '')}" ${locked} required />
              ${renderUserFieldError(formErrors, 'last_name')}
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="admin_user_email">Email</label>
              <input id="admin_user_email" class="${getUserFieldClass(formErrors, 'email')}" name="email" type="email" value="${escapeHtml(user.email || '')}" ${locked} required />
              ${renderUserFieldError(formErrors, 'email')}
            </div>
            <div class="field">
              <label for="admin_user_phone">Phone</label>
              <input id="admin_user_phone" class="${getUserFieldClass(formErrors, 'phone')}" name="phone" type="text" value="${escapeHtml(user.phone || '')}" ${locked} />
              ${renderUserFieldError(formErrors, 'phone')}
            </div>
          </div>

          <div class="field">
            <label for="admin_user_address">Address</label>
            <textarea id="admin_user_address" class="${getUserFieldClass(formErrors, 'address')}" name="address" ${locked}>${escapeHtml(user.address || '')}</textarea>
            ${renderUserFieldError(formErrors, 'address')}
          </div>

          <div class="form-row">
            <div class="field">
              <label for="admin_user_type">User Role</label>
              ${isSuperAdmin ? `
                <input id="admin_user_type_label" type="text" value="super_admin" disabled />
                <small class="text-muted">Super admin role is fixed and cannot be reassigned.</small>
              ` : `
                <select id="admin_user_type" class="${getUserFieldClass(formErrors, 'user_type')}" name="user_type" ${locked} required>
                  <option value="">Select role</option>
                  ${['admin', 'finance', 'worker', 'customer'].map((role) => `
                    <option value="${role}" ${String(user.user_type || '') === role ? 'selected' : ''}>${role.replace(/_/g, ' ')}</option>
                  `).join('')}
                </select>
                ${renderUserFieldError(formErrors, 'user_type')}
              `}
            </div>
            <div class="field">
              <label for="admin_user_status">Status</label>
              <select id="admin_user_status" class="${getUserFieldClass(formErrors, 'status')}" name="status" ${locked} required>
                ${['active', 'inactive', 'suspended'].map((status) => `
                  <option value="${status}" ${String(user.status || 'active') === status ? 'selected' : ''}>${status}</option>
                `).join('')}
              </select>
              ${renderUserFieldError(formErrors, 'status')}
            </div>
          </div>

          ${!isEditing ? `
            <div class="field">
              <label for="admin_user_password">Password</label>
              <input id="admin_user_password" class="${getUserFieldClass(formErrors, 'password')}" name="password" type="password" minlength="8" ${locked} required />
              ${renderUserFieldError(formErrors, 'password')}
            </div>
          ` : `
            <div class="desktop-inline-banner">Password changes stay in the self-service profile flow, just like the website admin page.</div>
          `}

          <div id="desktopWorkerFields" class="${String(user.user_type || '') === 'worker' ? '' : 'hidden'}">
            <div class="desktop-section-divider">
              <h5><i class="fas fa-tools"></i> Worker Details</h5>
              <p class="mb-0 text-muted">Coverage, rate, and skills are used when matching workers to jobs.</p>
            </div>
            <div class="field">
              <label for="admin_user_skills">Skills</label>
              <input id="admin_user_skills" class="${getUserFieldClass(formErrors, 'skills')}" name="skills" type="text" value="${escapeHtml(skills)}" placeholder="e.g., plumbing, electrical, carpentry" ${locked} />
              ${renderUserFieldError(formErrors, 'skills')}
            </div>
            <div class="form-row">
              <div class="field">
                <label for="admin_user_experience_years">Years of Experience</label>
                <input id="admin_user_experience_years" class="${getUserFieldClass(formErrors, 'experience_years')}" name="experience_years" type="number" min="0" max="50" value="${escapeHtml(user.experience_years ?? 0)}" ${locked} />
                ${renderUserFieldError(formErrors, 'experience_years')}
              </div>
              <div class="field">
                <label for="admin_user_commission_rate">Commission Rate (%)</label>
                <input id="admin_user_commission_rate" class="${getUserFieldClass(formErrors, 'commission_rate')}" name="commission_rate" type="number" min="0" max="100" step="0.01" value="${escapeHtml(user.commission_rate ?? 20)}" ${locked} />
                ${renderUserFieldError(formErrors, 'commission_rate')}
              </div>
            </div>
            <div class="form-row">
              <div class="field">
                <label for="admin_user_service_city">Service City / Area</label>
                <input id="admin_user_service_city" class="${getUserFieldClass(formErrors, 'service_city')}" name="service_city" type="text" value="${escapeHtml(user.service_city || '')}" ${locked} />
                ${renderUserFieldError(formErrors, 'service_city')}
              </div>
              <div class="field">
                <label for="admin_user_service_radius_km">Coverage Radius (km)</label>
                <input id="admin_user_service_radius_km" class="${getUserFieldClass(formErrors, 'service_radius_km')}" name="service_radius_km" type="number" min="1" max="500" step="0.1" value="${escapeHtml(user.service_radius_km ?? 20)}" ${locked} />
                ${renderUserFieldError(formErrors, 'service_radius_km')}
              </div>
            </div>
            <div class="form-row">
              <div class="field">
                <label for="admin_user_work_latitude">Base Latitude</label>
                <input id="admin_user_work_latitude" class="${getUserFieldClass(formErrors, 'work_latitude')}" name="work_latitude" type="number" min="-90" max="90" step="0.00000001" value="${escapeHtml(user.work_latitude || '')}" ${locked} />
                ${renderUserFieldError(formErrors, 'work_latitude')}
              </div>
              <div class="field">
                <label for="admin_user_work_longitude">Base Longitude</label>
                <input id="admin_user_work_longitude" class="${getUserFieldClass(formErrors, 'work_longitude')}" name="work_longitude" type="number" min="-180" max="180" step="0.00000001" value="${escapeHtml(user.work_longitude || '')}" ${locked} />
                ${renderUserFieldError(formErrors, 'work_longitude')}
              </div>
            </div>
          </div>

          ${mode !== 'create' ? `
            <div class="desktop-insight-grid">
              <div class="desktop-insight-tile">
                <span>User ID</span>
                <strong>${formatValue(user.id)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Role</span>
                <strong>${escapeHtml(String(user.user_type || '-').replace(/_/g, ' '))}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Joined</span>
                <strong>${formatDate(user.created_at)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Worker Rating</span>
                <strong>${formatValue(user.average_rating ?? '-')}</strong>
              </div>
            </div>
          ` : ''}

          <div class="desktop-form-actions">
            ${isArchivedUser
              ? '<button type="button" class="action-button" id="userRestoreButton">Restore User</button><button type="button" class="ghost-button desktop-danger-button" id="userPermanentDeleteButton">Delete Permanently</button>'
              : isViewing
                ? '<button type="button" class="action-button" id="userEditButton">Edit User</button>'
                : `<button type="submit" class="action-button" id="userSaveButton">${submitLabel}</button>`}
            ${canArchive ? '<button type="button" class="ghost-button desktop-danger-button" id="userArchiveButton">Archive User</button>' : ''}
            ${mode !== 'create' ? '<button type="button" class="ghost-button" id="userCancelSelectionButton">Clear Selection</button>' : ''}
          </div>
        </form>
      </div>
    </div>
  `;
}

function createUsersEmptyDetailPanel(state) {
  const isArchivedView = Boolean(state.userFilters?.showDeleted);
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-user-gear"></i> User Workspace
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          <div>
            <h5 class="mb-2">${isArchivedView ? 'Select an archived user' : 'Select a user or create a new one'}</h5>
            <p class="mb-0 text-muted">${isArchivedView ? 'Choose an archived user to restore it or permanently delete it.' : 'Choose a row action from the list, or click <strong>Add New User</strong> to open the admin form.'}</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function createUsersView(state) {
  const users = state.routeUsers || [];
  const stats = state.routeUserStats || {};
  const filters = state.userFilters || getDefaultUserFilters();
  const isArchivedView = Boolean(filters.showDeleted);
  const mode = state.userEditorMode || 'idle';
  const tableTitle = isArchivedView
    ? 'Archived Users'
    : filters.status === 'active'
      ? 'Active Users'
      : filters.status
        ? `${String(filters.status).replace(/_/g, ' ')} Users`
        : 'All Users';
  const pagination = state.routeUsersPagination || { total: users.length, page: filters.page || 1, limit: filters.limit || 25, pages: 1 };
  const currentPage = Number(pagination.page || 1);
  const totalPages = Number(pagination.pages || 1);
  const totalUsers = Number(pagination.total || users.length || 0);
  const pageSize = Number(pagination.limit || filters.limit || 25);
  const rangeStart = totalUsers ? ((currentPage - 1) * pageSize) + 1 : 0;
  const rangeEnd = totalUsers ? Math.min(currentPage * pageSize, totalUsers) : 0;

  return `
    ${createInfoBanner(state.routeNotice)}
    ${createMetricGrid([
      { icon: 'fas fa-users', value: stats.total_users ?? users.length, label: 'Total Users', tone: 'primary' },
      { icon: 'fas fa-box-archive', value: stats.archived_users ?? 0, label: 'Archived', tone: 'secondary' },
      { icon: 'fas fa-user-tie', value: stats.total_workers ?? 0, label: 'Workers', tone: 'info' },
      { icon: 'fas fa-user-friends', value: stats.total_customers ?? 0, label: 'Customers', tone: 'success' }
    ])}

    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> User Filters
      </div>
      <div class="card-body desktop-card-body">
        <form id="usersFilterForm" class="desktop-filter-grid">
          <div class="field">
            <label for="usersFilterQuery">Search</label>
            <input id="usersFilterQuery" name="q" type="text" value="${escapeHtml(filters.q || '')}" placeholder="Name or email" />
          </div>
          <div class="field">
            <label for="usersFilterRole">Role</label>
            <select id="usersFilterRole" name="userType">
              <option value="">All roles</option>
              ${['admin', 'finance', 'worker', 'customer', 'super_admin'].map((role) => `
                <option value="${role}" ${filters.userType === role ? 'selected' : ''}>${role.replace(/_/g, ' ')}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="usersFilterStatus">Status</label>
            <select id="usersFilterStatus" name="status">
              <option value="" ${!filters.status ? 'selected' : ''}>All statuses</option>
              ${['active', 'inactive', 'suspended'].map((status) => `
                <option value="${status}" ${filters.status === status ? 'selected' : ''}>${status}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="usersFilterLimit">Rows</label>
            <select id="usersFilterLimit" name="limit">
              ${[10, 25, 50, 100].map((limit) => `
                <option value="${limit}" ${Number(filters.limit || 25) === limit ? 'selected' : ''}>${limit}</option>
              `).join('')}
            </select>
          </div>
          <div class="desktop-form-actions desktop-users-filter-actions">
            <button type="submit" class="action-button">Apply Filters</button>
            <button type="button" class="ghost-button" id="usersResetFiltersButton">Reset</button>
            <button type="button" class="ghost-button" id="usersArchivedToggleButton">${isArchivedView ? 'Back to Active Users' : 'View Archived'}</button>
            ${isArchivedView ? '' : '<button type="button" class="ghost-button" id="usersCreateButton">Add New User</button>'}
          </div>
        </form>
      </div>
    </div>

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-users"></i> ${tableTitle}</span>
          <span class="badge bg-secondary">${rangeStart}-${rangeEnd} of ${totalUsers}</span>
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive desktop-users-list-scroll">
            <table class="table table-hover desktop-users-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Role</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                ${users.length ? users.map((user) => `
                  <tr
                    class="${Number(state.selectedUserId || 0) === Number(user.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-user-row="true"
                    data-user-id="${user.id}"
                  >
                    <td>
                      <strong class="desktop-user-name">${escapeHtml([user.first_name, user.last_name].filter(Boolean).join(' ') || 'Unnamed user')}</strong>
                    </td>
                    <td><span class="badge bg-secondary">${escapeHtml(String(user.user_type || 'user').replace(/_/g, ' '))}</span></td>
                    <td><span class="badge bg-${isArchivedView ? 'secondary' : String(user.status || 'active') === 'active' ? 'success' : 'secondary'}">${escapeHtml(isArchivedView ? 'archived' : String(user.status || 'active').replace(/_/g, ' '))}</span></td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No users matched the current filters.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
          <div class="desktop-pagination-bar">
            <div class="desktop-table-subtext">Page ${currentPage} of ${totalPages}</div>
            <div class="desktop-form-actions">
              <button type="button" class="ghost-button" id="usersPrevPageButton" ${currentPage <= 1 ? 'disabled' : ''}>Previous</button>
              <button type="button" class="ghost-button" id="usersNextPageButton" ${currentPage >= totalPages ? 'disabled' : ''}>Next</button>
            </div>
          </div>
        </div>
      </div>

      ${mode === 'idle' ? createUsersEmptyDetailPanel(state) : createUserEditorForm(state)}
    </div>
  `;
}

function cacheUsers(state, users) {
  state.userDetailsById = state.userDetailsById || {};
  users.forEach((user) => {
    if (!user?.id) {
      return;
    }

    const existingUser = state.userDetailsById[user.id] || {};
    state.userDetailsById[user.id] = { ...existingUser, ...user };
  });
}

function selectUserFromCache(state, userId) {
  const normalizedUserId = Number(userId || 0);
  if (!normalizedUserId) {
    state.selectedUserId = null;
    state.selectedUser = null;
    return;
  }

  state.selectedUserId = normalizedUserId;
  state.userDetailsById = state.userDetailsById || {};
  const cachedUser = state.userDetailsById[normalizedUserId];
  const listUser = (state.routeUsers || []).find((user) => Number(user.id) === normalizedUserId) || null;
  state.selectedUser = cachedUser || listUser || null;
}

function renderUsersViewOnly(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.innerHTML = createUsersView(state);
  bindUsersView(state, session, bridge);
}

function renderPendingWorkersViewOnly(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.innerHTML = createPendingWorkersView(state);
  bindPendingWorkersView(state, session, bridge);
}

function createPaymentsView(state) {
  const payments = state.routePayments || [];

  if (state.role === 'customer') {
    const completedPayments = payments.filter((payment) => String(payment.status || '') === 'completed');

    return `
      ${createPageHeading('fas fa-credit-card', 'My Payments')}
      ${createInfoBanner(state.routeNotice || 'This payment history is loaded directly from the backend for your account.')}
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-receipt"></i> Payment Records
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Payment Reference</th>
                  <th>Booking</th>
                  <th>Service</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                ${payments.length ? payments.map((payment) => `
                  <tr>
                    <td><code>${payment.payment_reference || payment.transaction_id || payment.id}</code></td>
                    <td>${payment.booking_title || payment.booking_reference || '-'}</td>
                    <td>${payment.service_name || '-'}</td>
                    <td><strong>${formatCurrency(payment.amount || 0)}</strong></td>
                    <td>${String(payment.payment_method || 'unassigned').replace(/_/g, ' ')}</td>
                    <td><span class="badge badge-${payment.status || 'pending'}">${String(payment.status || 'pending').replace(/_/g, ' ')}</span></td>
                    <td>${formatDate(payment.payment_date || payment.created_at)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">No payment records returned for your account yet.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      ${payments.length ? `
        <div class="card desktop-card mt-4">
          <div class="card-header desktop-card-header">
            <i class="fas fa-chart-pie"></i> Payment Summary
          </div>
          <div class="card-body desktop-card-body">
            <div class="row text-center">
              <div class="col-md-4">
                <h6 class="text-muted">Total Spent</h6>
                <h4 class="text-primary">${formatCurrency(payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0))}</h4>
              </div>
              <div class="col-md-4">
                <h6 class="text-muted">Total Payments</h6>
                <h4 class="text-info">${payments.length}</h4>
              </div>
              <div class="col-md-4">
                <h6 class="text-muted">Completed</h6>
                <h4 class="text-success">${completedPayments.length}</h4>
              </div>
            </div>
          </div>
        </div>
      ` : ''}
    `;
  }

  return createCashierPaymentsView(state, {
    createInfoBanner,
    createMetricGrid,
    escapeHtml,
    formatCurrency,
    formatDate
  });
}

function createRecordsView(state) {
  const records = state.routeRecords || [];

  return `
    ${createInfoBanner(state.routeNotice || 'Service records help operations track field work, notes, and payment status.')}
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-file-invoice"></i> Service Records
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Record</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment Status</th>
                <th>Total</th>
                <th>Scheduled</th>
              </tr>
            </thead>
            <tbody>
              ${records.length ? records.map((record) => `
                <tr>
                  <td><strong>${record.id}</strong></td>
                  <td>${record.customer_name || record.customer_id || '-'}</td>
                  <td><span class="badge badge-${record.status || 'pending'}">${String(record.status || 'pending').replace(/_/g, ' ')}</span></td>
                  <td><span class="badge bg-secondary">${String(record.payment_status || 'unpaid').replace(/_/g, ' ')}</span></td>
                  <td>${formatCurrency(record.total_amount || 0)}</td>
                  <td>${formatDate(record.scheduled_at || record.created_at)}</td>
                </tr>
              `).join('') : `
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No service records are available to the desktop app for this role yet.</td>
                </tr>
              `}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

function createPendingWorkersEmptyPanel() {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-id-card"></i> Application Details
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          <div>
            <h5 class="mb-2">Select a worker application</h5>
            <p class="mb-0 text-muted">Review the applicant details here, then approve or reject the worker application.</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function createPendingWorkerDetailPanel(state) {
  const worker = state.selectedPendingWorker || null;
  if (!worker) {
    return createPendingWorkersEmptyPanel();
  }

  const displayedSkills = Array.isArray(worker.skills_display)
    ? worker.skills_display
    : parseSkills(worker.skills);
  const workerName = [worker.first_name, worker.last_name].filter(Boolean).join(' ') || 'Worker Applicant';
  const resumePath = String(worker.resume_path || '').trim();

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-id-card"></i> Application Details</span>
        <span class="badge bg-warning text-dark">Pending Review</span>
      </div>
      <div class="card-body desktop-card-body">
        <h5 class="mb-2">${escapeHtml(workerName)}</h5>
        <p class="text-muted mb-3">${escapeHtml(worker.email || 'No email provided')}</p>

        <div class="desktop-insight-grid">
          <div class="desktop-insight-tile">
            <span>Phone</span>
            <strong>${escapeHtml(worker.phone || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Applied On</span>
            <strong>${formatDate(worker.created_at)}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Experience</span>
            <strong>${formatValue(worker.experience_years ?? 0)} years</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Commission Rate</span>
            <strong>${formatValue(worker.commission_rate ?? 20)}%</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Service City</span>
            <strong>${escapeHtml(worker.service_city || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Coverage Radius</span>
            <strong>${formatValue(worker.service_radius_km ?? '-')} km</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Resume</span>
            <strong>${escapeHtml(resumePath || 'Not uploaded')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Status</span>
            <strong>${escapeHtml(String(worker.status || 'pending').replace(/_/g, ' '))}</strong>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Address</h6>
          <div class="border rounded p-3">${escapeHtml(worker.address || 'No address provided.')}</div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Skills</h6>
          ${displayedSkills.length ? `
            <div class="desktop-chip-row">
              ${displayedSkills.map((skill) => `<span class="badge bg-secondary">${escapeHtml(skill)}</span>`).join('')}
            </div>
          ` : `
            <div class="border rounded p-3 text-muted">No skills were provided for this application.</div>
          `}
        </div>

        <div class="desktop-form-actions mt-4">
          <button type="button" class="action-button" data-pending-worker-action="approve" data-worker-id="${worker.id}">Approve Worker</button>
          <button type="button" class="ghost-button desktop-danger-button" data-pending-worker-action="reject" data-worker-id="${worker.id}">Reject Worker</button>
        </div>
      </div>
    </div>
  `;
}

function createPendingWorkersView(state) {
  const workers = state.routePendingWorkers || [];
  const selectedWorkerId = Number(state.selectedPendingWorkerId || 0);
  const applicantsWithResume = workers.filter((worker) => String(worker.resume_path || '').trim()).length;
  const withSkills = workers.filter((worker) => {
    const displayedSkills = Array.isArray(worker.skills_display) ? worker.skills_display : parseSkills(worker.skills);
    return displayedSkills.length > 0;
  }).length;

  return `
    ${createPageHeading('fas fa-user-clock', 'Pending Worker Applications')}
    ${createInfoBanner(state.routeNotice || 'Review pending worker applications and approve or reject them directly from Desktop.')}
    ${createMetricGrid([
      { icon: 'fas fa-user-clock', value: workers.length, label: 'Pending Applications', tone: 'warning' },
      { icon: 'fas fa-file-arrow-up', value: applicantsWithResume, label: 'With Resume', tone: 'info' },
      { icon: 'fas fa-screwdriver-wrench', value: withSkills, label: 'With Skills Listed', tone: 'success' }
    ])}

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-users-viewfinder"></i> Application Queue
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive desktop-users-list-scroll">
            <table class="table table-hover desktop-users-table">
              <thead>
                <tr>
                  <th>Applicant</th>
                  <th>Experience</th>
                  <th>Applied</th>
                </tr>
              </thead>
              <tbody>
                ${workers.length ? workers.map((worker) => `
                  <tr
                    class="${selectedWorkerId === Number(worker.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-pending-worker-row="true"
                    data-worker-id="${worker.id}"
                  >
                    <td>
                      <strong class="desktop-user-name">${escapeHtml([worker.first_name, worker.last_name].filter(Boolean).join(' ') || 'Unnamed applicant')}</strong>
                      <div class="desktop-table-subtext">${escapeHtml(worker.email || '-')}</div>
                    </td>
                    <td>${formatValue(worker.experience_years ?? 0)} years</td>
                    <td>${formatDate(worker.created_at)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No pending worker applications are waiting right now.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createPendingWorkerDetailPanel(state)}
      </div>
    </div>
  `;
}

function getBookingActionButtons(booking, role, route) {
  const status = String(booking.status || '');
  const actions = [];

  if (role === 'worker' && route === 'available-jobs' && status === 'pending') {
    actions.push(`<button type="button" class="ghost-button table-action" data-booking-action="accept" data-booking-id="${booking.id}">Accept</button>`);
  }

  if (role === 'customer' && ['pending', 'assigned'].includes(status)) {
    actions.push(`<button type="button" class="ghost-button table-action" data-booking-action="cancel" data-booking-id="${booking.id}">Cancel</button>`);
  }

  if (role === 'worker' && status === 'assigned') {
    actions.push(`<button type="button" class="ghost-button table-action" data-booking-action="start" data-booking-id="${booking.id}">Start</button>`);
  }

  if (role === 'worker' && status === 'in_progress') {
    actions.push(`<button type="button" class="ghost-button table-action" data-booking-action="complete" data-booking-id="${booking.id}">Complete</button>`);
  }

  if (role === 'customer' && status === 'completed' && booking.worker_id) {
    actions.push(`<button type="button" class="ghost-button table-action" data-booking-action="review" data-booking-id="${booking.id}" data-worker-id="${booking.worker_id}">Review</button>`);
  }

  if (!actions.length) {
    actions.push('<span class="text-muted">No actions</span>');
  }

  return actions.join('');
}

function createReviewForm(bookingId, workerId) {
  return `
    <div class="card desktop-card mt-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-star"></i> Leave a Review
      </div>
      <div class="card-body desktop-card-body">
        <form id="reviewForm" class="desktop-form-grid">
          <input type="hidden" name="booking_id" value="${bookingId}" />
          <input type="hidden" name="worker_id" value="${workerId}" />
          <div class="form-row">
            <div class="field">
              <label for="review_rating">Overall Rating</label>
              <input id="review_rating" name="rating" type="number" min="1" max="5" value="5" required />
            </div>
            <div class="field">
              <label for="review_recommend">Would Recommend</label>
              <select id="review_recommend" name="would_recommend" required>
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="review_service_quality">Service Quality</label>
              <input id="review_service_quality" name="service_quality" type="number" min="1" max="5" value="5" required />
            </div>
            <div class="field">
              <label for="review_timeliness">Timeliness</label>
              <input id="review_timeliness" name="timeliness" type="number" min="1" max="5" value="5" required />
            </div>
          </div>
          <div class="field">
            <label for="review_professionalism">Professionalism</label>
            <input id="review_professionalism" name="professionalism" type="number" min="1" max="5" value="5" required />
          </div>
          <div class="field">
            <label for="review_comment">Comment</label>
            <textarea id="review_comment" name="comment"></textarea>
          </div>
          <div class="desktop-form-actions">
            <button id="reviewSubmitButton" type="submit" class="action-button">Submit Review</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createCompleteJobForm(booking) {
  const totalFee = Number(booking.total_fee ?? (Number(booking.labor_fee || 0) + Number(booking.materials_fee || 0)));

  return `
    <div class="card desktop-card mt-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-cash-register"></i> Complete Job and Record Payment
      </div>
      <div class="card-body desktop-card-body">
        <form id="completeJobForm" class="desktop-form-grid">
          <input type="hidden" name="booking_id" value="${booking.id}" />
          <div class="form-row">
            <div class="field">
              <label for="complete_amount_collected">Amount Collected</label>
              <input id="complete_amount_collected" name="amount_collected" type="number" min="0.01" step="0.01" max="${totalFee}" value="${totalFee}" required />
            </div>
            <div class="field">
              <label for="complete_payment_method">Payment Method</label>
              <select id="complete_payment_method" name="payment_method" required>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
                <option value="paymaya">PayMaya</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label for="complete_payment_notes">Payment Notes</label>
            <textarea id="complete_payment_notes" name="payment_notes" placeholder="Optional notes about the payment collected."></textarea>
          </div>
          <div class="desktop-form-actions">
            <button id="completeJobSubmitButton" type="submit" class="action-button">Complete and Record Payment</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createPlaceholderView(route, role) {
  const title = getViewTitle(route, role);

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-tools"></i> ${title}
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          ${title} is the next Desktop module to implement. The Backend API is ready, but this native Desktop screen is still being built.
        </div>
      </div>
    </div>
  `;
}

function createRouteErrorView(route, role, message) {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-triangle-exclamation"></i> ${getViewTitle(route, role)}
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          ${message || 'This page could not load from the backend right now.'}
        </div>
      </div>
    </div>
  `;
}

function createRouteLoadingView(route, role) {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-spinner fa-spin"></i> ${getViewTitle(route, role)}
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          Loading ${getViewTitle(route, role).toLowerCase()}...
        </div>
      </div>
    </div>
  `;
}

function findBookingById(state, bookingId) {
  return (state.routeBookings || []).find((booking) => String(booking.id) === String(bookingId)) || null;
}

function renderCustomerDashboardHome(contentElement, state) {
  const bookings = state.bookings || [];

  contentElement.innerHTML = `
    <div class="container-fluid">
      <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="stat-card">
            <i class="fas fa-calendar-check stat-icon" style="color: var(--primary);"></i>
            <div class="stat-value">${formatValue(state.stats.active_bookings ?? 0)}</div>
            <div class="stat-label">Active Bookings</div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="stat-card warning">
            <i class="fas fa-hourglass-half stat-icon" style="color: var(--warning);"></i>
            <div class="stat-value">${formatValue(state.stats.pending_bookings ?? 0)}</div>
            <div class="stat-label">Pending Bookings</div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="stat-card success">
            <i class="fas fa-check-circle stat-icon" style="color: var(--success);"></i>
            <div class="stat-value">${formatValue(state.stats.completed_bookings ?? 0)}</div>
            <div class="stat-label">Completed Bookings</div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="stat-card info">
            <i class="fas fa-book stat-icon" style="color: var(--info);"></i>
            <div class="stat-value">${formatValue(state.stats.total_bookings ?? 0)}</div>
            <div class="stat-label">Total Bookings</div>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="stat-card success">
            <i class="fas fa-peso-sign stat-icon" style="color: var(--success);"></i>
            <div class="stat-value">${formatCurrency(state.stats.total_spent ?? 0)}</div>
            <div class="stat-label">Total Spent</div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="stat-card">
            <i class="fas fa-star stat-icon" style="color: #ffc107;"></i>
            <div class="stat-value">${formatValue(state.stats.average_rating_given ?? 0)}/5</div>
            <div class="stat-label">Avg. Rating Given</div>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-lg-6">
          <div class="card desktop-card">
            <div class="card-header desktop-card-header">
              <i class="fas fa-chart-line"></i> Spending Trend (Last 30 Days)
            </div>
            <div class="card-body desktop-card-body">
              <div class="chart-container">
                <div class="chart-placeholder">No spending data yet.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card desktop-card">
            <div class="card-header desktop-card-header">
              <i class="fas fa-chart-pie"></i> Service Preferences
            </div>
            <div class="card-body desktop-card-body">
              <div class="chart-container">
                <div class="chart-placeholder">No service preference data yet.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <div class="card desktop-card">
            <div class="card-header desktop-card-header">
              <i class="fas fa-list"></i> Recent Bookings
            </div>
            <div class="card-body desktop-card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Reference</th>
                      <th>Title</th>
                      <th>Status</th>
                      <th>Scheduled Date</th>
                      <th>Amount</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${bookings.length ? bookings.map((booking) => `
                      <tr>
                        <td><strong>${booking.booking_reference || 'N/A'}</strong></td>
                        <td>${booking.title || 'N/A'}</td>
                        <td>
                          <span class="badge badge-${booking.status || 'pending'}">
                            ${String(booking.status || 'pending').replace(/_/g, ' ')}
                          </span>
                        </td>
                        <td>${formatDate(booking.scheduled_date || booking.created_at)}</td>
                        <td>${formatCurrency(booking.total_fee ?? 0)}</td>
                        <td>
                          <button type="button" class="ghost-button table-action" disabled>
                            <i class="fas fa-eye"></i>
                          </button>
                        </td>
                      </tr>
                    `).join('') : `
                      <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                          <i class="fas fa-inbox fa-2x mb-2"></i>
                          <p class="mb-0">No bookings yet. Create your first booking from Services.</p>
                        </td>
                      </tr>
                    `}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderDashboardHome(contentElement, state) {
  if (state.role === 'customer') {
    renderCustomerDashboardHome(contentElement, state);
    return;
  }

  const template = getRoleTemplate(state.role);
  const financeTransactions = state.role === 'finance'
    ? applyFinanceDashboardFilters(state.bookings || [], state.financeDashboardFilters || {})
    : state.bookings;

  contentElement.innerHTML = `
    <div class="row mb-4">
      ${buildRoleStatCards(state.role, state.stats)}
    </div>

    <div class="row mb-4">
      <div class="col-lg-6">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-chart-pie"></i> ${template.chartLeftTitle}
          </div>
          <div class="card-body desktop-card-body">
            <div class="chart-container">
              <div class="chart-placeholder">${template.chartLeftFallback}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-chart-line"></i> ${template.chartRightTitle}
          </div>
          <div class="card-body desktop-card-body">
            <div class="chart-container">
              <div class="chart-placeholder">${template.chartRightFallback}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    ${state.role === 'finance' ? createFinanceDashboardFilterCard(state) : ''}
    ${state.role === 'finance' ? createFinanceDashboardSummaryCards(financeTransactions) : ''}

    <div class="row">
      <div class="col-lg-12">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-list"></i> ${template.tableTitle}
          </div>
          <div class="card-body desktop-card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    ${template.tableHeaders.map((header) => `<th>${header}</th>`).join('')}
                  </tr>
                </thead>
                <tbody>
                  ${renderTableRows(financeTransactions, state.role)}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    ${state.role === 'finance' ? createFinanceDashboardDetailCard(state) : ''}
  `;
}

function bindDashboardHome(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement || state.role !== 'finance') {
    return;
  }

  ensureFinanceDashboardFilters(state);

  const filterForm = contentElement.querySelector('[data-finance-dashboard-filter="true"]');
  filterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(filterForm);
    state.financeDashboardFilters = {
      q: String(formData.get('q') || '').trim(),
      status: String(formData.get('status') || '').trim(),
      method: String(formData.get('method') || '').trim(),
      date_from: String(formData.get('date_from') || '').trim(),
      date_to: String(formData.get('date_to') || '').trim()
    };
    renderDashboardHome(contentElement, state);
    bindDashboardHome(state, session, bridge);
    updateInlineStatus('Finance dashboard filters applied.', 'success');
  });

  const resetFilterButton = contentElement.querySelector('[data-finance-dashboard-filter-reset="true"]');
  resetFilterButton?.addEventListener('click', () => {
    state.financeDashboardFilters = getDefaultFinanceDashboardFilters();
    renderDashboardHome(contentElement, state);
    bindDashboardHome(state, session, bridge);
    updateInlineStatus('Finance dashboard filters reset.', 'success');
  });

  contentElement.querySelectorAll('[data-dashboard-payment-view]').forEach((button) => {
    button.addEventListener('click', () => {
      const transactionId = Number(button.getAttribute('data-transaction-id'));
      if (!transactionId) {
        updateInlineStatus('This transaction could not be opened because its ID is missing.', 'error');
        return;
      }

      state.selectedDashboardTransactionId = transactionId;
      renderDashboardHome(contentElement, state);
      bindDashboardHome(state, session, bridge);
    });
  });

  const closeButton = getElementById('financeDashboardDetailCloseButton');
  if (closeButton) {
    closeButton.onclick = () => {
      state.selectedDashboardTransactionId = null;
      renderDashboardHome(contentElement, state);
      bindDashboardHome(state, session, bridge);
    };
  }
}

function updateViewHeader(state) {
  const titleElement = getElementById('desktopViewTitle');
  const subtitleElement = getElementById('desktopViewSubtitle');
  const displayName = [state.profile.first_name, state.profile.last_name].filter(Boolean).join(' ') || 'User';

  if (titleElement) {
    titleElement.textContent = 'Welcome back!';
  }

  if (subtitleElement) {
    subtitleElement.textContent = displayName;
  }
}

function updateInlineStatus(message, type = null) {
  const element = getElementById('desktopInlineStatus');
  if (!element) {
    return;
  }

  element.className = 'status-banner';
  if (!message) {
    element.classList.add('is-empty');
    element.textContent = '';
    return;
  }

  if (type) {
    element.classList.add(type);
  }

  element.textContent = message;
}

async function performAuthenticatedRequest(session, bridge, endpoint, options = {}, bridgeMethod = null, bridgePayload = null) {
  if (bridge && bridgeMethod && typeof bridge[bridgeMethod] === 'function') {
    if (Array.isArray(bridgePayload)) {
      return bridge[bridgeMethod](session.token, ...bridgePayload);
    }

    return bridge[bridgeMethod](session.token, bridgePayload);
  }

  return requestJson(endpoint, {
    ...options,
    headers: {
      Authorization: `Bearer ${session.token}`,
      ...(options.headers || {})
    }
  });
}

function buildDashboardShell(profile, role) {
  const userName = [profile.first_name, profile.last_name].filter(Boolean).join(' ') || 'User';
  const displayRole = String(role || 'customer').replace(/_/g, ' ');
  const sidebarLinks = getSidebarLinks(role);

  return `
    <div class="desktop-dashboard-shell">
      <div class="sidebar desktop-sidebar" id="sidebar">
        <div class="brand desktop-brand">
          <i class="fas fa-link"></i>
          <h5>SkillLink</h5>
        </div>
        <nav class="sidebar-nav" id="desktopSidebarNav">
          ${sidebarLinks.map((item) => `
            <a href="#${item.route}" data-route="${item.route}" class="${item.route === 'dashboard' ? 'active' : ''}">
              <i class="${item.icon}"></i>
              <span>${item.label}</span>
            </a>
          `).join('')}
          <a href="#profile" data-route="profile">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
          </a>
          <a href="#settings" data-route="settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
          </a>
          <a href="#logout" id="sidebarLogoutLink" data-route="logout" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </nav>
      </div>

      <div class="main-content desktop-main-content" id="mainContent">
        <div class="topbar desktop-topbar">
          <div class="welcome desktop-welcome">
            <button class="btn btn-sm btn-light desktop-toggle-button" id="toggleSidebar" type="button">
              <i class="fas fa-bars"></i>
            </button>
            <div>
              <h6 id="desktopViewTitle">Welcome back!</h6>
              <p class="mb-0 text-muted" id="desktopViewSubtitle">${userName}</p>
            </div>
          </div>
          <div class="user-profile desktop-user-profile">
            <span class="role-badge desktop-role-badge">${displayRole}</span>
            <div>
              <p class="mb-0">${profile.email || '-'}</p>
              <small class="text-muted">Last login: Today</small>
            </div>
          </div>
        </div>

        <div class="container-fluid page-content desktop-page-content">
          <p id="desktopInlineStatus" class="status-banner is-empty" aria-live="polite"></p>
          <div id="desktopContentArea"></div>
        </div>

        <div class="footer desktop-footer">
          <p>&copy; ${new Date().getFullYear()} Skill Link Services. All rights reserved.</p>
        </div>
      </div>
    </div>
  `;
}

function setDashboardVisible(isVisible) {
  const authStage = getElementById('authStage');
  const authSection = getElementById('authSection');
  const registerSection = getElementById('registerSection');
  const dashboardSection = getElementById('dashboardSection');

  authStage?.classList.toggle('hidden', isVisible);
  authSection?.classList.toggle('hidden', isVisible);
  registerSection?.classList.add('hidden');
  dashboardSection?.classList.toggle('hidden', !isVisible);

  if (isVisible) {
    document.body.classList.remove('auth-mode');
    document.body.classList.add('dashboard-mode');
  } else {
    document.body.classList.remove('dashboard-mode');
    document.body.classList.add('auth-mode');
  }
}

async function renderRoute(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  state.routeRequestSeq = (state.routeRequestSeq || 0) + 1;
  const requestSeq = state.routeRequestSeq;

  updateViewHeader(state);
  if (state.currentRoute !== 'dashboard') {
    contentElement.innerHTML = createRouteLoadingView(state.currentRoute, state.role);
  }

  try {
    await ensureRouteData(state, session, bridge);
  } catch (error) {
    if (requestSeq !== state.routeRequestSeq) {
      return;
    }
    contentElement.innerHTML = createRouteErrorView(state.currentRoute, state.role, error.message || 'Failed to load this page from the backend.');
    updateInlineStatus(error.message || 'Failed to load this page from the backend.', 'error');
    return;
  }

  if (requestSeq !== state.routeRequestSeq) {
    return;
  }

  try {
    if (state.currentRoute === 'dashboard') {
      renderDashboardHome(contentElement, state);
      bindDashboardHome(state, session, bridge);
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'profile') {
      contentElement.innerHTML = createProfileForm(state.profile);
      bindProfileForm(state, session, bridge);
      return;
    }

    if (state.currentRoute === 'settings') {
      contentElement.innerHTML = createSettingsForm(state);
      bindPasswordForm(session, bridge);
      return;
    }

    if (state.currentRoute === 'services') {
      contentElement.innerHTML = createServicesView(state);
      bindServicesView(state, session, bridge);
      return;
    }

    if (state.currentRoute === 'available-jobs') {
      contentElement.innerHTML = createBookingsView(state);
      bindBookingsView(state, session, bridge);
      return;
    }

    if (['bookings', 'my-jobs'].includes(state.currentRoute)) {
      if (state.currentRoute === 'bookings' && ['admin', 'super_admin'].includes(state.role)) {
        contentElement.innerHTML = createAdminBookingsView(state, {
          createInfoBanner,
          createMetricGrid,
          createPageHeading,
          escapeHtml,
          formatCurrency,
          formatDate,
          formatValue
        });
        bindAdminBookingsView(state, session, bridge, {
          performAuthenticatedRequest,
          renderRoute,
          setStatus,
          updateInlineStatus
        });
      } else {
        contentElement.innerHTML = createBookingsView(state);
        bindBookingsView(state, session, bridge);
      }
      return;
    }

    if (state.currentRoute === 'payments') {
      contentElement.innerHTML = createPaymentsView(state);
      bindCashierPaymentsView(state, session, bridge, {
        performAuthenticatedRequest,
        renderRoute,
        setStatus,
        updateInlineStatus
      });
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'earnings') {
      contentElement.innerHTML = createEarningsView(state);
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'users') {
      contentElement.innerHTML = createUsersView(state);
      bindUsersView(state, session, bridge);
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'user-analytics') {
      contentElement.innerHTML = createAdminUserAnalyticsView(state, {
        createInfoBanner,
        createMetricGrid,
        createPageHeading,
        escapeHtml,
        formatCurrency,
        formatDate,
        formatValue
      });
      bindAdminUserAnalyticsView(state, session, bridge, {
        performAuthenticatedRequest,
        updateInlineStatus,
        helpers: {
          createInfoBanner,
          createMetricGrid,
          createPageHeading,
          escapeHtml,
          formatCurrency,
          formatDate,
          formatValue
        }
      });
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'backups') {
      contentElement.innerHTML = createAdminBackupsView(state, {
        createInfoBanner,
        createMetricGrid,
        createPageHeading,
        escapeHtml,
        formatDate
      });
      bindAdminBackupsView(state, session, bridge, {
        performAuthenticatedRequest,
        renderRoute,
        setStatus,
        updateInlineStatus
      });
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'rates') {
      contentElement.innerHTML = createAdminReviewsView(state, {
        createInfoBanner,
        createMetricGrid,
        createPageHeading,
        escapeHtml,
        formatDate,
        formatValue
      });
      bindAdminReviewsView(state, session, bridge, {
        performAuthenticatedRequest,
        renderRoute,
        setStatus,
        updateInlineStatus,
        createInfoBanner,
        createMetricGrid,
        createPageHeading,
        escapeHtml,
        formatDate,
        formatValue
      });
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'pending-workers') {
      contentElement.innerHTML = createPendingWorkersView(state);
      bindPendingWorkersView(state, session, bridge);
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'security') {
      contentElement.innerHTML = createAdminSecurityView(state, {
        createInfoBanner,
        createMetricGrid,
        createPageHeading,
        escapeHtml,
        formatDate,
        formatRelativeCount,
        getSeverityTone
      });
      bindAdminSecurityView(state, session, bridge, {
        performAuthenticatedRequest,
        renderRoute,
        setStatus,
        updateInlineStatus
      });
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'records') {
      if (['admin', 'super_admin'].includes(state.role)) {
        contentElement.innerHTML = createAdminRecordsView(state, {
          createInfoBanner,
          createMetricGrid,
          createPageHeading,
          escapeHtml,
          formatCurrency,
          formatDate,
          formatValue
        });
        bindAdminRecordsView(state, session, bridge, {
          performAuthenticatedRequest,
          renderRoute,
          setStatus,
          updateInlineStatus,
          helpers: {
            createInfoBanner,
            createMetricGrid,
            createPageHeading,
            escapeHtml,
            formatCurrency,
            formatDate,
            formatValue
          }
        });
      } else {
        contentElement.innerHTML = createRecordsView(state);
      }
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'payouts') {
      contentElement.innerHTML = createCashierPayoutsView(state, {
        createInfoBanner,
        createMetricGrid,
        escapeHtml,
        formatCurrency,
        formatDate
      });
      bindCashierPaymentsView(state, session, bridge, {
        performAuthenticatedRequest,
        renderRoute,
        setStatus,
        updateInlineStatus
      });
      updateInlineStatus('', null);
      return;
    }

    if (state.currentRoute === 'reports') {
      contentElement.innerHTML = createCashierReportsView(state, {
        createInfoBanner,
        createMetricGrid,
        formatCurrency,
        formatDate,
        formatValue
      });
      bindCashierPaymentsView(state, session, bridge, {
        performAuthenticatedRequest,
        renderRoute,
        setStatus,
        updateInlineStatus
      });
      updateInlineStatus('', null);
      return;
    }

    contentElement.innerHTML = createPlaceholderView(state.currentRoute, state.role);
    updateInlineStatus('', null);
  } catch (error) {
    contentElement.innerHTML = createRouteErrorView(state.currentRoute, state.role, error.message || 'This page could not be rendered.');
    updateInlineStatus(error.message || 'This page could not be rendered.', 'error');
  }
}

function setActiveNav(route) {
  const nav = getElementById('desktopSidebarNav');
  if (!nav) {
    return;
  }

  nav.querySelectorAll('[data-route]').forEach((link) => {
    link.classList.toggle('active', link.getAttribute('data-route') === route);
  });
}

function resetRouteState(state) {
  state.routeNotice = '';
  state.routeBookings = [];
  state.routeBookingStats = {};
  state.routeBackups = [];
  state.routeBackupSummary = {};
  state.routeReviews = [];
  state.routeReviewStats = {};
  state.routeReviewWorkers = [];
  state.routeTopReviewWorkers = [];
  state.routePayments = [];
  state.routeUsers = [];
  state.selectedAnalyticsUser = null;
  state.selectedAnalyticsUserDashboard = {};
  state.routePendingWorkers = [];
  state.routeUserStats = {};
  state.routeUsersPagination = { total: 0, page: 1, limit: 25, pages: 1 };
  state.routePaymentStats = {};
  state.routeRevenueReport = [];
  state.routeRecords = [];
  state.selectedAdminRecord = null;
  state.routeSettingsSummary = {};
  state.routeSecurityDashboard = {};
  state.routeSecurityStats = {};
  state.routeSecurityEvents = [];
  state.routeSecurityNotifications = [];
  state.routeBlockedIps = [];
}

function bindProfileForm(state, session, bridge) {
  const form = getElementById('profileForm');
  const saveButton = getElementById('profileSaveButton');
  if (!form || !saveButton) {
    return;
  }

  form.onsubmit = async (event) => {
    event.preventDefault();
    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';

    const payload = Object.fromEntries(new FormData(form).entries());
    const requestedEmail = String(payload.email || '').trim().toLowerCase();
    const currentEmail = String(state.profile?.email || '').trim().toLowerCase();
    delete payload.email;

    try {
      const response = await performAuthenticatedRequest(
        session,
        bridge,
        '/api/auth/profile',
        {
          method: 'PUT',
          body: payload
        },
        'updateProfile',
        payload
      );

      state.profile = response?.data || state.profile;

      let successMessage = response?.message || 'Profile updated successfully.';
      if (requestedEmail && requestedEmail !== currentEmail) {
        const requestResponse = await performAuthenticatedRequest(
          session,
          bridge,
          '/api/auth/change-email/request',
          {
            method: 'POST',
            body: {
              email: requestedEmail,
            }
          }
        );

        updateInlineStatus(requestResponse?.message || 'Verification code sent to your new email address.', 'success');
        const otp = await requestOtpCode(requestedEmail, {
          title: 'Verify new email',
          iconClass: 'fas fa-envelope-open-text',
          submitLabel: 'Update Email',
          message: `Enter the 6-digit code sent to ${requestedEmail}.`
        });

        const confirmResponse = await performAuthenticatedRequest(
          session,
          bridge,
          '/api/auth/change-email/confirm',
          {
            method: 'POST',
            body: {
              email: requestedEmail,
              otp,
            }
          }
        );

        state.profile = confirmResponse?.data || state.profile;
        successMessage = confirmResponse?.message || 'Profile and email updated successfully.';
      }

      saveSession(session.token, state.profile);
      updateInlineStatus(successMessage, 'success');
      setStatus(successMessage, 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to update profile.', 'error');
      setStatus(error.message || 'Failed to update profile.', 'error');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Save Profile';
    }
  };
}

function bindPasswordForm(session, bridge) {
  const form = getElementById('passwordForm');
  const saveButton = getElementById('passwordSaveButton');
  if (!form || !saveButton) {
    return;
  }

  form.onsubmit = async (event) => {
    event.preventDefault();
    saveButton.disabled = true;
    saveButton.textContent = 'Updating...';

    const payload = Object.fromEntries(new FormData(form).entries());

    try {
      const response = await performAuthenticatedRequest(
        session,
        bridge,
        '/api/auth/change-password',
        {
          method: 'POST',
          body: payload
        },
        'changePassword',
        payload
      );

      form.reset();
      updateInlineStatus(response?.message || 'Password changed successfully.', 'success');
      setStatus('Password changed successfully.', 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to change password.', 'error');
      setStatus(error.message || 'Failed to change password.', 'error');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Update Password';
    }
  };
}

function bindSidebarNavigation(state, session, bridge) {
  const nav = getElementById('desktopSidebarNav');
  if (!nav) {
    return;
  }

  nav.onclick = async (event) => {
    const link = event.target.closest('[data-route]');
    if (!link) {
      return;
    }

    event.preventDefault();
    const route = link.getAttribute('data-route');

    if (route === 'logout') {
      const logoutButton = getElementById('logoutButton');
      logoutButton?.click();
      return;
    }

    state.currentRoute = route;
    setActiveNav(route);
    await renderRoute(state, session, bridge);
  };
}

async function ensureRouteData(state, session, bridge) {
  resetRouteState(state);

  if (state.currentRoute === 'services') {
    const categoryKey = state.serviceCategory || '__all__';
    state.serviceFilters = state.serviceFilters || getDefaultServiceFilters(state.role);
    const statusKey = (state.role === 'admin' || state.role === 'super_admin')
      ? (state.serviceFilters.status || 'active')
      : 'active';
    const cacheKey = `${categoryKey}::${statusKey}`;
    const serviceRouteNotice = state.role === 'admin' || state.role === 'super_admin'
      ? 'Manage service offerings here. Create, update, and retire catalog items directly from the desktop.'
      : '';
    state.serviceCatalogCache = state.serviceCatalogCache || {};

    if (state.serviceCatalogCache[cacheKey] && state.serviceCategories?.length) {
      state.services = state.serviceCatalogCache[cacheKey];
      state.routeNotice = serviceRouteNotice;
      return;
    }

    const query = new URLSearchParams();
    if (state.serviceCategory) {
      query.set('category', state.serviceCategory);
    }
    if (state.role === 'admin' || state.role === 'super_admin') {
      query.set('status', state.serviceFilters.status || 'active');
    }

    const [servicesResponse, categoriesResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, `/api/services${query.toString() ? `?${query.toString()}` : ''}`, { method: 'GET' }),
      state.serviceCategories?.length ? Promise.resolve({ data: state.serviceCategories }) : performAuthenticatedRequest(session, bridge, '/api/services/categories', { method: 'GET' })
    ]);

    state.services = servicesResponse?.data || [];
    state.serviceCategories = Array.isArray(categoriesResponse?.data)
      ? categoriesResponse.data
      : Object.keys(categoriesResponse?.data || {});
    state.serviceCatalogCache[cacheKey] = state.services;
    state.routeNotice = serviceRouteNotice;
    return;
  }

  if (['bookings', 'my-jobs'].includes(state.currentRoute)) {
    if (state.currentRoute === 'bookings' && ['admin', 'super_admin'].includes(state.role)) {
      await loadAdminBookingsRouteData(state, session, bridge, performAuthenticatedRequest);
      return;
    }

    const bookingResponse = await performAuthenticatedRequest(session, bridge, '/api/dashboard/bookings?limit=50', { method: 'GET' });
    const allBookings = bookingResponse?.bookings || [];

    state.routeBookings = allBookings;
    return;
  }

  if (state.currentRoute === 'available-jobs') {
    const availableJobsResponse = await performAuthenticatedRequest(
      session,
      bridge,
      '/api/bookings/available?limit=50',
      { method: 'GET' },
      'getAvailableJobs',
      50
    );

    state.routeBookings = availableJobsResponse?.data || [];
    return;
  }

  if (state.currentRoute === 'earnings' && state.role === 'worker') {
    const earningsResponse = await performAuthenticatedRequest(session, bridge, `/api/payments/worker-earnings/${state.profile.id}`, { method: 'GET' });
    state.earningsData = earningsResponse?.data || { total_earnings: 0, payouts: [] };
    return;
  }

  if (state.currentRoute === 'settings') {
    const settingsResponse = await performAuthenticatedRequest(session, bridge, '/api/auth/settings', { method: 'GET' });
    state.routeSettingsSummary = settingsResponse?.data || {};
    state.routeNotice = 'Settings data is loaded from the backend session and activity trackers.';
    return;
  }

  if (state.currentRoute === 'payments') {
    if (state.role === 'customer') {
      const paymentsResponse = await performAuthenticatedRequest(session, bridge, '/api/payments/mine', { method: 'GET' });
      state.routePayments = paymentsResponse?.data || [];
      state.routeNotice = 'This payment history is loaded directly from the backend for your account.';
      return;
    }
  }

  if (state.currentRoute === 'users') {
    state.userFilters = state.userFilters || getDefaultUserFilters();
    const { q = '', userType = '', status = 'active', showDeleted = false, page = 1, limit = 25 } = state.userFilters;
    const normalizedQuery = String(q || '').trim();
    const params = new URLSearchParams({ limit: String(limit), page: String(page) });
    if (userType) {
      params.set('user_type', userType);
    }
    if (showDeleted) {
      params.set('show_deleted', '1');
    }
    if (status && !normalizedQuery) {
      params.set('status', status);
    }

    const [usersResponse, statisticsResponse] = await Promise.all([
      performAuthenticatedRequest(
        session,
        bridge,
        normalizedQuery
          ? `/api/users/search?q=${encodeURIComponent(normalizedQuery)}${userType ? `&user_type=${encodeURIComponent(userType)}` : ''}${status ? `&status=${encodeURIComponent(status)}` : ''}${showDeleted ? '&show_deleted=1' : ''}&limit=${encodeURIComponent(String(limit))}&page=${encodeURIComponent(String(page))}`
          : `/api/users?${params.toString()}`,
        { method: 'GET' }
      ),
      performAuthenticatedRequest(session, bridge, '/api/users/statistics', { method: 'GET' }).catch(() => ({ data: {} }))
    ]);

    const users = usersResponse?.data || [];

    state.routeUsers = users;
    state.routeUsersPagination = usersResponse?.pagination || { total: users.length, page, limit, pages: 1 };
    state.routeUserStats = statisticsResponse?.data || {};
    state.routeNotice = showDeleted
      ? (normalizedQuery
        ? `Showing archived search results for "${normalizedQuery}" in the desktop admin workspace.`
        : 'Archived users are shown here. Restore them to move them back into the active user workspace.')
      : (normalizedQuery
        ? `Showing search results for "${normalizedQuery}" in the desktop admin workspace.`
        : 'This desktop page mirrors the website admin user manager with inline editing and archive actions.');
    cacheUsers(state, users);

    if (state.selectedUserId) {
      selectUserFromCache(state, state.selectedUserId);
    }

    return;
  }

  if (state.currentRoute === 'user-analytics') {
    await loadAdminUserAnalyticsRouteData(state, session, bridge, performAuthenticatedRequest);
    return;
  }

  if (state.currentRoute === 'backups') {
    await loadAdminBackupsRouteData(state, session, bridge, performAuthenticatedRequest);
    return;
  }

  if (state.currentRoute === 'rates') {
    await loadAdminReviewsRouteData(state, session, bridge, performAuthenticatedRequest);
    return;
  }

  if (state.currentRoute === 'pending-workers') {
    const workersResponse = await performAuthenticatedRequest(
      session,
      bridge,
      '/api/users/pending-workers?limit=100',
      { method: 'GET' }
    );

    state.routePendingWorkers = workersResponse?.data || [];
    state.routeNotice = state.routePendingWorkers.length
      ? 'Pending worker applications are waiting for admin review.'
      : 'There are no pending worker applications at the moment.';

    if (state.selectedPendingWorkerId) {
      try {
        const detailResponse = await performAuthenticatedRequest(
          session,
          bridge,
          `/api/users/${state.selectedPendingWorkerId}`,
          { method: 'GET' }
        );
        const detail = detailResponse?.data || null;
        if (detail && detail.user_type === 'worker' && detail.status === 'pending') {
          state.selectedPendingWorker = detail;
        } else {
          state.selectedPendingWorker = null;
          state.selectedPendingWorkerId = null;
        }
      } catch {
        state.selectedPendingWorker = null;
        state.selectedPendingWorkerId = null;
      }
    }

    return;
  }

  if (state.currentRoute === 'security') {
    await loadAdminSecurityRouteData(state, session, bridge, performAuthenticatedRequest);
    return;
  }

  if (await loadCashierRouteData(state, session, bridge, performAuthenticatedRequest)) {
    return;
  }

  if (state.currentRoute === 'records') {
    if (['admin', 'super_admin'].includes(state.role)) {
      await loadAdminRecordsRouteData(state, session, bridge, performAuthenticatedRequest);
      return;
    }

    try {
      const recordsResponse = await performAuthenticatedRequest(session, bridge, '/api/records?limit=50', { method: 'GET' });
      state.routeRecords = recordsResponse?.data || [];
    } catch (error) {
      state.routeRecords = [];
      state.routeNotice = error.message || 'Service records are not available in the desktop flow yet.';
    }
  }
}

function bindServicesView(state, session, bridge) {
  return bindServicesWorkspaceView(state, session, bridge, {
    performAuthenticatedRequest,
    renderRoute,
    setActiveNav,
    setStatus,
    updateInlineStatus
  });
}

function bindBookingsView(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.querySelectorAll('[data-booking-action]').forEach((button) => {
    button.addEventListener('click', async () => {
      const action = button.getAttribute('data-booking-action');
      const bookingId = button.getAttribute('data-booking-id');
      const workerId = button.getAttribute('data-worker-id');

      if (action === 'review') {
        const panel = getElementById('bookingReviewPanel');
        if (panel) {
          panel.innerHTML = createReviewForm(bookingId, workerId);
          bindReviewForm(state, session, bridge);
        }
        return;
      }

      if (action === 'complete') {
        const booking = findBookingById(state, bookingId);
        const panel = getElementById('bookingReviewPanel');
        if (panel && booking) {
          panel.innerHTML = createCompleteJobForm(booking);
          bindCompleteJobForm(state, session, bridge);
        }
        return;
      }

      button.disabled = true;

      try {
        if (action === 'accept') {
          await performAuthenticatedRequest(
            session,
            bridge,
            `/api/bookings/${bookingId}/accept`,
            {
              method: 'PUT'
            },
            'acceptJob',
            bookingId
          );

          state.currentRoute = 'my-jobs';
          setActiveNav('my-jobs');
        }

        if (action === 'cancel') {
          await performAuthenticatedRequest(session, bridge, `/api/bookings/${bookingId}/cancel`, {
            method: 'PUT',
            body: { reason: 'Cancelled from Desktop app' }
          });
        }

        if (action === 'start') {
          await performAuthenticatedRequest(session, bridge, `/api/bookings/${bookingId}/start`, {
            method: 'PUT'
          });
        }

        updateInlineStatus(`Booking ${action} action completed successfully.`, 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || `Failed to ${action} booking.`, 'error');
      } finally {
        button.disabled = false;
      }
    });
  });
}

function bindReviewForm(state, session, bridge) {
  const form = getElementById('reviewForm');
  const submitButton = getElementById('reviewSubmitButton');
  if (!form || !submitButton) {
    return;
  }

  form.onsubmit = async (event) => {
    event.preventDefault();
    submitButton.disabled = true;
    submitButton.textContent = 'Submitting...';

    const payload = Object.fromEntries(new FormData(form).entries());
    payload.customer_id = String(state.profile.id);

    try {
      const eligibility = await performAuthenticatedRequest(
        session,
        bridge,
        `/api/reviews/can-review?booking_id=${encodeURIComponent(payload.booking_id)}&customer_id=${encodeURIComponent(payload.customer_id)}`,
        { method: 'GET' }
      );

      if (!eligibility?.data?.can_review) {
        throw new Error('This booking is not eligible for review.');
      }

      const response = await performAuthenticatedRequest(session, bridge, '/api/reviews', {
        method: 'POST',
        body: payload
      });

      getElementById('bookingReviewPanel').innerHTML = '';
      updateInlineStatus(response?.message || 'Review submitted successfully.', 'success');
      setStatus('Review submitted successfully.', 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to submit review.', 'error');
      setStatus(error.message || 'Failed to submit review.', 'error');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Submit Review';
    }
  };
}

function bindPendingWorkersView(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.querySelectorAll('[data-pending-worker-row]').forEach((row) => {
    row.addEventListener('click', async () => {
      const workerId = Number(row.getAttribute('data-worker-id'));
      if (!workerId) {
        return;
      }

      const selectedFromList = (state.routePendingWorkers || []).find((worker) => Number(worker.id) === workerId) || null;
      state.selectedPendingWorkerId = workerId;
      state.selectedPendingWorker = selectedFromList;
      renderPendingWorkersViewOnly(state, session, bridge);

      try {
        const detailResponse = await performAuthenticatedRequest(
          session,
          bridge,
          `/api/users/${workerId}`,
          { method: 'GET' }
        );
        const detail = detailResponse?.data || null;
        if (Number(state.selectedPendingWorkerId || 0) !== workerId || !detail) {
          return;
        }

        state.selectedPendingWorker = detail;
        renderPendingWorkersViewOnly(state, session, bridge);
      } catch {
        // Keep the selected row data visible if detail loading fails.
      }
    });
  });

  contentElement.querySelectorAll('[data-pending-worker-action]').forEach((button) => {
    button.addEventListener('click', async () => {
      const action = String(button.getAttribute('data-pending-worker-action') || '');
      const workerId = Number(button.getAttribute('data-worker-id'));
      if (!workerId || !['approve', 'reject'].includes(action)) {
        return;
      }

      const confirmed = typeof window.confirm === 'function'
        ? window.confirm(`${action === 'approve' ? 'Approve' : 'Reject'} this worker application?`)
        : true;
      if (!confirmed) {
        return;
      }

      button.disabled = true;
      button.textContent = action === 'approve' ? 'Approving...' : 'Rejecting...';

      try {
        const endpoint = action === 'approve'
          ? `/api/users/${workerId}/approve-worker`
          : `/api/users/${workerId}/reject-worker`;
        const response = await performAuthenticatedRequest(session, bridge, endpoint, { method: 'POST' });

        state.selectedPendingWorkerId = null;
        state.selectedPendingWorker = null;
        updateInlineStatus(response?.message || `Worker application ${action}d successfully.`, 'success');
        setStatus(response?.message || `Worker application ${action}d successfully.`, 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || `Failed to ${action} the worker application.`, 'error');
        setStatus(error.message || `Failed to ${action} the worker application.`, 'error');
      } finally {
        button.disabled = false;
        button.textContent = action === 'approve' ? 'Approve Worker' : 'Reject Worker';
      }
    });
  });
}

function syncUserWorkerFields() {
  const roleField = getElementById('admin_user_type');
  const workerFields = getElementById('desktopWorkerFields');
  if (!workerFields) {
    return;
  }

  const roleValue = roleField?.value || document.querySelector('input[name="user_type"]')?.value || '';
  workerFields.classList.toggle('hidden', roleValue !== 'worker');
}

function buildUserPayload(form) {
  const payload = Object.fromEntries(new FormData(form).entries());
  if ((payload.user_type || '') !== 'worker') {
    delete payload.skills;
    delete payload.experience_years;
    delete payload.commission_rate;
    delete payload.service_city;
    delete payload.service_radius_km;
    delete payload.work_latitude;
    delete payload.work_longitude;
  }

  return payload;
}

function bindUsersView(state, session, bridge) {
  const filterForm = getElementById('usersFilterForm');
  const resetButton = getElementById('usersResetFiltersButton');
  const archivedToggleButton = getElementById('usersArchivedToggleButton');
  const createButton = getElementById('usersCreateButton');
  const contentElement = getElementById('desktopContentArea');

  if (filterForm) {
    filterForm.onsubmit = async (event) => {
      event.preventDefault();
      const formData = new FormData(filterForm);
      state.userFilters = {
        q: String(formData.get('q') || '').trim(),
        userType: String(formData.get('userType') || ''),
        status: String(formData.get('status') || ''),
        showDeleted: Boolean(state.userFilters?.showDeleted),
        limit: Number(formData.get('limit') || 25),
        page: 1
      };
      await renderRoute(state, session, bridge);
    };
  }

  if (resetButton) {
    resetButton.onclick = async () => {
      state.userFilters = {
        ...getDefaultUserFilters(),
        showDeleted: Boolean(state.userFilters?.showDeleted),
        status: state.userFilters?.showDeleted ? '' : 'active'
      };
      state.userEditorMode = 'idle';
      state.selectedUserId = null;
      state.selectedUser = null;
      await renderRoute(state, session, bridge);
    };
  }

  if (archivedToggleButton) {
    archivedToggleButton.onclick = async () => {
      const nextShowDeleted = !Boolean(state.userFilters?.showDeleted);
      state.userFilters = {
        ...(state.userFilters || getDefaultUserFilters()),
        showDeleted: nextShowDeleted,
        status: nextShowDeleted ? '' : 'active',
        page: 1
      };
      state.userEditorMode = 'idle';
      state.selectedUserId = null;
      state.selectedUser = null;
      await renderRoute(state, session, bridge);
    };
  }

  if (createButton) {
    createButton.onclick = () => {
      state.userFormError = '';
      state.userFormErrors = {};
      state.userEditorMode = 'create';
      state.selectedUserId = null;
      state.selectedUser = null;
      renderUsersViewOnly(state, session, bridge);
    };
  }

  if (contentElement) {
    contentElement.querySelectorAll('[data-user-row]').forEach((row) => {
      row.addEventListener('click', async (event) => {
        if (event.target.closest('[data-user-action]')) {
          return;
        }

        const userId = Number(row.getAttribute('data-user-id'));
        state.userFormError = '';
        state.userFormErrors = {};
        state.userEditorMode = 'view';
        selectUserFromCache(state, userId);
        renderUsersViewOnly(state, session, bridge);
      });
    });

    contentElement.querySelectorAll('[data-user-action]').forEach((button) => {
      button.addEventListener('click', async () => {
        const action = button.getAttribute('data-user-action');
        const userId = Number(button.getAttribute('data-user-id'));

        if (action === 'archive') {
          const confirmed = typeof window.confirm === 'function'
            ? window.confirm('Archive this user?')
            : true;
          if (!confirmed) {
            return;
          }

          button.disabled = true;
          try {
            const response = await performAuthenticatedRequest(session, bridge, `/api/users/${userId}`, {
              method: 'DELETE'
            });
            updateInlineStatus(response?.message || 'User archived successfully.', 'success');
            setStatus(response?.message || 'User archived successfully.', 'success');
            if (Number(state.selectedUserId || 0) === userId) {
              state.selectedUserId = null;
              state.selectedUser = null;
              state.userEditorMode = 'idle';
            }
            if (state.userDetailsById) {
              delete state.userDetailsById[userId];
            }
            await renderRoute(state, session, bridge);
          } catch (error) {
            updateInlineStatus(error.message || 'Failed to archive user.', 'error');
            setStatus(error.message || 'Failed to archive user.', 'error');
          } finally {
            button.disabled = false;
          }
          return;
        }

        state.userEditorMode = action === 'edit' ? 'edit' : 'view';
        state.userFormError = '';
        state.userFormErrors = {};
        selectUserFromCache(state, userId);
        renderUsersViewOnly(state, session, bridge);
      });
    });
  }

  const editorForm = getElementById('userEditorForm');
  const saveButton = getElementById('userSaveButton');
  const editButton = getElementById('userEditButton');
  const archiveButton = getElementById('userArchiveButton');
  const restoreButton = getElementById('userRestoreButton');
  const permanentDeleteButton = getElementById('userPermanentDeleteButton');
  const clearButton = getElementById('userCancelSelectionButton');
  const newButton = getElementById('userCreateNewButton');
  const prevPageButton = getElementById('usersPrevPageButton');
  const nextPageButton = getElementById('usersNextPageButton');
  const roleField = getElementById('admin_user_type');

  if (prevPageButton) {
    prevPageButton.onclick = async () => {
      const currentPage = Number(state.userFilters?.page || 1);
      if (currentPage <= 1) {
        return;
      }
      state.userFilters = {
        ...(state.userFilters || getDefaultUserFilters()),
        page: currentPage - 1
      };
      await renderRoute(state, session, bridge);
    };
  }

  if (nextPageButton) {
    nextPageButton.onclick = async () => {
      const currentPage = Number(state.userFilters?.page || 1);
      const totalPages = Number(state.routeUsersPagination?.pages || 1);
      if (currentPage >= totalPages) {
        return;
      }
      state.userFilters = {
        ...(state.userFilters || getDefaultUserFilters()),
        page: currentPage + 1
      };
      await renderRoute(state, session, bridge);
    };
  }

  roleField?.addEventListener('change', syncUserWorkerFields);
  syncUserWorkerFields();

  if (editButton) {
    editButton.onclick = () => {
      state.userFormError = '';
      state.userFormErrors = {};
      state.userEditorMode = 'edit';
      renderUsersViewOnly(state, session, bridge);
    };
  }

  if (archiveButton) {
    archiveButton.onclick = async () => {
      if (!state.selectedUserId) {
        return;
      }

      const confirmed = typeof window.confirm === 'function'
        ? window.confirm('Soft delete this user? The account will be archived, not permanently removed.')
        : true;
      if (!confirmed) {
        return;
      }

      archiveButton.disabled = true;
      archiveButton.textContent = 'Deleting...';

      try {
        const archivedUserId = state.selectedUserId;
        const response = await performAuthenticatedRequest(session, bridge, `/api/users/${archivedUserId}`, {
          method: 'DELETE'
        });

        state.selectedUserId = null;
        state.selectedUser = null;
        state.userEditorMode = 'idle';
        if (state.userDetailsById) {
          delete state.userDetailsById[archivedUserId];
        }
        updateInlineStatus(response?.message || 'User archived successfully.', 'success');
        setStatus(response?.message || 'User archived successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to archive user.', 'error');
        setStatus(error.message || 'Failed to archive user.', 'error');
      } finally {
        archiveButton.disabled = false;
        archiveButton.textContent = 'Archive User';
      }
    };
  }

  if (restoreButton) {
    restoreButton.onclick = async () => {
      if (!state.selectedUserId) {
        return;
      }

      const confirmed = typeof window.confirm === 'function'
        ? window.confirm('Restore this user?')
        : true;
      if (!confirmed) {
        return;
      }

      restoreButton.disabled = true;
      restoreButton.textContent = 'Restoring...';

      try {
        const restoredUserId = state.selectedUserId;
        const response = await performAuthenticatedRequest(session, bridge, `/api/users/${restoredUserId}/restore`, {
          method: 'POST'
        });

        const restoredUser = response?.data || null;
        if (restoredUser?.id) {
          state.userDetailsById = state.userDetailsById || {};
          state.userDetailsById[restoredUser.id] = {
            ...(state.userDetailsById[restoredUser.id] || {}),
            ...restoredUser
          };
        }
        state.selectedUserId = null;
        state.selectedUser = null;
        state.userEditorMode = 'idle';
        updateInlineStatus(response?.message || 'User restored successfully.', 'success');
        setStatus(response?.message || 'User restored successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to restore user.', 'error');
        setStatus(error.message || 'Failed to restore user.', 'error');
      } finally {
        restoreButton.disabled = false;
        restoreButton.textContent = 'Restore User';
      }
    };
  }

  if (permanentDeleteButton) {
    permanentDeleteButton.onclick = async () => {
      if (!state.selectedUserId) {
        return;
      }

      const confirmed = typeof window.confirm === 'function'
        ? window.confirm('Delete this archived user permanently? This cannot be undone.')
        : true;
      if (!confirmed) {
        return;
      }

      permanentDeleteButton.disabled = true;
      permanentDeleteButton.textContent = 'Deleting...';

      try {
        const deletedUserId = state.selectedUserId;
        const response = await performAuthenticatedRequest(session, bridge, `/api/users/${deletedUserId}/permanent`, {
          method: 'DELETE'
        });

        if (state.userDetailsById) {
          delete state.userDetailsById[deletedUserId];
        }
        state.selectedUserId = null;
        state.selectedUser = null;
        state.userEditorMode = 'idle';
        updateInlineStatus(response?.message || 'User permanently deleted successfully.', 'success');
        setStatus(response?.message || 'User permanently deleted successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to permanently delete user.', 'error');
        setStatus(error.message || 'Failed to permanently delete user.', 'error');
      } finally {
        permanentDeleteButton.disabled = false;
        permanentDeleteButton.textContent = 'Delete Permanently';
      }
    };
  }

  if (clearButton) {
    clearButton.onclick = () => {
      state.userFormError = '';
      state.userFormErrors = {};
      state.selectedUserId = null;
      state.selectedUser = null;
      state.userEditorMode = 'idle';
      renderUsersViewOnly(state, session, bridge);
    };
  }

  if (newButton) {
    newButton.onclick = () => {
      state.userFormError = '';
      state.userFormErrors = {};
      state.selectedUserId = null;
      state.selectedUser = null;
      state.userEditorMode = 'create';
      renderUsersViewOnly(state, session, bridge);
    };
  }

  if (editorForm && saveButton) {
    editorForm.onsubmit = async (event) => {
      event.preventDefault();
      saveButton.disabled = true;
      saveButton.textContent = state.userEditorMode === 'edit' ? 'Saving...' : 'Creating...';

      const payload = buildUserPayload(editorForm);
      const isEditing = state.userEditorMode === 'edit' && payload.id;

      try {
        const response = await performAuthenticatedRequest(
          session,
          bridge,
          isEditing ? `/api/users/${payload.id}` : '/api/users',
          {
            method: isEditing ? 'PUT' : 'POST',
            body: payload
          }
        );

        const savedUser = response?.data || null;
        state.userFormError = '';
        state.userFormErrors = {};
        if (savedUser?.id) {
          state.userDetailsById = state.userDetailsById || {};
          state.userDetailsById[savedUser.id] = {
            ...(state.userDetailsById[savedUser.id] || {}),
            ...savedUser
          };
        }
        state.selectedUserId = savedUser?.id || state.selectedUserId;
        state.selectedUser = savedUser;
        state.userEditorMode = savedUser ? 'view' : 'idle';
        updateInlineStatus(response?.message || (isEditing ? 'User updated successfully.' : 'User created successfully.'), 'success');
        setStatus(response?.message || (isEditing ? 'User updated successfully.' : 'User created successfully.'), 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        state.userFormError = error.message || 'Failed to save user.';
        state.userFormErrors = normalizeUserFormErrors(error?.details);
        renderUsersViewOnly(state, session, bridge);
        updateInlineStatus(error.message || 'Failed to save user.', 'error');
        setStatus(error.message || 'Failed to save user.', 'error');
      } finally {
        saveButton.disabled = false;
        saveButton.textContent = isEditing ? 'Save Changes' : 'Create User';
      }
    };
  }
}

function bindCompleteJobForm(state, session, bridge) {
  const form = getElementById('completeJobForm');
  const submitButton = getElementById('completeJobSubmitButton');
  if (!form || !submitButton) {
    return;
  }

  form.onsubmit = async (event) => {
    event.preventDefault();
    submitButton.disabled = true;
    submitButton.textContent = 'Completing...';

    const payload = Object.fromEntries(new FormData(form).entries());
    const bookingId = payload.booking_id;
    delete payload.booking_id;

    try {
      const response = await performAuthenticatedRequest(
        session,
        bridge,
        `/api/bookings/${bookingId}/complete-with-payment`,
        {
          method: 'POST',
          body: payload
        },
        'completeJobWithPayment',
        [bookingId, payload]
      );

      getElementById('bookingReviewPanel').innerHTML = '';
      updateInlineStatus(response?.message || 'Job completed and payment recorded successfully.', 'success');
      setStatus('Job completed and payment recorded successfully.', 'success');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to complete job.', 'error');
      setStatus(error.message || 'Failed to complete job.', 'error');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Complete and Record Payment';
    }
  };
}

export async function renderDashboardView(session = getSession()) {
  const bridge = getDesktopBridge();

  if (!getElementById('dashboardSection')) {
    console.error('Dashboard section not found');
    return;
  }

  if (!session?.token) {
    console.error('No session token available');
    return;
  }

  setDashboardVisible(true);

  const requestProfile = performAuthenticatedRequest(
    session,
    bridge,
    '/api/auth/profile',
    { method: 'GET' },
    'getProfile'
  );
  const requestStats = performAuthenticatedRequest(
    session,
    bridge,
    '/api/dashboard/stats',
    { method: 'GET' },
    'getDashboardStats'
  );
  const requestBookings = bridge
    ? bridge.getDashboardBookings(session.token, 8)
    : requestJson('/api/dashboard/bookings?limit=8', {
        method: 'GET',
        headers: { Authorization: `Bearer ${session.token}` }
      });

  try {
    const [profileResponse, statsResponse, bookingsResponse] = await Promise.all([
      requestProfile.catch(() => ({ data: session.user || {} })),
      requestStats.catch(() => ({ stats: {} })),
      requestBookings.catch(() => ({ bookings: [] }))
    ]);

    const profile = profileResponse?.data || session.user || {};
    const role = profile.user_type || session.user?.user_type || 'customer';
    const dashboardSection = getElementById('dashboardSection');

    if (!dashboardSection) {
      return;
    }

    dashboardSection.innerHTML = buildDashboardShell(profile, role);

    const state = {
      currentRoute: 'dashboard',
      profile,
      stats: statsResponse?.stats || {},
      bookings: bookingsResponse?.bookings || [],
      role,
      adminUserAnalyticsFilters: getDefaultAdminUserAnalyticsFilters(),
      adminSecurityFilters: getDefaultAdminSecurityFilters(),
      selectedAnalyticsUserId: null,
      selectedAnalyticsUser: null,
      selectedAnalyticsUserDashboard: {},
      adminReviewFilters: getDefaultAdminReviewFilters(),
      selectedAdminReviewId: null,
      selectedAdminReview: null,
      adminRecordFilters: getDefaultAdminRecordFilters(),
      selectedAdminRecordId: null,
      selectedAdminRecord: null,
      selectedAdminBookingId: null,
      selectedAdminBooking: null,
      selectedAdminBookingWorkers: [],
      selectedPendingWorkerId: null,
      selectedPendingWorker: null,
      userFilters: getDefaultUserFilters(),
      userEditorMode: 'idle',
      selectedUserId: null,
      selectedUser: null,
      userDetailsById: {}
    };

    const performLogout = async (event) => {
      if (event) {
        event.preventDefault();
      }

      try {
        await performAuthenticatedRequest(
          session,
          bridge,
          '/api/auth/logout',
          { method: 'POST' },
          'logout'
        );
      } catch {
        // Ignore backend logout errors for local cleanup.
      }

      clearSession();
      setDashboardVisible(false);
      getElementById('loginForm')?.reset();
      getElementById('registerForm')?.reset();
      setStatus('Logged out successfully.', null);
    };

    getElementById('logoutButton')?.addEventListener('click', performLogout);
    getElementById('sidebarLogoutLink')?.addEventListener('click', performLogout);

    bindSidebarNavigation(state, session, bridge);
    await renderRoute(state, session, bridge);

    if (typeof window.initSidebarToggle === 'function') {
      window.initSidebarToggle();
    }

    setStatus('Dashboard loaded successfully.', 'success');
    // Start polling for pending worker applications (desktop notification for admins)
    try {
      const initialPending = Number(state.stats?.pending_workers || 0);
      let lastPending = initialPending;

      const pollPending = async () => {
        try {
          const resp = bridge
            ? await bridge.getDashboardData(session.token)
            : await requestJson('/api/dashboard/data', { method: 'GET', headers: { Authorization: `Bearer ${session.token}` } });

          const data = resp?.data || resp?.stats || {};
          const newPending = Number(data?.stats?.pending_workers ?? (Array.isArray(resp?.data?.pendingWorkerApplications) ? resp.data.pendingWorkerApplications.length : 0));

          if (Number.isFinite(newPending) && newPending > lastPending) {
            const diff = newPending - lastPending;
            try {
              new Notification('New Worker Applications', { body: `${diff} new application(s) pending approval.` });
            } catch (nerr) {
              console.log('Notification failed:', nerr);
            }
          }

          lastPending = newPending;
        } catch (err) {
          console.error('Pending poll error:', err);
        }
      };

      // Poll every 20 seconds
      setInterval(pollPending, 20000);
    } catch (e) {
      console.error('Failed to start pending worker poll:', e);
    }
  } catch (error) {
    console.error('Dashboard render error:', error);
    setStatus(error.message || 'Failed to load dashboard data.', 'error');
  }
}
