import { getElementById } from '../../core/dom.js';

export function getDefaultAdminRecordFilters() {
  return {
    q: '',
    status: '',
    paymentStatus: ''
  };
}

function getPersonName(record, prefix) {
  const fullName = String(record?.[`${prefix}_name`] || '').trim();
  if (fullName) {
    return fullName;
  }

  return [record?.[`${prefix}_first_name`], record?.[`${prefix}_last_name`]].filter(Boolean).join(' ').trim() || `Unknown ${prefix}`;
}

function normalizeRecordStatus(value, fallback = 'pending') {
  return String(value || fallback).trim().toLowerCase();
}

function getRecordSearchText(record) {
  return [
    record?.payment_ref,
    record?.address_text,
    record?.service_name,
    getPersonName(record, 'customer'),
    getPersonName(record, 'provider'),
    record?.customer_note,
    record?.provider_note,
    record?.admin_note
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
}

function filterRecords(records = [], filters = {}) {
  const query = String(filters.q || '').trim().toLowerCase();
  const status = normalizeRecordStatus(filters.status, '');
  const paymentStatus = normalizeRecordStatus(filters.paymentStatus, '');

  return (records || []).filter((record) => {
    const matchesQuery = !query || getRecordSearchText(record).includes(query);
    const matchesStatus = !status || normalizeRecordStatus(record.status) === status;
    const matchesPaymentStatus = !paymentStatus || normalizeRecordStatus(record.payment_status, 'unpaid') === paymentStatus;

    return matchesQuery && matchesStatus && matchesPaymentStatus;
  });
}

function createRecordFilterCard(state, helpers) {
  const { escapeHtml } = helpers;
  const filters = state.adminRecordFilters || getDefaultAdminRecordFilters();

  return `
    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> Record Filters
      </div>
      <div class="card-body desktop-card-body">
        <form id="adminRecordFilterForm" class="desktop-filter-grid">
          <div class="field">
            <label for="adminRecordQuery">Search</label>
            <input id="adminRecordQuery" name="q" type="text" value="${escapeHtml(filters.q || '')}" placeholder="Reference, customer, provider, service, notes" />
          </div>
          <div class="field">
            <label for="adminRecordStatus">Status</label>
            <select id="adminRecordStatus" name="status">
              <option value="" ${!filters.status ? 'selected' : ''}>All statuses</option>
              ${['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'].map((status) => `
                <option value="${status}" ${normalizeRecordStatus(filters.status, '') === status ? 'selected' : ''}>${status.replace(/_/g, ' ')}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="adminRecordPaymentStatus">Payment</label>
            <select id="adminRecordPaymentStatus" name="paymentStatus">
              <option value="" ${!filters.paymentStatus ? 'selected' : ''}>All payment states</option>
              ${['unpaid', 'partial', 'paid', 'refunded'].map((status) => `
                <option value="${status}" ${normalizeRecordStatus(filters.paymentStatus, '') === status ? 'selected' : ''}>${status}</option>
              `).join('')}
            </select>
          </div>
          <div class="desktop-form-actions">
            <button type="submit" class="action-button">Apply Filters</button>
            <button type="button" class="ghost-button" id="adminRecordResetFiltersButton">Reset</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createRecordEmptyPanel() {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-file-invoice"></i> Record Workspace
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          <div>
            <h5 class="mb-2">Select a record</h5>
            <p class="mb-0 text-muted">Open a service record to inspect its details and update status, payment state, or notes from Desktop.</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function createRecordEditorPanel(state, helpers) {
  const { escapeHtml, formatCurrency, formatDate, formatValue } = helpers;
  const record = state.selectedAdminRecord || null;
  if (!record) {
    return createRecordEmptyPanel();
  }

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-file-invoice"></i> Record Workspace</span>
        <span class="badge badge-${record.status || 'pending'}">${escapeHtml(normalizeRecordStatus(record.status).replace(/_/g, ' '))}</span>
      </div>
      <div class="card-body desktop-card-body">
        <h5 class="mb-2">${escapeHtml(record.service_name || `Record #${record.id}`)}</h5>
        <p class="text-muted mb-3">${escapeHtml(record.payment_ref || `Record #${record.id}`)}</p>

        <div class="desktop-insight-grid">
          <div class="desktop-insight-tile">
            <span>Customer</span>
            <strong>${escapeHtml(getPersonName(record, 'customer'))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Provider</span>
            <strong>${escapeHtml(getPersonName(record, 'provider'))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Total Amount</span>
            <strong>${formatCurrency(record.total_amount ?? 0)}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Scheduled</span>
            <strong>${formatDate(record.scheduled_at || record.created_at)}</strong>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Address</h6>
          <div class="border rounded p-3">${escapeHtml(record.address_text || 'No service address recorded.')}</div>
        </div>

        <form id="adminRecordEditorForm" class="desktop-form-grid mt-4">
          <input type="hidden" name="id" value="${escapeHtml(record.id || '')}" />
          <div class="form-row">
            <div class="field">
              <label for="admin_record_status">Status</label>
              <select id="admin_record_status" name="status">
                ${['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'].map((status) => `
                  <option value="${status}" ${normalizeRecordStatus(record.status) === status ? 'selected' : ''}>${status.replace(/_/g, ' ')}</option>
                `).join('')}
              </select>
            </div>
            <div class="field">
              <label for="admin_record_payment_status">Payment Status</label>
              <select id="admin_record_payment_status" name="payment_status">
                ${['unpaid', 'partial', 'paid', 'refunded'].map((status) => `
                  <option value="${status}" ${normalizeRecordStatus(record.payment_status, 'unpaid') === status ? 'selected' : ''}>${status}</option>
                `).join('')}
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="admin_record_labor_fee">Labor Fee</label>
              <input id="admin_record_labor_fee" name="labor_fee" type="number" min="0" step="0.01" value="${escapeHtml(record.labor_fee ?? 0)}" />
            </div>
            <div class="field">
              <label for="admin_record_platform_fee">Platform Fee</label>
              <input id="admin_record_platform_fee" name="platform_fee" type="number" min="0" step="0.01" value="${escapeHtml(record.platform_fee ?? 0)}" />
            </div>
          </div>

          <div class="field">
            <label for="admin_record_payment_ref">Payment Reference</label>
            <input id="admin_record_payment_ref" name="payment_ref" type="text" value="${escapeHtml(record.payment_ref || '')}" />
          </div>

          <div class="field">
            <label for="admin_record_admin_note">Admin Note</label>
            <textarea id="admin_record_admin_note" name="admin_note">${escapeHtml(record.admin_note || '')}</textarea>
          </div>

          <div class="desktop-form-actions">
            <button id="adminRecordSaveButton" type="submit" class="action-button">Save Record</button>
          </div>
        </form>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Customer Note</h6>
          <div class="border rounded p-3">${escapeHtml(record.customer_note || 'No customer note recorded.')}</div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Provider Note</h6>
          <div class="border rounded p-3">${escapeHtml(record.provider_note || 'No provider note recorded.')}</div>
        </div>
      </div>
    </div>
  `;
}

function buildViewHelpers(tools) {
  return tools.helpers;
}

export function createAdminRecordsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, createPageHeading, escapeHtml, formatCurrency, formatDate } = helpers;
  const records = filterRecords(state.routeRecords || [], state.adminRecordFilters || getDefaultAdminRecordFilters());
  const selectedRecordId = Number(state.selectedAdminRecordId || 0);
  const completedCount = records.filter((record) => normalizeRecordStatus(record.status) === 'completed').length;
  const paidCount = records.filter((record) => normalizeRecordStatus(record.payment_status, 'unpaid') === 'paid').length;

  return `
    ${createPageHeading('fas fa-file-invoice', 'Service Records')}
    ${createInfoBanner(state.routeNotice || 'Track operational records here and keep service status, payment state, and notes up to date.')}
    ${createMetricGrid([
      { icon: 'fas fa-file-lines', value: records.length, label: 'Visible Records', tone: 'primary' },
      { icon: 'fas fa-circle-check', value: completedCount, label: 'Completed', tone: 'success' },
      { icon: 'fas fa-wallet', value: paidCount, label: 'Paid', tone: 'info' },
      { icon: 'fas fa-peso-sign', value: formatCurrency(records.reduce((sum, record) => sum + Number(record.total_amount || 0), 0)), label: 'Visible Total', tone: 'warning' }
    ])}

    ${createRecordFilterCard(state, helpers)}

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-list"></i> Record Queue
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive desktop-users-list-scroll">
            <table class="table table-hover desktop-users-table">
              <thead>
                <tr>
                  <th>Record</th>
                  <th>Status</th>
                  <th>Scheduled</th>
                </tr>
              </thead>
              <tbody>
                ${records.length ? records.map((record) => `
                  <tr
                    class="${selectedRecordId === Number(record.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-admin-record-row="true"
                    data-record-id="${record.id}"
                  >
                    <td>
                      <strong class="desktop-user-name">${escapeHtml(record.payment_ref || `Record #${record.id}`)}</strong>
                      <div class="desktop-table-subtext">${escapeHtml(getPersonName(record, 'customer'))} • ${escapeHtml(record.service_name || 'Service record')}</div>
                    </td>
                    <td><span class="badge badge-${record.status || 'pending'}">${escapeHtml(normalizeRecordStatus(record.status).replace(/_/g, ' '))}</span></td>
                    <td>${formatDate(record.scheduled_at || record.created_at)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No records matched the current filters.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createRecordEditorPanel(state, helpers)}
      </div>
    </div>
  `;
}

function rerenderAdminRecordsView(state, session, bridge, tools) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.innerHTML = createAdminRecordsView(state, buildViewHelpers(tools));
  bindAdminRecordsView(state, session, bridge, tools);
}

export async function loadAdminRecordsRouteData(state, session, bridge, performAuthenticatedRequest) {
  state.adminRecordFilters = {
    ...getDefaultAdminRecordFilters(),
    ...(state.adminRecordFilters || {})
  };

  const recordsResponse = await performAuthenticatedRequest(session, bridge, '/api/records?limit=50', { method: 'GET' });
  const records = recordsResponse?.data || [];
  state.routeRecords = records;
  state.routeNotice = records.length
    ? 'Service records are available for admin review and inline updates from Desktop.'
    : 'No service records were returned from the backend right now.';

  const selectedStillExists = records.some((record) => Number(record.id) === Number(state.selectedAdminRecordId || 0));
  if (!selectedStillExists) {
    state.selectedAdminRecordId = Number(records[0]?.id || 0) || null;
  }

  if (!state.selectedAdminRecordId) {
    state.selectedAdminRecord = null;
    return;
  }

  try {
    const detailResponse = await performAuthenticatedRequest(
      session,
      bridge,
      `/api/records/${state.selectedAdminRecordId}`,
      { method: 'GET' }
    );
    state.selectedAdminRecord = detailResponse?.data || null;
  } catch {
    state.selectedAdminRecord = records.find((record) => Number(record.id) === Number(state.selectedAdminRecordId || 0)) || null;
  }
}

export function bindAdminRecordsView(state, session, bridge, tools) {
  const { performAuthenticatedRequest, renderRoute, setStatus, updateInlineStatus } = tools;
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  const filterForm = getElementById('adminRecordFilterForm');
  filterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(filterForm);
    state.adminRecordFilters = {
      q: String(formData.get('q') || '').trim(),
      status: String(formData.get('status') || '').trim(),
      paymentStatus: String(formData.get('paymentStatus') || '').trim()
    };
    rerenderAdminRecordsView(state, session, bridge, tools);
    updateInlineStatus('Record filters applied.', 'success');
  });

  getElementById('adminRecordResetFiltersButton')?.addEventListener('click', () => {
    state.adminRecordFilters = getDefaultAdminRecordFilters();
    rerenderAdminRecordsView(state, session, bridge, tools);
    updateInlineStatus('Record filters reset.', 'success');
  });

  contentElement.querySelectorAll('[data-admin-record-row]').forEach((row) => {
    row.addEventListener('click', async () => {
      const recordId = Number(row.getAttribute('data-record-id'));
      if (!recordId) {
        return;
      }

      const selectedFromList = (state.routeRecords || []).find((record) => Number(record.id) === recordId) || null;
      state.selectedAdminRecordId = recordId;
      state.selectedAdminRecord = selectedFromList;
      rerenderAdminRecordsView(state, session, bridge, tools);

      try {
        const detailResponse = await performAuthenticatedRequest(
          session,
          bridge,
          `/api/records/${recordId}`,
          { method: 'GET' }
        );
        const detail = detailResponse?.data || null;
        if (Number(state.selectedAdminRecordId || 0) !== recordId || !detail) {
          return;
        }

        state.selectedAdminRecord = detail;
        rerenderAdminRecordsView(state, session, bridge, tools);
      } catch {
        // Keep locally available record data visible if detail loading fails.
      }
    });
  });

  const recordForm = getElementById('adminRecordEditorForm');
  const recordSaveButton = getElementById('adminRecordSaveButton');
  if (!recordForm || !recordSaveButton) {
    return;
  }

  recordForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(recordForm).entries());
    const recordId = Number(payload.id || 0);
    if (!recordId) {
      updateInlineStatus('This record could not be updated because its ID is missing.', 'error');
      return;
    }

    delete payload.id;
    recordSaveButton.disabled = true;
    recordSaveButton.textContent = 'Saving...';

    try {
      const response = await performAuthenticatedRequest(session, bridge, `/api/records/${recordId}`, {
        method: 'PUT',
        body: payload
      });

      updateInlineStatus(response?.message || 'Record updated successfully.', 'success');
      setStatus(response?.message || 'Record updated successfully.', 'success');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to update the selected record.', 'error');
      setStatus(error.message || 'Failed to update the selected record.', 'error');
    } finally {
      recordSaveButton.disabled = false;
      recordSaveButton.textContent = 'Save Record';
    }
  });
}
