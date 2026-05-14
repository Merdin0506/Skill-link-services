import { getElementById } from '../../core/dom.js';

function formatParticipantName(booking, prefix) {
  if (!prefix) {
    const directName = String(booking?.name || booking?.full_name || '').trim();
    if (directName) {
      return directName;
    }

    const firstName = String(booking?.first_name || '').trim();
    const lastName = String(booking?.last_name || '').trim();
    const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
    return fullName || 'Unnamed worker';
  }

  const directName = String(booking?.[`${prefix}_name`] || '').trim();
  if (directName) {
    return directName;
  }

  const firstName = String(booking?.[`${prefix}_first_name`] || '').trim();
  const lastName = String(booking?.[`${prefix}_last_name`] || '').trim();
  const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
  return fullName || 'Unassigned';
}

function getBookingAmount(booking) {
  return Number(booking?.total_fee ?? (Number(booking?.labor_fee || 0) + Number(booking?.materials_fee || 0)));
}

function createEmptyBookingDetailPanel() {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-calendar-day"></i> Booking Details
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          <div>
            <h5 class="mb-2">Select a booking</h5>
            <p class="mb-0 text-muted">Review the full booking details and assign an available worker from this panel.</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function createAssignmentBlock(state, helpers) {
  const { escapeHtml } = helpers;
  const booking = state.selectedAdminBooking || null;
  const workers = Array.isArray(state.selectedAdminBookingWorkers) ? state.selectedAdminBookingWorkers : [];

  if (!booking) {
    return '';
  }

  if (String(booking.status || '') !== 'pending') {
    return `
      <div class="desktop-inline-banner">
        This booking is already ${escapeHtml(String(booking.status || 'processed').replace(/_/g, ' '))}. Desktop assignment is available while a booking is still pending.
      </div>
    `;
  }

  if (!workers.length) {
    return `
      <div class="desktop-inline-banner warning">
        No active workers matched this service category right now, so this booking cannot be assigned yet.
      </div>
    `;
  }

  return `
    <form id="adminBookingAssignForm" class="desktop-form-grid">
      <input type="hidden" name="booking_id" value="${booking.id}" />
      <div class="field">
        <label for="admin_booking_worker_id">Available Worker</label>
        <select id="admin_booking_worker_id" name="worker_id" required>
          <option value="">Select worker</option>
          ${workers.map((worker) => `
            <option value="${worker.id}">
              ${escapeHtml(formatParticipantName(worker, ''))}${worker.distance_km !== null && worker.distance_km !== undefined ? ` (${escapeHtml(String(worker.distance_km))} km)` : ''}
            </option>
          `).join('')}
        </select>
      </div>
      <div class="desktop-form-actions">
        <button id="adminBookingAssignButton" type="submit" class="action-button">Assign Worker</button>
      </div>
    </form>
  `;
}

function createBookingDetailPanel(state, helpers) {
  const { escapeHtml, formatCurrency, formatDate, formatValue } = helpers;
  const booking = state.selectedAdminBooking || null;

  if (!booking) {
    return createEmptyBookingDetailPanel();
  }

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-calendar-day"></i> Booking Details</span>
        <span class="badge badge-${booking.status || 'pending'}">${escapeHtml(String(booking.status || 'pending').replace(/_/g, ' '))}</span>
      </div>
      <div class="card-body desktop-card-body">
        <h5 class="mb-2">${escapeHtml(booking.title || booking.service_name || `Booking #${booking.id}`)}</h5>
        <p class="text-muted mb-3">${escapeHtml(booking.booking_reference || `Reference #${booking.id}`)}</p>

        <div class="desktop-insight-grid">
          <div class="desktop-insight-tile">
            <span>Customer</span>
            <strong>${escapeHtml(formatParticipantName(booking, 'customer'))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Current Worker</span>
            <strong>${escapeHtml(formatParticipantName(booking, 'worker'))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Service</span>
            <strong>${escapeHtml(booking.service_name || '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Category</span>
            <strong>${escapeHtml(String(booking.service_category || booking.category || '-').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Scheduled</span>
            <strong>${formatDate(booking.scheduled_date || booking.created_at)}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Total Fee</span>
            <strong>${formatCurrency(getBookingAmount(booking))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Priority</span>
            <strong>${escapeHtml(String(booking.priority || 'standard').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Available Workers</span>
            <strong>${formatValue((state.selectedAdminBookingWorkers || []).length)}</strong>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Location</h6>
          <div class="border rounded p-3">${escapeHtml(booking.location_address || booking.address || 'No location provided.')}</div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Description</h6>
          <div class="border rounded p-3">${escapeHtml(booking.description || 'No description provided for this booking.')}</div>
        </div>

        ${booking.notes ? `
          <div class="mt-4">
            <h6 class="text-muted mb-2">Notes</h6>
            <div class="border rounded p-3">${escapeHtml(booking.notes)}</div>
          </div>
        ` : ''}

        <div class="mt-4">
          <h6 class="text-muted mb-2">Worker Assignment</h6>
          ${createAssignmentBlock(state, helpers)}
        </div>
      </div>
    </div>
  `;
}

function buildViewHelpers(tools) {
  return {
    createInfoBanner: tools.createInfoBanner,
    createMetricGrid: tools.createMetricGrid,
    createPageHeading: tools.createPageHeading,
    escapeHtml: tools.escapeHtml,
    formatCurrency: tools.formatCurrency,
    formatDate: tools.formatDate,
    formatValue: tools.formatValue
  };
}

export function createAdminBookingsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, createPageHeading, escapeHtml, formatDate, formatValue } = helpers;
  const bookings = Array.isArray(state.routeBookings) ? state.routeBookings : [];
  const stats = state.routeBookingStats || {};
  const selectedBookingId = Number(state.selectedAdminBookingId || 0);

  return `
    ${createPageHeading('fas fa-calendar-check', 'Bookings')}
    ${createInfoBanner(state.routeNotice || 'Manage bookings and assign pending jobs to available workers.')}
    ${createMetricGrid([
      { icon: 'fas fa-hourglass-half', value: stats.pending_bookings ?? 0, label: 'Pending', tone: 'warning' },
      { icon: 'fas fa-user-check', value: stats.assigned_bookings ?? 0, label: 'Assigned', tone: 'info' },
      { icon: 'fas fa-screwdriver-wrench', value: stats.in_progress_bookings ?? 0, label: 'In Progress', tone: 'primary' },
      { icon: 'fas fa-circle-check', value: stats.completed_bookings ?? 0, label: 'Completed', tone: 'success' }
    ])}

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-list"></i> Booking Queue
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive desktop-users-list-scroll">
            <table class="table table-hover desktop-users-table">
              <thead>
                <tr>
                  <th>Booking</th>
                  <th>Status</th>
                  <th>Scheduled</th>
                </tr>
              </thead>
              <tbody>
                ${bookings.length ? bookings.map((booking) => `
                  <tr
                    class="${selectedBookingId === Number(booking.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-admin-booking-row="true"
                    data-booking-id="${booking.id}"
                  >
                    <td>
                      <strong class="desktop-user-name">${escapeHtml(booking.booking_reference || `Booking #${booking.id}`)}</strong>
                      <div class="desktop-table-subtext">${escapeHtml(formatParticipantName(booking, 'customer'))} • ${escapeHtml(booking.service_name || booking.title || 'Untitled booking')}</div>
                    </td>
                    <td><span class="badge badge-${booking.status || 'pending'}">${escapeHtml(String(booking.status || 'pending').replace(/_/g, ' '))}</span></td>
                    <td>${formatDate(booking.scheduled_date || booking.created_at)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No bookings are available in the admin queue right now.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createBookingDetailPanel(state, { escapeHtml, formatCurrency: helpers.formatCurrency, formatDate, formatValue })}
      </div>
    </div>
  `;
}

function rerenderAdminBookingsView(state, session, bridge, tools) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.innerHTML = createAdminBookingsView(state, buildViewHelpers(tools));
  bindAdminBookingsView(state, session, bridge, tools);
}

export async function loadAdminBookingsRouteData(state, session, bridge, performAuthenticatedRequest) {
  const [bookingResponse, statisticsResponse] = await Promise.all([
    performAuthenticatedRequest(session, bridge, '/api/dashboard/bookings?limit=50', { method: 'GET' }),
    performAuthenticatedRequest(session, bridge, '/api/bookings/statistics', { method: 'GET' }).catch(() => ({ data: {} }))
  ]);

  state.routeBookings = bookingResponse?.bookings || [];
  state.routeBookingStats = statisticsResponse?.data || {};
  state.routeNotice = 'Manage the booking pipeline here and assign pending bookings to active workers without leaving Desktop.';

  if (!state.routeBookings.length) {
    state.selectedAdminBookingId = null;
    state.selectedAdminBooking = null;
    state.selectedAdminBookingWorkers = [];
    return;
  }

  const bookingStillExists = state.routeBookings.some((booking) => Number(booking.id) === Number(state.selectedAdminBookingId || 0));
  if (!bookingStillExists) {
    state.selectedAdminBookingId = Number(state.routeBookings[0]?.id || 0) || null;
  }

  if (!state.selectedAdminBookingId) {
    state.selectedAdminBooking = null;
    state.selectedAdminBookingWorkers = [];
    return;
  }

  const detailResponse = await performAuthenticatedRequest(
    session,
    bridge,
    `/api/bookings/${state.selectedAdminBookingId}`,
    { method: 'GET' }
  );
  const detail = detailResponse?.data || null;
  state.selectedAdminBooking = detail;

  if (!detail || !detail.service_id || String(detail.status || '') !== 'pending') {
    state.selectedAdminBookingWorkers = [];
    return;
  }

  const workersResponse = await performAuthenticatedRequest(
    session,
    bridge,
    `/api/bookings/available-workers/${detail.service_id}?booking_id=${encodeURIComponent(String(detail.id || state.selectedAdminBookingId))}`,
    { method: 'GET' }
  );
  state.selectedAdminBookingWorkers = workersResponse?.data || [];
}

export function bindAdminBookingsView(state, session, bridge, tools) {
  const { performAuthenticatedRequest, renderRoute, setStatus, updateInlineStatus } = tools;
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.querySelectorAll('[data-admin-booking-row]').forEach((row) => {
    row.addEventListener('click', async () => {
      const bookingId = Number(row.getAttribute('data-booking-id'));
      if (!bookingId) {
        return;
      }

      const selectedFromList = (state.routeBookings || []).find((booking) => Number(booking.id) === bookingId) || null;
      state.selectedAdminBookingId = bookingId;
      state.selectedAdminBooking = selectedFromList;
      state.selectedAdminBookingWorkers = [];
      rerenderAdminBookingsView(state, session, bridge, tools);

      try {
        const detailResponse = await performAuthenticatedRequest(
          session,
          bridge,
          `/api/bookings/${bookingId}`,
          { method: 'GET' }
        );
        const detail = detailResponse?.data || null;
        if (Number(state.selectedAdminBookingId || 0) !== bookingId || !detail) {
          return;
        }

        state.selectedAdminBooking = detail;

        if (detail.service_id && String(detail.status || '') === 'pending') {
          const workersResponse = await performAuthenticatedRequest(
            session,
            bridge,
            `/api/bookings/available-workers/${detail.service_id}?booking_id=${encodeURIComponent(String(detail.id || bookingId))}`,
            { method: 'GET' }
          );
          if (Number(state.selectedAdminBookingId || 0) === bookingId) {
            state.selectedAdminBookingWorkers = workersResponse?.data || [];
          }
        } else if (Number(state.selectedAdminBookingId || 0) === bookingId) {
          state.selectedAdminBookingWorkers = [];
        }

        rerenderAdminBookingsView(state, session, bridge, tools);
      } catch {
        // Keep the selected row data visible even if the detail request fails.
      }
    });
  });

  const form = getElementById('adminBookingAssignForm');
  const submitButton = getElementById('adminBookingAssignButton');
  if (!form || !submitButton) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const payload = Object.fromEntries(new FormData(form).entries());
    if (!payload.worker_id) {
      updateInlineStatus('Select a worker before assigning this booking.', 'error');
      setStatus('Select a worker before assigning this booking.', 'error');
      return;
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Assigning...';

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/bookings/assign-worker', {
        method: 'POST',
        body: {
          booking_id: payload.booking_id,
          worker_id: payload.worker_id,
          assigned_by: state.profile.id
        }
      });

      state.selectedAdminBookingId = Number(payload.booking_id || state.selectedAdminBookingId || 0) || null;
      state.selectedAdminBooking = null;
      state.selectedAdminBookingWorkers = [];
      updateInlineStatus(response?.message || 'Worker assigned successfully.', 'success');
      setStatus(response?.message || 'Worker assigned successfully.', 'success');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to assign worker to this booking.', 'error');
      setStatus(error.message || 'Failed to assign worker to this booking.', 'error');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Assign Worker';
    }
  });
}
