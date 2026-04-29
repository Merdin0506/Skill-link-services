import { getElementById } from '../../core/dom.js';
import { clearSession, getSession, saveSession } from '../../core/storage.js';
import { setStatus } from '../../core/status.js';
import { requestJson } from '../../services/apiClient.js';
import { getDesktopBridge } from '../../services/desktopBridge.js';
import { getRoleTemplate } from './roleTemplates.js';

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
      return `
        <tr>
          <td><strong>#${booking.payment_reference || reference}</strong></td>
          <td>${reference}</td>
          <td>${formatCurrency(booking.amount ?? booking.total_fee ?? 0)}</td>
          <td><span class="badge bg-secondary">${String(booking.payment_method || 'Unpaid').replace(/_/g, ' ')}</span></td>
          <td><span class="badge ${statusClass}">${status.replace(/_/g, ' ')}</span></td>
          <td>${formatDate(booking.transaction_created_at || booking.created_at)}</td>
          <td><button type="button" class="ghost-button table-action" disabled>View</button></td>
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

function getSidebarLinks(role) {
  if (role === 'admin' || role === 'super_admin') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', route: 'dashboard' },
      { icon: 'fas fa-users', label: 'Users', route: 'users' },
      { icon: 'fas fa-calendar-check', label: 'Bookings', route: 'bookings' },
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
    profile: 'Profile',
    settings: 'Settings',
    users: 'Users',
    bookings: role === 'customer' ? 'My Bookings' : 'Bookings',
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
              <input id="profile_email" type="email" value="${profile.email || ''}" disabled />
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
          ` : ''}

          <div class="desktop-form-actions">
            <button id="profileSaveButton" type="submit" class="action-button">Save Profile</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createSettingsForm() {
  return `
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
  const categories = state.serviceCategories || [];
  const services = state.services || [];

  return `
    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-list"></i> Browse Services
      </div>
      <div class="card-body desktop-card-body">
        <div class="desktop-chip-row">
          <button type="button" class="ghost-button desktop-chip ${!state.serviceCategory ? 'is-active' : ''}" data-category="">
            All
          </button>
          ${categories.map((category) => `
            <button type="button" class="ghost-button desktop-chip ${state.serviceCategory === category ? 'is-active' : ''}" data-category="${category}">
              ${String(category).replace(/_/g, ' ')}
            </button>
          `).join('')}
        </div>
      </div>
    </div>

    <div class="desktop-service-grid">
      ${services.length ? services.map((service) => `
        <div class="card desktop-card desktop-service-card">
          <div class="card-body desktop-card-body">
            <div class="desktop-service-head">
              <div>
                <h5>${service.name || 'Service'}</h5>
                <span class="badge bg-secondary">${service.category || 'general'}</span>
              </div>
              <strong>${formatCurrency(service.base_price ?? 0)}</strong>
            </div>
            <p class="text-muted mb-3">${service.description || 'No description provided.'}</p>
            <div class="desktop-service-meta">
              <span><i class="fas fa-clock"></i> ${service.estimated_duration || '-'} mins</span>
              <span><i class="fas fa-toggle-on"></i> ${service.status || 'active'}</span>
            </div>
            <div class="desktop-form-actions mt-3">
              <button type="button" class="action-button" data-service-book="${service.id}">Book Service</button>
            </div>
          </div>
        </div>
      `).join('') : `
        <div class="card desktop-card">
          <div class="card-body desktop-card-body">
            <div class="chart-placeholder desktop-placeholder-card">No services available for this category yet.</div>
          </div>
        </div>
      `}
    </div>

    <div class="card desktop-card mt-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-calendar-plus"></i> Create Booking
      </div>
      <div class="card-body desktop-card-body">
        <form id="bookingCreateForm" class="desktop-form-grid">
          <input id="booking_service_id" name="service_id" type="hidden" value="${state.selectedService?.id || ''}" />
          <div class="form-row">
            <div class="field">
              <label for="booking_title">Title</label>
              <input id="booking_title" name="title" type="text" value="${state.selectedService ? `Booking for ${state.selectedService.name}` : ''}" required />
            </div>
            <div class="field">
              <label for="booking_priority">Priority</label>
              <select id="booking_priority" name="priority" required>
                ${['low', 'medium', 'high', 'urgent'].map((priority) => `
                  <option value="${priority}" ${priority === 'medium' ? 'selected' : ''}>${priority}</option>
                `).join('')}
              </select>
            </div>
          </div>
          <div class="field">
            <label for="booking_description">Description</label>
            <textarea id="booking_description" name="description">${state.selectedService?.description || ''}</textarea>
          </div>
          <div class="field">
            <label for="booking_location_address">Location Address</label>
            <textarea id="booking_location_address" name="location_address" required>${state.profile.address || ''}</textarea>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="booking_scheduled_date">Scheduled Date</label>
              <input id="booking_scheduled_date" name="scheduled_date" type="date" required />
            </div>
            <div class="field">
              <label for="booking_scheduled_time">Scheduled Time</label>
              <input id="booking_scheduled_time" name="scheduled_time" type="time" required />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="booking_labor_fee">Labor Fee</label>
              <input id="booking_labor_fee" name="labor_fee" type="number" min="1" step="0.01" value="${state.selectedService?.base_price || ''}" required />
            </div>
            <div class="field">
              <label for="booking_materials_fee">Materials Fee</label>
              <input id="booking_materials_fee" name="materials_fee" type="number" min="0" step="0.01" value="0" />
            </div>
          </div>
          <div class="field">
            <label for="booking_notes">Notes</label>
            <textarea id="booking_notes" name="notes"></textarea>
          </div>
          <div class="desktop-form-actions">
            <button id="bookingCreateButton" type="submit" class="action-button">Create Booking</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createBookingsView(state) {
  const bookings = state.routeBookings || [];

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-calendar-check"></i> ${getViewTitle(state.currentRoute, state.role)}
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Title</th>
                <th>Status</th>
                <th>Scheduled</th>
                <th>Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${bookings.length ? bookings.map((booking) => `
                <tr>
                  <td><strong>${booking.booking_reference || booking.id}</strong></td>
                  <td>${booking.title || 'Untitled booking'}</td>
                  <td><span class="badge badge-${booking.status || 'pending'}">${String(booking.status || 'pending').replace(/_/g, ' ')}</span></td>
                  <td>${formatDate(booking.scheduled_date || booking.created_at)}</td>
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

function findBookingById(state, bookingId) {
  return (state.routeBookings || []).find((booking) => String(booking.id) === String(bookingId)) || null;
}

function renderDashboardHome(contentElement, state) {
  const template = getRoleTemplate(state.role);

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
                  ${renderTableRows(state.bookings, state.role)}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

function updateViewHeader(state) {
  const titleElement = getElementById('desktopViewTitle');
  const subtitleElement = getElementById('desktopViewSubtitle');
  const displayName = [state.profile.first_name, state.profile.last_name].filter(Boolean).join(' ') || 'User';

  if (titleElement) {
    titleElement.textContent = getViewTitle(state.currentRoute, state.role);
  }

  if (subtitleElement) {
    subtitleElement.textContent = state.currentRoute === 'dashboard'
      ? `Welcome back, ${displayName}.`
      : `Signed in as ${displayName}.`;
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
          <p>Desktop workspace</p>
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
              <h6 id="desktopViewTitle">Dashboard</h6>
              <p class="mb-0 text-muted" id="desktopViewSubtitle">Welcome back, ${userName}.</p>
            </div>
          </div>
          <div class="user-profile desktop-user-profile">
            <div class="desktop-user-meta">
              <span class="role-badge desktop-role-badge">${displayRole}</span>
            </div>
            <div class="desktop-account-panel">
              <p class="mb-0">${profile.email || '-'}</p>
              <small class="text-muted">Desktop connected to Backend API</small>
            </div>
            <button id="logoutButton" class="ghost-button desktop-ghost-button" type="button">Logout</button>
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

  updateViewHeader(state);
  await ensureRouteData(state, session, bridge);

  if (state.currentRoute === 'dashboard') {
    renderDashboardHome(contentElement, state);
    updateInlineStatus('', null);
    return;
  }

  if (state.currentRoute === 'profile') {
    contentElement.innerHTML = createProfileForm(state.profile);
    bindProfileForm(state, session, bridge);
    return;
  }

  if (state.currentRoute === 'settings') {
    contentElement.innerHTML = createSettingsForm();
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
    contentElement.innerHTML = createBookingsView(state);
    bindBookingsView(state, session, bridge);
    return;
  }

  if (state.currentRoute === 'earnings') {
    contentElement.innerHTML = createEarningsView(state);
    updateInlineStatus('', null);
    return;
  }

  contentElement.innerHTML = createPlaceholderView(state.currentRoute, state.role);
  updateInlineStatus('', null);
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
      saveSession(session.token, state.profile);
      updateInlineStatus(response?.message || 'Profile updated successfully.', 'success');
      setStatus('Profile updated successfully.', 'success');
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
  if (state.currentRoute === 'services') {
    const [servicesResponse, categoriesResponse] = await Promise.all([
      performAuthenticatedRequest(session, bridge, `/api/services${state.serviceCategory ? `?category=${encodeURIComponent(state.serviceCategory)}` : ''}`, { method: 'GET' }),
      state.serviceCategories?.length ? Promise.resolve({ data: state.serviceCategories }) : performAuthenticatedRequest(session, bridge, '/api/services/categories', { method: 'GET' })
    ]);

    state.services = servicesResponse?.data || [];
    state.serviceCategories = categoriesResponse?.data || [];
    return;
  }

  if (['bookings', 'my-jobs'].includes(state.currentRoute)) {
    const bookingResponse = await performAuthenticatedRequest(session, bridge, '/api/dashboard/bookings?limit=50', { method: 'GET' });
    const allBookings = bookingResponse?.bookings || [];

    if (state.currentRoute === 'my-jobs' && state.role === 'worker') {
      state.routeBookings = allBookings;
    } else {
      state.routeBookings = allBookings;
    }
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
  }
}

function bindServicesView(state, session, bridge) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.querySelectorAll('[data-category]').forEach((button) => {
    button.addEventListener('click', async () => {
      state.serviceCategory = button.getAttribute('data-category') || '';
      await renderRoute(state, session, bridge);
    });
  });

  contentElement.querySelectorAll('[data-service-book]').forEach((button) => {
    button.addEventListener('click', () => {
      const serviceId = Number(button.getAttribute('data-service-book'));
      const service = (state.services || []).find((item) => Number(item.id) === serviceId);
      if (!service) {
        return;
      }

      state.selectedService = service;
      const serviceIdInput = getElementById('booking_service_id');
      const titleInput = getElementById('booking_title');
      const descriptionInput = getElementById('booking_description');
      const laborFeeInput = getElementById('booking_labor_fee');

      if (serviceIdInput) {
        serviceIdInput.value = String(service.id);
      }
      if (titleInput) {
        titleInput.value = `Booking for ${service.name}`;
      }
      if (descriptionInput) {
        descriptionInput.value = service.description || '';
      }
      if (laborFeeInput) {
        laborFeeInput.value = String(service.base_price || '');
      }

      updateInlineStatus(`Selected ${service.name}. Complete the booking form below.`, 'success');
    });
  });

  const form = getElementById('bookingCreateForm');
  const submitButton = getElementById('bookingCreateButton');
  if (!form || !submitButton) {
    return;
  }

  form.onsubmit = async (event) => {
    event.preventDefault();
    submitButton.disabled = true;
    submitButton.textContent = 'Creating...';

    const payload = Object.fromEntries(new FormData(form).entries());
    payload.customer_id = String(state.profile.id);

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/bookings', {
        method: 'POST',
        body: payload
      });

      form.reset();
      state.selectedService = null;
      updateInlineStatus(response?.message || 'Booking created successfully.', 'success');
      setStatus('Booking created successfully.', 'success');
      state.currentRoute = 'bookings';
      setActiveNav('bookings');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to create booking.', 'error');
      setStatus(error.message || 'Failed to create booking.', 'error');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Create Booking';
    }
  };
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
      role
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
  } catch (error) {
    console.error('Dashboard render error:', error);
    setStatus(error.message || 'Failed to load dashboard data.', 'error');
  }
}
