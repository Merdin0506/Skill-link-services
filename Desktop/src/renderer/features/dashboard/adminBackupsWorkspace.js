import { getElementById } from '../../core/dom.js';

function formatBytes(size = 0) {
  const numeric = Number(size || 0);
  if (!Number.isFinite(numeric) || numeric <= 0) {
    return '0 B';
  }

  if (numeric < 1024) {
    return `${numeric} B`;
  }

  if (numeric < 1024 * 1024) {
    return `${(numeric / 1024).toFixed(2)} KB`;
  }

  if (numeric < 1024 * 1024 * 1024) {
    return `${(numeric / (1024 * 1024)).toFixed(2)} MB`;
  }

  return `${(numeric / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

export function createAdminBackupsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, createPageHeading, escapeHtml, formatDate } = helpers;
  const backups = Array.isArray(state.routeBackups) ? state.routeBackups : [];
  const summary = state.routeBackupSummary || {};
  const latest = summary.latest_backup || null;

  return `
    ${createPageHeading('fas fa-database', 'Backups')}
    ${createInfoBanner(state.routeNotice || 'Create and restore database backups from Desktop. Restores automatically create a pre-restore backup first.')}
    ${createMetricGrid([
      { icon: 'fas fa-box-archive', value: summary.count ?? backups.length, label: 'Total Backups', tone: 'primary' },
      { icon: 'fas fa-hard-drive', value: formatBytes(summary.total_size ?? 0), label: 'Stored Size', tone: 'info' },
      { icon: 'fas fa-clock-rotate-left', value: latest?.filename || 'None yet', label: 'Latest Backup', tone: 'success' }
    ])}

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-shield-halved"></i> Backup Controls</span>
        <button type="button" class="action-button" id="adminBackupCreateButton">Create Backup Now</button>
      </div>
      <div class="card-body desktop-card-body">
        <div class="desktop-inline-banner">
          Restoring a backup replaces the current database contents. The system creates a pre-restore backup automatically before the restore begins.
        </div>
      </div>
    </div>

    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-list"></i> Available Backups
      </div>
      <div class="card-body desktop-card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Filename</th>
                <th>Created</th>
                <th>Size</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${backups.length ? backups.map((backup) => `
                <tr>
                  <td><strong>${escapeHtml(backup.filename || 'backup.sql.gz')}</strong></td>
                  <td>${formatDate(backup.modified_at ? new Date(Number(backup.modified_at) * 1000).toISOString() : backup.path)}</td>
                  <td>${formatBytes(backup.size || 0)}</td>
                  <td class="desktop-table-actions">
                    <button
                      type="button"
                      class="ghost-button desktop-danger-button"
                      data-backup-restore="true"
                      data-backup-file="${escapeHtml(backup.filename || '')}"
                    >
                      Restore
                    </button>
                  </td>
                </tr>
              `).join('') : `
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">No backup files are available yet.</td>
                </tr>
              `}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

export async function loadAdminBackupsRouteData(state, session, bridge, performAuthenticatedRequest) {
  const response = await performAuthenticatedRequest(session, bridge, '/api/backups', { method: 'GET' });
  const payload = response?.data || {};
  state.routeBackups = payload.backups || [];
  state.routeBackupSummary = payload.summary || {};
  state.routeNotice = state.routeBackups.length
    ? 'Desktop is showing the current backup inventory from the backend.'
    : 'No backups were found yet. Create one to start protecting the database state.';
}

export function bindAdminBackupsView(state, session, bridge, tools) {
  const { performAuthenticatedRequest, renderRoute, setStatus, updateInlineStatus } = tools;
  const createButton = getElementById('adminBackupCreateButton');

  createButton?.addEventListener('click', async () => {
    createButton.disabled = true;
    createButton.textContent = 'Creating...';

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/backups', { method: 'POST' });
      updateInlineStatus(response?.message || 'Backup created successfully.', 'success');
      setStatus(response?.message || 'Backup created successfully.', 'success');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to create backup.', 'error');
      setStatus(error.message || 'Failed to create backup.', 'error');
    } finally {
      createButton.disabled = false;
      createButton.textContent = 'Create Backup Now';
    }
  });

  document.querySelectorAll('[data-backup-restore]').forEach((button) => {
    button.addEventListener('click', async () => {
      const backupFile = String(button.getAttribute('data-backup-file') || '').trim();
      if (!backupFile) {
        return;
      }

      const confirmed = typeof window.confirm === 'function'
        ? window.confirm(`Restore backup "${backupFile}"? This will replace the current database.`)
        : true;
      if (!confirmed) {
        return;
      }

      button.disabled = true;
      const previousLabel = button.textContent;
      button.textContent = 'Restoring...';

      try {
        const response = await performAuthenticatedRequest(session, bridge, '/api/backups/restore', {
          method: 'POST',
          body: { backup_file: backupFile }
        });
        updateInlineStatus(response?.message || 'Backup restored successfully.', 'success');
        setStatus(response?.message || 'Backup restored successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to restore backup.', 'error');
        setStatus(error.message || 'Failed to restore backup.', 'error');
      } finally {
        button.disabled = false;
        button.textContent = previousLabel;
      }
    });
  });
}
