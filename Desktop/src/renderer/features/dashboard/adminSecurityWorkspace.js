import { getElementById } from '../../core/dom.js';

function getSecurityEventTypeOptions(events = []) {
  const baseTypes = [
    'login_failed',
    'login_success',
    'suspicious_activity',
    'unauthorized_access',
    'account_locked',
    'account_unlocked'
  ];

  const eventTypes = new Set(baseTypes);
  events.forEach((event) => {
    const type = String(event?.event_type || '').trim();
    if (type) {
      eventTypes.add(type);
    }
  });

  return Array.from(eventTypes).sort((left, right) => left.localeCompare(right));
}

function formatEventLabel(value) {
  return String(value || 'event')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function getDefaultAdminSecurityFilters() {
  return {
    search: '',
    eventType: '',
    severity: '',
    blockedSearch: '',
    unreadOnly: false
  };
}

function getSecuritySyncMeta(state) {
  return state.routeSecuritySyncMeta || {};
}

function applySecuritySyncSnapshot(state, snapshot = {}) {
  if (Array.isArray(snapshot.security_events)) {
    state.routeSecurityEvents = snapshot.security_events;
  }

  if (Array.isArray(snapshot.notifications)) {
    state.routeSecurityNotifications = snapshot.notifications;
  }

  if (Array.isArray(snapshot.blocked_ips)) {
    state.routeBlockedIps = snapshot.blocked_ips;
  }

  if (snapshot.statistics && typeof snapshot.statistics === 'object') {
    state.routeSecurityStats = {
      ...(state.routeSecurityStats || {}),
      sync_statistics: snapshot.statistics
    };
  }

  const timestamp = Number(snapshot.timestamp || 0);
  if (timestamp) {
    state.routeSecurityLastSync = timestamp;
    state.routeSecuritySyncMeta = {
      ...(state.routeSecuritySyncMeta || {}),
      lastSync: timestamp,
      status: 'connected'
    };
  }
}

function stopAdminSecuritySync(state) {
  if (state.securitySyncTimer) {
    window.clearInterval(state.securitySyncTimer);
    state.securitySyncTimer = null;
  }
}

export function createAdminSecurityView(state, helpers) {
  const {
    createInfoBanner,
    createMetricGrid,
    createPageHeading,
    escapeHtml,
    formatDate,
    formatRelativeCount,
    getSeverityTone
  } = helpers;

  const dashboard = state.routeSecurityDashboard || {};
  const stats = state.routeSecurityStats || {};
  const filters = state.adminSecurityFilters || getDefaultAdminSecurityFilters();
  const topThreats = Array.isArray(dashboard.top_threats) ? dashboard.top_threats : [];
  const alerts = Array.isArray(dashboard.recent_alerts) ? dashboard.recent_alerts : [];
  const events = Array.isArray(state.routeSecurityEvents) ? state.routeSecurityEvents : [];
  const blockedIps = Array.isArray(state.routeBlockedIps) ? state.routeBlockedIps : [];
  const notifications = Array.isArray(state.routeSecurityNotifications) ? state.routeSecurityNotifications : [];
  const eventStats = stats.event_stats || {};
  const blockStats = stats.block_stats || {};
  const notificationStats = stats.notification_stats || {};
  const dashboardSummary = dashboard.summary || {};
  const eventTypes = getSecurityEventTypeOptions(events);
  const securitySettings = state.routeSecuritySettings || {};
  const syncMeta = getSecuritySyncMeta(state);
  const syncStatistics = stats.sync_statistics || {};
  const lastSyncText = syncMeta.lastSync ? formatDate(syncMeta.lastSync * 1000) : 'Not synced yet';

  return `
    ${createPageHeading('fas fa-shield-alt', 'Security Center')}
    ${createInfoBanner(state.routeNotice || 'Monitor security activity, unblock IPs, manage alerts, and review backend events from Desktop.')}
    ${createMetricGrid([
      { icon: 'fas fa-triangle-exclamation', value: dashboardSummary.total_events ?? eventStats.total_events ?? 0, label: 'Tracked Events', tone: 'danger' },
      { icon: 'fas fa-lock', value: dashboardSummary.failed_logins ?? eventStats.login_failed ?? 0, label: 'Failed Logins', tone: 'warning' },
      { icon: 'fas fa-user-shield', value: dashboardSummary.suspicious_activities ?? eventStats.suspicious_activity ?? 0, label: 'Suspicious Activity', tone: 'info' },
      { icon: 'fas fa-ban', value: dashboardSummary.blocked_ips ?? blockStats.active_blocks ?? blockedIps.length, label: 'Blocked IPs', tone: 'primary' },
      { icon: 'fas fa-bell', value: notificationStats.unread ?? alerts.length, label: 'Unread Alerts', tone: 'warning' },
      { icon: 'fas fa-check-circle', value: dashboardSummary.successful_logins ?? eventStats.login_success ?? 0, label: 'Successful Logins', tone: 'success' }
    ])}

    <div class="row">
      <div class="col-xl-5">
        <div class="card desktop-card mb-4">
          <div class="card-header desktop-card-header">
            <i class="fas fa-rotate"></i> Live Sync
          </div>
          <div class="card-body desktop-card-body">
            <div class="desktop-insight-grid">
              <div class="desktop-insight-tile">
                <span>Connection</span>
                <strong>${escapeHtml(syncMeta.status || 'connected')}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Last Sync</span>
                <strong>${escapeHtml(lastSyncText)}</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Polling Interval</span>
                <strong>${escapeHtml(String(securitySettings.sync_poll_seconds || 20))}s</strong>
              </div>
              <div class="desktop-insight-tile">
                <span>Unread Alerts</span>
                <strong>${escapeHtml(String(syncStatistics.unread_notifications ?? notificationStats.unread ?? 0))}</strong>
              </div>
            </div>
            <p class="desktop-table-subtext mt-3 mb-0">This screen polls the backend security sync endpoint while you keep the Security Center open.</p>
          </div>
        </div>
      </div>
      <div class="col-xl-7">
        <div class="card desktop-card mb-4">
          <div class="card-header desktop-card-header">
            <i class="fas fa-cog"></i> Security Settings
          </div>
          <div class="card-body desktop-card-body">
            <form id="adminSecuritySettingsForm" class="desktop-form-grid">
              <div class="form-row">
                <div class="field">
                  <label for="adminSecurityThreshold">Brute Force Threshold</label>
                  <input id="adminSecurityThreshold" name="brute_force_threshold" type="number" min="1" max="20" value="${escapeHtml(securitySettings.brute_force_threshold ?? 5)}" />
                </div>
                <div class="field">
                  <label for="adminSecurityDuration">Block Duration (minutes)</label>
                  <input id="adminSecurityDuration" name="block_duration_minutes" type="number" min="5" max="10080" value="${escapeHtml(securitySettings.block_duration_minutes ?? 30)}" />
                </div>
              </div>
              <div class="field">
                <label for="adminSecurityPollSeconds">Sync Polling Interval (seconds)</label>
                <input id="adminSecurityPollSeconds" name="sync_poll_seconds" type="number" min="10" max="300" value="${escapeHtml(securitySettings.sync_poll_seconds ?? 20)}" />
              </div>
              <div class="desktop-settings-session-grid">
                <label class="d-flex align-items-center gap-2 mb-0">
                  <input name="auto_block_enabled" type="checkbox" ${securitySettings.auto_block_enabled ? 'checked' : ''} />
                  Auto-block repeated offenders
                </label>
                <label class="d-flex align-items-center gap-2 mb-0">
                  <input name="notify_on_failed_login" type="checkbox" ${securitySettings.notify_on_failed_login ? 'checked' : ''} />
                  Alert on failed logins
                </label>
                <label class="d-flex align-items-center gap-2 mb-0">
                  <input name="notify_on_blocked_ip" type="checkbox" ${securitySettings.notify_on_blocked_ip ? 'checked' : ''} />
                  Alert when IPs are blocked
                </label>
                <label class="d-flex align-items-center gap-2 mb-0">
                  <input name="notify_on_suspicious_activity" type="checkbox" ${securitySettings.notify_on_suspicious_activity ? 'checked' : ''} />
                  Alert on suspicious activity
                </label>
              </div>
              <div class="desktop-form-actions">
                <button id="adminSecuritySettingsSave" type="submit" class="action-button">Save Settings</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-sliders"></i> Security Filters & Actions
      </div>
      <div class="card-body desktop-card-body">
        <form id="adminSecurityFiltersForm" class="desktop-form-grid">
          <div class="field">
            <label for="adminSecuritySearch">Search Events</label>
            <input id="adminSecuritySearch" name="search" type="search" class="form-control" value="${escapeHtml(filters.search || '')}" placeholder="IP, email, or details" />
          </div>
          <div class="field">
            <label for="adminSecurityEventType">Event Type</label>
            <select id="adminSecurityEventType" name="eventType" class="form-control">
              <option value="">All event types</option>
              ${eventTypes.map((type) => `
                <option value="${escapeHtml(type)}" ${filters.eventType === type ? 'selected' : ''}>${escapeHtml(formatEventLabel(type))}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="adminSecuritySeverity">Severity</label>
            <select id="adminSecuritySeverity" name="severity" class="form-control">
              <option value="">All severities</option>
              ${['critical', 'high', 'medium', 'low', 'info'].map((severity) => `
                <option value="${severity}" ${filters.severity === severity ? 'selected' : ''}>${escapeHtml(formatEventLabel(severity))}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="adminSecurityBlockedSearch">Blocked IP Search</label>
            <input id="adminSecurityBlockedSearch" name="blockedSearch" type="search" class="form-control" value="${escapeHtml(filters.blockedSearch || '')}" placeholder="Search blocked IPs" />
          </div>
          <div class="field d-flex align-items-end">
            <label class="d-flex align-items-center gap-2 mb-0" for="adminSecurityUnreadOnly">
              <input id="adminSecurityUnreadOnly" name="unreadOnly" type="checkbox" ${filters.unreadOnly ? 'checked' : ''} />
              Show unread notifications only
            </label>
          </div>
          <div class="desktop-form-actions desktop-form-actions--filters">
            <button id="adminSecurityApplyFilters" type="submit" class="action-button">Apply Filters</button>
            <button id="adminSecurityResetFilters" type="button" class="ghost-button">Reset</button>
            <button id="adminSecurityRefreshButton" type="button" class="ghost-button">Refresh</button>
            <button id="adminSecurityExportReport" type="button" class="ghost-button">Export Report</button>
            <button id="adminSecurityMarkAllRead" type="button" class="ghost-button" ${notifications.length ? '' : 'disabled'}>Mark All Read</button>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-8">
        <div class="card desktop-card mb-4">
          <div class="card-header desktop-card-header">
            <i class="fas fa-clock-rotate-left"></i> Security Events
          </div>
          <div class="card-body desktop-card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Event</th>
                    <th>Severity</th>
                    <th>IP Address</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  ${events.length ? events.map((event) => `
                    <tr>
                      <td>
                        <strong>${escapeHtml(formatEventLabel(event.event_type || 'event'))}</strong>
                        <div class="desktop-table-subtext">${escapeHtml(event.details || 'No additional details provided.')}</div>
                      </td>
                      <td><span class="badge bg-${getSeverityTone(event.severity)}">${escapeHtml(formatEventLabel(event.severity || 'info'))}</span></td>
                      <td>${escapeHtml(event.ip_address || '-')}</td>
                      <td>${formatDate(event.created_at)}</td>
                    </tr>
                  `).join('') : `
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No security events matched the current filters.</td>
                    </tr>
                  `}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card desktop-card">
          <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-ban"></i> Blocked IP Addresses</span>
            <span class="desktop-table-subtext">${formatRelativeCount(blockedIps.length)} shown</span>
          </div>
          <div class="card-body desktop-card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>IP Address</th>
                    <th>Reason</th>
                    <th>Blocked Until</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  ${blockedIps.length ? blockedIps.map((blockedIp) => `
                    <tr>
                      <td><strong>${escapeHtml(blockedIp.ip_address || '-')}</strong></td>
                      <td>${escapeHtml(blockedIp.reason || blockedIp.notes || 'Manual or automated security block')}</td>
                      <td>${formatDate(blockedIp.blocked_until || blockedIp.expires_at || blockedIp.created_at)}</td>
                      <td class="desktop-table-actions">
                        <button
                          type="button"
                          class="ghost-button desktop-danger-button"
                          data-security-unblock-id="${Number(blockedIp.id || 0)}"
                          data-security-ip="${escapeHtml(blockedIp.ip_address || '')}"
                        >
                          Unblock
                        </button>
                      </td>
                    </tr>
                  `).join('') : `
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No active blocked IP entries were returned.</td>
                    </tr>
                  `}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card desktop-card mb-4">
          <div class="card-header desktop-card-header">
            <i class="fas fa-user-lock"></i> Manual Block
          </div>
          <div class="card-body desktop-card-body">
            <form id="adminSecurityBlockForm" class="desktop-form-grid">
              <div class="field">
                <label for="adminSecurityBlockIp">IP Address</label>
                <input id="adminSecurityBlockIp" name="ip_address" type="text" class="form-control" placeholder="e.g. 203.0.113.5" />
              </div>
              <div class="field">
                <label for="adminSecurityBlockReason">Reason</label>
                <input id="adminSecurityBlockReason" name="reason" type="text" class="form-control" placeholder="Manual block by admin" />
              </div>
              <div class="field">
                <label for="adminSecurityBlockDuration">Duration</label>
                <select id="adminSecurityBlockDuration" name="duration" class="form-control">
                  <option value="+1 hour">1 hour</option>
                  <option value="+6 hours">6 hours</option>
                  <option value="+1 day">1 day</option>
                  <option value="+7 days">7 days</option>
                  <option value="+30 days">30 days</option>
                </select>
              </div>
              <div class="desktop-form-actions">
                <button id="adminSecurityBlockSubmit" type="submit" class="action-button">Block IP</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card desktop-card mb-4">
          <div class="card-header desktop-card-header">
            <i class="fas fa-bell"></i> Notifications
          </div>
          <div class="card-body desktop-card-body">
            ${notifications.length ? notifications.map((notification) => `
              <div class="desktop-insight-row">
                <div>
                  <strong>${escapeHtml(notification.title || notification.type || 'Security alert')}</strong>
                  <div class="desktop-table-subtext">${escapeHtml(notification.message || notification.description || 'Review this security alert.')}</div>
                </div>
                <div class="desktop-table-actions">
                  <span class="badge bg-${getSeverityTone(notification.priority || 'warning')}">${escapeHtml(formatEventLabel(notification.priority || 'pending'))}</span>
                  ${notification.is_read ? '' : `
                    <button
                      type="button"
                      class="ghost-button"
                      data-security-mark-read="${Number(notification.id || 0)}"
                    >
                      Mark Read
                    </button>
                  `}
                </div>
              </div>
            `).join('') : `
              <div class="chart-placeholder desktop-placeholder-card">No security notifications matched the current view.</div>
            `}
          </div>
        </div>

        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-radar"></i> Top Threat Sources
          </div>
          <div class="card-body desktop-card-body">
            ${topThreats.length ? topThreats.map((threat) => `
              <div class="desktop-insight-row">
                <div>
                  <strong>${escapeHtml(threat.ip_address || 'Unknown IP')}</strong>
                  <div class="desktop-table-subtext">${escapeHtml(threat.details || threat.event_type || 'Suspicious activity detected')}</div>
                </div>
                <span class="badge bg-${getSeverityTone(threat.severity || 'medium')}">${formatRelativeCount(threat.event_count || threat.total || 1)} hits</span>
              </div>
            `).join('') : `
              <div class="chart-placeholder desktop-placeholder-card">No top threat data is available yet.</div>
            `}
          </div>
        </div>
      </div>
    </div>
  `;
}

export async function loadAdminSecurityRouteData(state, session, bridge, performAuthenticatedRequest) {
  const filters = state.adminSecurityFilters || getDefaultAdminSecurityFilters();
  const eventQuery = new URLSearchParams({ limit: '25' });
  const blockedQuery = new URLSearchParams({ limit: '25' });
  const notificationQuery = new URLSearchParams({ limit: '8' });

  if (filters.search) {
    eventQuery.set('search', filters.search);
  }

  if (filters.eventType) {
    eventQuery.set('event_type', filters.eventType);
  }

  if (filters.severity) {
    eventQuery.set('severity', filters.severity);
  }

  if (filters.blockedSearch) {
    blockedQuery.set('search', filters.blockedSearch);
  }

  if (filters.unreadOnly) {
    notificationQuery.set('unread', 'true');
  }

  const [dashboardResponse, eventsResponse, blockedResponse, statisticsResponse, notificationsResponse, settingsResponse, syncResponse] = await Promise.all([
    performAuthenticatedRequest(session, bridge, '/api/security/dashboard', { method: 'GET' }),
    performAuthenticatedRequest(session, bridge, `/api/security/events?${eventQuery.toString()}`, { method: 'GET' }).catch(() => ({ data: { events: [] } })),
    performAuthenticatedRequest(session, bridge, `/api/security/blocked-ips?${blockedQuery.toString()}`, { method: 'GET' }).catch(() => ({ data: { blocked_ips: [] } })),
    performAuthenticatedRequest(session, bridge, '/api/security/statistics', { method: 'GET' }).catch(() => ({ data: {} })),
    performAuthenticatedRequest(session, bridge, `/api/security/notifications?${notificationQuery.toString()}`, { method: 'GET' }).catch(() => ({ data: { notifications: [] } })),
    performAuthenticatedRequest(session, bridge, '/api/security/settings', { method: 'GET' }).catch(() => ({ data: {} })),
    performAuthenticatedRequest(session, bridge, '/api/security/sync/initialize', { method: 'GET' }).catch(() => ({ data: {}, timestamp: 0 }))
  ]);

  state.routeSecurityDashboard = dashboardResponse?.data || {};
  state.routeSecurityEvents = eventsResponse?.data?.events || [];
  state.routeBlockedIps = blockedResponse?.data?.blocked_ips || [];
  state.routeSecurityStats = statisticsResponse?.data || {};
  state.routeSecurityNotifications = notificationsResponse?.data?.notifications || [];
  state.routeSecuritySettings = settingsResponse?.data || {};
  state.routeSecuritySyncMeta = {
    status: 'connected',
    lastSync: Number(syncResponse?.timestamp || syncResponse?.data?.timestamp || 0) || null
  };
  state.routeSecurityLastSync = Number(syncResponse?.timestamp || syncResponse?.data?.timestamp || 0) || state.routeSecurityLastSync || null;
  state.routeNotice = 'Security activity is live from the backend APIs. Use the actions below when something needs intervention.';
}

export function bindAdminSecurityView(state, session, bridge, tools) {
  const {
    performAuthenticatedRequest,
    renderRoute,
    setStatus,
    updateInlineStatus
  } = tools;

  const filterForm = getElementById('adminSecurityFiltersForm');
  const resetButton = getElementById('adminSecurityResetFilters');
  const refreshButton = getElementById('adminSecurityRefreshButton');
  const exportButton = getElementById('adminSecurityExportReport');
  const markAllReadButton = getElementById('adminSecurityMarkAllRead');
  const blockForm = getElementById('adminSecurityBlockForm');
  const settingsForm = getElementById('adminSecuritySettingsForm');

  filterForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(filterForm);
    state.adminSecurityFilters = {
      search: String(formData.get('search') || '').trim(),
      eventType: String(formData.get('eventType') || '').trim(),
      severity: String(formData.get('severity') || '').trim(),
      blockedSearch: String(formData.get('blockedSearch') || '').trim(),
      unreadOnly: formData.get('unreadOnly') === 'on'
    };

    updateInlineStatus('Security filters applied.', 'success');
    await renderRoute(state, session, bridge);
  });

  resetButton?.addEventListener('click', async () => {
    state.adminSecurityFilters = getDefaultAdminSecurityFilters();
    updateInlineStatus('Security filters reset.', 'success');
    await renderRoute(state, session, bridge);
  });

  refreshButton?.addEventListener('click', async () => {
    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/security/sync/refresh', { method: 'POST' });
      applySecuritySyncSnapshot(state, response?.data || {});
      await renderRoute(state, session, bridge);
    } catch {
      await renderRoute(state, session, bridge);
    }
    updateInlineStatus('Security data refreshed successfully.', 'success');
    setStatus('Security data refreshed successfully.', 'success');
  });

  settingsForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submitButton = getElementById('adminSecuritySettingsSave');
    const formData = new FormData(settingsForm);
    const payload = {
      brute_force_threshold: Number(formData.get('brute_force_threshold') || 5),
      block_duration_minutes: Number(formData.get('block_duration_minutes') || 30),
      sync_poll_seconds: Number(formData.get('sync_poll_seconds') || 20),
      auto_block_enabled: formData.get('auto_block_enabled') === 'on',
      notify_on_failed_login: formData.get('notify_on_failed_login') === 'on',
      notify_on_blocked_ip: formData.get('notify_on_blocked_ip') === 'on',
      notify_on_suspicious_activity: formData.get('notify_on_suspicious_activity') === 'on',
    };

    try {
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
      }

      const response = await performAuthenticatedRequest(session, bridge, '/api/security/settings', {
        method: 'PUT',
        body: payload
      });

      state.routeSecuritySettings = response?.data || payload;
      updateInlineStatus(response?.message || 'Security settings updated successfully.', 'success');
      setStatus(response?.message || 'Security settings updated successfully.', 'success');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to update security settings.', 'error');
      setStatus(error.message || 'Failed to update security settings.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Save Settings';
      }
    }
  });

  exportButton?.addEventListener('click', async () => {
    try {
      exportButton.disabled = true;
      const response = await performAuthenticatedRequest(session, bridge, '/api/security/report?period=daily', { method: 'GET' });
      const report = response?.data || {};
      const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `security-report-${new Date().toISOString().slice(0, 10)}.json`;
      link.click();
      window.URL.revokeObjectURL(url);
      updateInlineStatus('Security report exported as JSON.', 'success');
      setStatus('Security report exported successfully.', 'success');
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to export the security report.', 'error');
      setStatus(error.message || 'Failed to export the security report.', 'error');
    } finally {
      exportButton.disabled = false;
    }
  });

  markAllReadButton?.addEventListener('click', async () => {
    try {
      markAllReadButton.disabled = true;
      const response = await performAuthenticatedRequest(session, bridge, '/api/security/notifications/mark-all-read', { method: 'POST' });
      updateInlineStatus(response?.message || 'All notifications marked as read.', 'success');
      setStatus(response?.message || 'All notifications marked as read.', 'success');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to mark all notifications as read.', 'error');
      setStatus(error.message || 'Failed to mark all notifications as read.', 'error');
    } finally {
      markAllReadButton.disabled = false;
    }
  });

  document.querySelectorAll('[data-security-mark-read]').forEach((button) => {
    button.addEventListener('click', async () => {
      const notificationId = Number(button.getAttribute('data-security-mark-read') || 0);
      if (!notificationId) {
        return;
      }

      try {
        button.disabled = true;
        const response = await performAuthenticatedRequest(session, bridge, `/api/security/notifications/${notificationId}/mark-read`, { method: 'POST' });
        updateInlineStatus(response?.message || 'Notification marked as read.', 'success');
        setStatus(response?.message || 'Notification marked as read.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to mark this notification as read.', 'error');
        setStatus(error.message || 'Failed to mark this notification as read.', 'error');
      } finally {
        button.disabled = false;
      }
    });
  });

  document.querySelectorAll('[data-security-unblock-id]').forEach((button) => {
    button.addEventListener('click', async () => {
      const blockId = Number(button.getAttribute('data-security-unblock-id') || 0);
      const ipAddress = String(button.getAttribute('data-security-ip') || '').trim();

      if (!blockId) {
        return;
      }

      const confirmed = typeof window.confirm === 'function'
        ? window.confirm(`Unblock ${ipAddress || 'this IP address'}?`)
        : true;
      if (!confirmed) {
        return;
      }

      try {
        button.disabled = true;
        const response = await performAuthenticatedRequest(session, bridge, `/api/security/unblock-ip/${blockId}`, { method: 'POST' });
        updateInlineStatus(response?.message || 'IP unblocked successfully.', 'success');
        setStatus(response?.message || 'IP unblocked successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to unblock this IP address.', 'error');
        setStatus(error.message || 'Failed to unblock this IP address.', 'error');
      } finally {
        button.disabled = false;
      }
    });
  });

  blockForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(blockForm);
    const payload = {
      ip_address: String(formData.get('ip_address') || '').trim(),
      reason: String(formData.get('reason') || '').trim() || 'Manual block by admin',
      duration: String(formData.get('duration') || '+1 hour'),
      is_temporary: true
    };

    if (!payload.ip_address) {
      updateInlineStatus('Enter an IP address before blocking it.', 'error');
      setStatus('Enter an IP address before blocking it.', 'error');
      return;
    }

    const submitButton = getElementById('adminSecurityBlockSubmit');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Blocking...';
    }

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/security/block-ip', {
        method: 'POST',
        body: payload
      });
      updateInlineStatus(response?.message || 'IP blocked successfully.', 'success');
      setStatus(response?.message || 'IP blocked successfully.', 'success');
      blockForm.reset();
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to block this IP address.', 'error');
      setStatus(error.message || 'Failed to block this IP address.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Block IP';
      }
    }
  });

  stopAdminSecuritySync(state);
  const pollSeconds = Math.max(10, Number(state.routeSecuritySettings?.sync_poll_seconds || 20));
  state.securitySyncTimer = window.setInterval(async () => {
    if (state.currentRoute !== 'security') {
      stopAdminSecuritySync(state);
      return;
    }

    try {
      const lastSync = Number(state.routeSecurityLastSync || 0);
      const response = await performAuthenticatedRequest(session, bridge, `/api/security/sync?last_sync=${encodeURIComponent(String(lastSync))}`, { method: 'GET' });
      state.routeSecuritySyncMeta = {
        ...(state.routeSecuritySyncMeta || {}),
        status: 'connected',
        lastSync: Number(response?.timestamp || state.routeSecurityLastSync || 0) || null
      };

      if (response?.status === 'updated') {
        applySecuritySyncSnapshot(state, response?.data?.snapshot || response?.data || {});
        await renderRoute(state, session, bridge);
      }
    } catch {
      state.routeSecuritySyncMeta = {
        ...(state.routeSecuritySyncMeta || {}),
        status: 'reconnecting'
      };
    }
  }, pollSeconds * 1000);
}

export { stopAdminSecuritySync };
