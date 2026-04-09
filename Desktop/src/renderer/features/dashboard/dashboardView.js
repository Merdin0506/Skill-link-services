import { getElementById, updateText } from '../../core/dom.js';
import { clearSession, getSession } from '../../core/storage.js';
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

function createStatCard({ icon, value, label, tone = 'primary', subtitle = '' }) {
  return `
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="stat-card ${tone}">
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

function renderTableRows(bookings, role) {
  const rows = bookings || [];
  if (!rows.length) {
    const colspan = role === 'finance' ? 7 : role === 'admin' || role === 'super_admin' ? 6 : 6;
    return `
      <tr>
        <td colspan="${colspan}" class="text-center text-muted py-4">
          <i class="fas fa-inbox fa-2x mb-2"></i>
          <p class="mb-0">No records yet.</p>
        </td>
      </tr>
    `;
  }

  return rows
    .map((booking) => {
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
            <td><button type="button" class="ghost-button table-action">View</button></td>
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
            <td><button type="button" class="ghost-button table-action">View</button></td>
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
            <td><button type="button" class="ghost-button table-action">View</button></td>
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
    })
    .join('');
}

function getSidebarLinks(role) {
  if (role === 'admin' || role === 'super_admin') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', href: '#dashboard' },
      { icon: 'fas fa-users', label: 'Users', href: '#users' },
      { icon: 'fas fa-calendar-check', label: 'Bookings', href: '#bookings' },
      { icon: 'fas fa-credit-card', label: 'Payments', href: '#payments' },
      { icon: 'fas fa-file-invoice', label: 'Service Records', href: '#records' }
    ];
  }

  if (role === 'worker') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', href: '#dashboard' },
      { icon: 'fas fa-briefcase', label: 'Available Jobs', href: '#available-jobs' },
      { icon: 'fas fa-tasks', label: 'My Jobs', href: '#my-jobs' },
      { icon: 'fas fa-wallet', label: 'Earnings', href: '#earnings' }
    ];
  }

  if (role === 'finance') {
    return [
      { icon: 'fas fa-chart-line', label: 'Dashboard', href: '#dashboard' },
      { icon: 'fas fa-money-bill-wave', label: 'Payments', href: '#payments' },
      { icon: 'fas fa-hand-holding-usd', label: 'Worker Payouts', href: '#payouts' },
      { icon: 'fas fa-chart-bar', label: 'Financial Reports', href: '#reports' }
    ];
  }

  return [
    { icon: 'fas fa-chart-line', label: 'Dashboard', href: '#dashboard' },
    { icon: 'fas fa-calendar-check', label: 'My Bookings', href: '#bookings' },
    { icon: 'fas fa-list', label: 'Services', href: '#services' },
    { icon: 'fas fa-credit-card', label: 'Payments', href: '#payments' }
  ];
}

function renderDashboardShell({ profile, stats, bookings, role }) {
  const dashboardSection = getElementById('dashboardSection');
  if (!dashboardSection) {
    return;
  }

  const template = getRoleTemplate(role);
  const userName = [profile.first_name, profile.last_name].filter(Boolean).join(' ') || 'User';
  const displayRole = String(role || 'customer').replace(/_/g, ' ');
  const statCards = buildRoleStatCards(role, stats);
  const sidebarLinks = getSidebarLinks(role);

  dashboardSection.innerHTML = `
    <div class="shell-grid dashboard-shell" id="mainContent">
      <aside class="panel sidebar">
        <div class="brand">
          <i class="fas fa-link"></i>
          <h5>SkillLink</h5>
        </div>
        <nav class="sidebar-nav">
          ${sidebarLinks.map((item, index) => `
            <a href="${item.href}" class="${index === 0 ? 'active' : ''}">
              <i class="${item.icon}"></i>
              <span>${item.label}</span>
            </a>
          `).join('')}
          <a href="#profile">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
          </a>
          <a href="#settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
          </a>
          <a href="#logout" id="sidebarLogoutLink" style="margin-top: 20px; border-top: 1px solid rgba(31,41,55,0.1); padding-top: 20px;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </nav>
      </aside>

      <section class="main-content dashboard-main">
        <div class="topbar">
          <div class="welcome">
            <button class="btn btn-sm btn-light" id="toggleSidebar" type="button">
              <i class="fas fa-bars"></i>
            </button>
            <div>
              <h6>Welcome back!</h6>
              <p class="mb-0 text-muted">${userName}</p>
            </div>
          </div>
          <div class="user-profile">
            <span class="role-badge">${displayRole}</span>
            <div>
              <p class="mb-0">${profile.email || '-'}</p>
              <small class="text-muted">Last login: Today</small>
            </div>
            <button id="logoutButton" class="ghost-button" type="button">Logout</button>
          </div>
        </div>

        <div class="container-fluid page-content">
        <div class="dashboard-alert alert-custom info">
          <i class="fas fa-info-circle"></i>
          <strong>${template.title}</strong>
          <p>${template.description}</p>
        </div>

        <div class="row mb-4">
          ${statCards}
        </div>

        <div class="row mb-4">
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header">
                <i class="fas fa-chart-pie"></i> ${template.chartLeftTitle}
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <div class="chart-placeholder">${template.chartLeftFallback}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header">
                <i class="fas fa-chart-line"></i> ${template.chartRightTitle}
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <div class="chart-placeholder">${template.chartRightFallback}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-header">
                <i class="fas fa-list"></i> ${template.tableTitle}
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        ${template.tableHeaders.map((header) => `<th>${header}</th>`).join('')}
                      </tr>
                    </thead>
                    <tbody>
                      ${renderTableRows(bookings, role)}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="roleContent" class="dashboard-section">
          <article class="role-panel">
            <h3 class="section-title">Quick Actions</h3>
            <div class="quick-actions">
              ${template.actions.map((action) => `<button type="button" class="action-button">${action}</button>`).join('')}
            </div>
          </article>
        </div>
        </div>
      </section>
    </div>
  `;
}

function setDashboardVisible(isVisible) {
  const authSection = getElementById('authSection');
  const registerSection = getElementById('registerSection');
  const dashboardSection = getElementById('dashboardSection');

  authSection?.classList.toggle('hidden', isVisible);
  registerSection?.classList.add('hidden');
  dashboardSection?.classList.toggle('hidden', !isVisible);
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

  const requestProfile = bridge
    ? bridge.getProfile(session.token)
    : requestJson('/api/auth/profile', {
        method: 'GET',
        headers: { Authorization: `Bearer ${session.token}` }
      });

  const requestStats = bridge
    ? bridge.getDashboardStats(session.token)
    : requestJson('/api/dashboard/stats', {
        method: 'GET',
        headers: { Authorization: `Bearer ${session.token}` }
      });

  const requestBookings = bridge
    ? bridge.getDashboardBookings(session.token, 8)
    : requestJson('/api/dashboard/bookings?limit=8', {
        method: 'GET',
        headers: { Authorization: `Bearer ${session.token}` }
      });

  try {
    const [profileResponse, statsResponse, bookingsResponse] = await Promise.all([
      requestProfile.catch(() => ({ data: session.user })),
      requestStats.catch(() => ({ stats: {} })),
      requestBookings.catch(() => ({ bookings: [] }))
    ]);

    const profile = profileResponse?.data || session.user || {};
    const currentRole = profile.user_type || session.user?.user_type || 'customer';

    updateText(getElementById('userName'), [profile.first_name, profile.last_name].filter(Boolean).join(' ') || 'User');
    updateText(getElementById('userRole'), currentRole);
    updateText(getElementById('sidebarRole'), currentRole);
    updateText(getElementById('userEmail'), profile.email || session.user?.email || '-');

    renderDashboardShell({
      profile,
      stats: statsResponse?.stats || {},
      bookings: bookingsResponse?.bookings || [],
      role: currentRole
    });

    const performLogout = async (event) => {
      if (event) {
        event.preventDefault();
      }

      try {
        if (bridge) {
          await bridge.logout(session.token);
        } else {
          await requestJson('/api/auth/logout', {
            method: 'POST',
            headers: { Authorization: `Bearer ${session.token}` }
          });
        }
      } catch {
        // Ignore backend logout errors for local session cleanup.
      }

      clearSession();
      setDashboardVisible(false);
      getElementById('loginForm')?.reset();
      getElementById('registerForm')?.reset();
      setStatus('Logged out successfully.', null);
    };

    const logoutButton = getElementById('logoutButton');
    if (logoutButton) {
      logoutButton.onclick = performLogout;
    }

    const sidebarLogoutLink = getElementById('sidebarLogoutLink');
    if (sidebarLogoutLink) {
      sidebarLogoutLink.onclick = performLogout;
    }

    setStatus('Dashboard loaded successfully.', 'success');
  } catch (error) {
    console.error('Dashboard render error:', error);
    setStatus(error.message || 'Failed to load dashboard data.', 'error');
  }
}
