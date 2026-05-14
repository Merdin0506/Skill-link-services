import { getElementById } from '../../core/dom.js';

export function getDefaultAdminUserAnalyticsFilters() {
  return {
    q: '',
    userType: '',
    status: ''
  };
}

function normalizeString(value) {
  return String(value || '').trim().toLowerCase();
}

function getUserDisplayName(user) {
  return [user?.first_name, user?.last_name].filter(Boolean).join(' ').trim() || `User #${user?.id || '-'}`;
}

function filterUsers(users = [], filters = {}) {
  const query = normalizeString(filters.q);
  const userType = normalizeString(filters.userType);
  const status = normalizeString(filters.status);

  return (users || []).filter((user) => {
    const searchableText = [
      user.first_name,
      user.last_name,
      user.email,
      user.phone
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    const matchesQuery = !query || searchableText.includes(query);
    const matchesRole = !userType || normalizeString(user.user_type) === userType;
    const matchesStatus = !status || normalizeString(user.status) === status;

    return matchesQuery && matchesRole && matchesStatus;
  });
}

function createUserAnalyticsFilterCard(state, helpers) {
  const { escapeHtml } = helpers;
  const filters = state.adminUserAnalyticsFilters || getDefaultAdminUserAnalyticsFilters();

  return `
    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> User Analytics Filters
      </div>
      <div class="card-body desktop-card-body">
        <form id="adminUserAnalyticsFilterForm" class="desktop-filter-grid">
          <div class="field">
            <label for="adminUserAnalyticsQuery">Search</label>
            <input id="adminUserAnalyticsQuery" name="q" type="text" value="${escapeHtml(filters.q || '')}" placeholder="Name, email, or phone" />
          </div>
          <div class="field">
            <label for="adminUserAnalyticsRole">Role</label>
            <select id="adminUserAnalyticsRole" name="userType">
              <option value="">All roles</option>
              ${['super_admin', 'admin', 'finance', 'worker', 'customer'].map((role) => `
                <option value="${role}" ${normalizeString(filters.userType) === role ? 'selected' : ''}>${role.replace(/_/g, ' ')}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="adminUserAnalyticsStatus">Status</label>
            <select id="adminUserAnalyticsStatus" name="status">
              <option value="">All statuses</option>
              ${['active', 'inactive', 'suspended'].map((status) => `
                <option value="${status}" ${normalizeString(filters.status) === status ? 'selected' : ''}>${status}</option>
              `).join('')}
            </select>
          </div>
          <div class="desktop-form-actions">
            <button type="submit" class="action-button">Apply Filters</button>
            <button type="button" class="ghost-button" id="adminUserAnalyticsResetFiltersButton">Reset</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createEmptyAnalyticsPanel() {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-chart-pie"></i> User Insight Panel
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          <div>
            <h5 class="mb-2">Select a user</h5>
            <p class="mb-0 text-muted">Open a user from the list to inspect role-specific dashboard data and account signals here.</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function createSelectedUserInsightPanel(state, helpers) {
  const { escapeHtml, formatCurrency, formatDate, formatValue } = helpers;
  const user = state.selectedAnalyticsUser || null;
  const dashboard = state.selectedAnalyticsUserDashboard || {};

  if (!user) {
    return createEmptyAnalyticsPanel();
  }

  const metricEntries = Object.entries(dashboard || {}).filter(([, value]) => (
    typeof value === 'number'
    || typeof value === 'string'
    || typeof value === 'boolean'
  ));

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-chart-pie"></i> User Insight Panel</span>
        <span class="badge bg-${normalizeString(user.status) === 'active' ? 'success' : 'secondary'}">${escapeHtml(user.status || 'unknown')}</span>
      </div>
      <div class="card-body desktop-card-body">
        <h5 class="mb-2">${escapeHtml(getUserDisplayName(user))}</h5>
        <p class="text-muted mb-3">${escapeHtml(user.email || 'No email')}</p>

        <div class="desktop-insight-grid">
          <div class="desktop-insight-tile">
            <span>Role</span>
            <strong>${escapeHtml(String(user.user_type || 'user').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Status</span>
            <strong>${escapeHtml(String(user.status || 'unknown').replace(/_/g, ' '))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Joined</span>
            <strong>${formatDate(user.created_at)}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Phone</span>
            <strong>${escapeHtml(user.phone || '-')}</strong>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-3">Dashboard Metrics</h6>
          ${metricEntries.length ? `
            <div class="desktop-insight-grid">
              ${metricEntries.slice(0, 12).map(([key, value]) => `
                <div class="desktop-insight-tile">
                  <span>${escapeHtml(key.replace(/_/g, ' '))}</span>
                  <strong>${/revenue|spent|earnings|amount|total/i.test(key) ? formatCurrency(value) : formatValue(value)}</strong>
                </div>
              `).join('')}
            </div>
          ` : `
            <div class="border rounded p-3 text-muted">No dashboard analytics were returned for this user yet.</div>
          `}
        </div>
      </div>
    </div>
  `;
}

function buildViewHelpers(tools) {
  return tools.helpers;
}

function rerenderAdminUserAnalyticsView(state, session, bridge, tools) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.innerHTML = createAdminUserAnalyticsView(state, buildViewHelpers(tools));
  bindAdminUserAnalyticsView(state, session, bridge, tools);
}

export function createAdminUserAnalyticsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, createPageHeading, escapeHtml, formatValue } = helpers;
  const stats = state.routeUserStats || {};
  const filteredUsers = filterUsers(state.routeUsers || [], state.adminUserAnalyticsFilters || getDefaultAdminUserAnalyticsFilters());
  const selectedUserId = Number(state.selectedAnalyticsUserId || 0);

  return `
    ${createPageHeading('fas fa-chart-line', 'User Analytics')}
    ${createInfoBanner(state.routeNotice || 'Review user distribution, health signals, and role-level activity from Desktop.')}
    ${createMetricGrid([
      { icon: 'fas fa-users', value: stats.total_users ?? 0, label: 'Total Users', tone: 'primary' },
      { icon: 'fas fa-user-gear', value: stats.total_admin_staff ?? 0, label: 'Admin Staff', tone: 'info' },
      { icon: 'fas fa-user-hard-hat', value: stats.total_workers ?? 0, label: 'Workers', tone: 'success' },
      { icon: 'fas fa-user-group', value: stats.total_customers ?? 0, label: 'Customers', tone: 'warning' },
      { icon: 'fas fa-user-check', value: stats.active_users ?? 0, label: 'Active Users', tone: 'success' },
      { icon: 'fas fa-user-slash', value: (stats.inactive_users ?? 0) + (stats.suspended_users ?? 0), label: 'Inactive or Suspended', tone: 'danger' }
    ])}

    ${createUserAnalyticsFilterCard(state, helpers)}

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-users-viewfinder"></i> Analytics User List</span>
          <span class="badge bg-secondary">${formatValue(filteredUsers.length)} visible</span>
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
                ${filteredUsers.length ? filteredUsers.map((user) => `
                  <tr
                    class="${selectedUserId === Number(user.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-admin-user-analytics-row="true"
                    data-user-id="${user.id}"
                  >
                    <td>
                      <strong class="desktop-user-name">${escapeHtml(getUserDisplayName(user))}</strong>
                      <div class="desktop-table-subtext">${escapeHtml(user.email || '-')}</div>
                    </td>
                    <td><span class="badge bg-secondary">${escapeHtml(String(user.user_type || 'user').replace(/_/g, ' '))}</span></td>
                    <td><span class="badge bg-${normalizeString(user.status) === 'active' ? 'success' : 'secondary'}">${escapeHtml(String(user.status || 'unknown').replace(/_/g, ' '))}</span></td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No users matched the analytics filters.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createSelectedUserInsightPanel(state, helpers)}
      </div>
    </div>
  `;
}

export async function loadAdminUserAnalyticsRouteData(state, session, bridge, performAuthenticatedRequest) {
  state.adminUserAnalyticsFilters = {
    ...getDefaultAdminUserAnalyticsFilters(),
    ...(state.adminUserAnalyticsFilters || {})
  };

  const [usersResponse, statisticsResponse] = await Promise.all([
    performAuthenticatedRequest(session, bridge, '/api/users?limit=50&page=1', { method: 'GET' }),
    performAuthenticatedRequest(session, bridge, '/api/users/statistics', { method: 'GET' }).catch(() => ({ data: {} }))
  ]);

  state.routeUsers = usersResponse?.data || [];
  state.routeUsersPagination = usersResponse?.pagination || { total: state.routeUsers.length, page: 1, limit: 50, pages: 1 };
  state.routeUserStats = statisticsResponse?.data || {};
  state.routeNotice = state.routeUsers.length
    ? 'Desktop analytics uses the user index plus per-user dashboard metrics from the backend.'
    : 'No users were returned for analytics right now.';

  const selectedStillExists = state.routeUsers.some((user) => Number(user.id) === Number(state.selectedAnalyticsUserId || 0));
  if (!selectedStillExists) {
    state.selectedAnalyticsUserId = Number(state.routeUsers[0]?.id || 0) || null;
  }

  if (!state.selectedAnalyticsUserId) {
    state.selectedAnalyticsUser = null;
    state.selectedAnalyticsUserDashboard = {};
    return;
  }

  state.selectedAnalyticsUser = state.routeUsers.find((user) => Number(user.id) === Number(state.selectedAnalyticsUserId || 0)) || null;

  try {
    const dashboardResponse = await performAuthenticatedRequest(
      session,
      bridge,
      `/api/users/dashboard/${state.selectedAnalyticsUserId}`,
      { method: 'GET' }
    );
    state.selectedAnalyticsUserDashboard = dashboardResponse?.data || {};
  } catch {
    state.selectedAnalyticsUserDashboard = {};
  }
}

export function bindAdminUserAnalyticsView(state, session, bridge, tools) {
  const { performAuthenticatedRequest, updateInlineStatus } = tools;
  const filterForm = getElementById('adminUserAnalyticsFilterForm');

  filterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(filterForm);
    state.adminUserAnalyticsFilters = {
      q: String(formData.get('q') || '').trim(),
      userType: String(formData.get('userType') || '').trim(),
      status: String(formData.get('status') || '').trim()
    };
    rerenderAdminUserAnalyticsView(state, session, bridge, tools);
    updateInlineStatus('User analytics filters applied.', 'success');
  });

  getElementById('adminUserAnalyticsResetFiltersButton')?.addEventListener('click', () => {
    state.adminUserAnalyticsFilters = getDefaultAdminUserAnalyticsFilters();
    rerenderAdminUserAnalyticsView(state, session, bridge, tools);
    updateInlineStatus('User analytics filters reset.', 'success');
  });

  getElementById('desktopContentArea')?.querySelectorAll('[data-admin-user-analytics-row]').forEach((row) => {
    row.addEventListener('click', async () => {
      const userId = Number(row.getAttribute('data-user-id'));
      if (!userId) {
        return;
      }

      state.selectedAnalyticsUserId = userId;
      state.selectedAnalyticsUser = (state.routeUsers || []).find((user) => Number(user.id) === userId) || null;
      state.selectedAnalyticsUserDashboard = {};
      rerenderAdminUserAnalyticsView(state, session, bridge, tools);

      try {
        const dashboardResponse = await performAuthenticatedRequest(
          session,
          bridge,
          `/api/users/dashboard/${userId}`,
          { method: 'GET' }
        );
        if (Number(state.selectedAnalyticsUserId || 0) !== userId) {
          return;
        }

        state.selectedAnalyticsUserDashboard = dashboardResponse?.data || {};
        rerenderAdminUserAnalyticsView(state, session, bridge, tools);
      } catch {
        // Keep base user selection visible even if analytics details fail to load.
      }
    });
  });
}
