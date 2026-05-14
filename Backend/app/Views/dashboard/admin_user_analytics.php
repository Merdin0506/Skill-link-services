<?= view('layouts/page_header', ['pageTitle' => 'User Analytics']) ?>

<?php
$selectedMetrics = array_filter($selectedUserDashboard ?? [], static function ($value): bool {
    return is_scalar($value) && !is_array($value);
});

$currentFilters = $filters ?? ['q' => '', 'userType' => '', 'status' => ''];
$selectedUserIdValue = (int) ($selectedUserId ?? 0);
?>

<div class="page-content">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0"><i class="fas fa-chart-line"></i> User Analytics</h3>
            <p class="text-muted mb-0">Review account distribution and inspect role-specific dashboard metrics for any user.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4 col-xl-2">
            <div class="stat-card">
                <div class="text-muted small">Total Users</div>
                <h2 class="mb-0"><?= esc((string) ($userStats['total_users'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="stat-card info">
                <div class="text-muted small">Admin Staff</div>
                <h2 class="mb-0"><?= esc((string) ($userStats['total_admin_staff'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="stat-card success">
                <div class="text-muted small">Workers</div>
                <h2 class="mb-0"><?= esc((string) ($userStats['total_workers'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="stat-card warning">
                <div class="text-muted small">Customers</div>
                <h2 class="mb-0"><?= esc((string) ($userStats['total_customers'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="stat-card success">
                <div class="text-muted small">Active Users</div>
                <h2 class="mb-0"><?= esc((string) ($userStats['active_users'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="stat-card danger">
                <div class="text-muted small">Inactive or Suspended</div>
                <h2 class="mb-0"><?= esc((string) ((int) ($userStats['inactive_users'] ?? 0) + (int) ($userStats['suspended_users'] ?? 0))) ?></h2>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> User Analytics Filters
        </div>
        <div class="card-body">
            <form method="get" action="<?= base_url('admin/user-analytics') ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="userAnalyticsQuery" class="form-label">Search</label>
                        <input id="userAnalyticsQuery" name="q" type="text" class="form-control" value="<?= esc($currentFilters['q'] ?? '') ?>" placeholder="Name, email, or phone">
                    </div>
                    <div class="col-md-3">
                        <label for="userAnalyticsRole" class="form-label">Role</label>
                        <select id="userAnalyticsRole" name="userType" class="form-select">
                            <option value="">All roles</option>
                            <?php foreach (['super_admin', 'admin', 'finance', 'worker', 'customer'] as $roleOption): ?>
                                <option value="<?= esc($roleOption) ?>" <?= ($currentFilters['userType'] ?? '') === $roleOption ? 'selected' : '' ?>>
                                    <?= esc(str_replace('_', ' ', $roleOption)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="userAnalyticsStatus" class="form-label">Status</label>
                        <select id="userAnalyticsStatus" name="status" class="form-select">
                            <option value="">All statuses</option>
                            <?php foreach (['active', 'inactive', 'suspended'] as $statusOption): ?>
                                <option value="<?= esc($statusOption) ?>" <?= ($currentFilters['status'] ?? '') === $statusOption ? 'selected' : '' ?>>
                                    <?= esc($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                        <a href="<?= base_url('admin/user-analytics') ?>" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users-viewfinder"></i> Analytics User List</span>
                    <span class="badge bg-secondary"><?= esc((string) count($users ?? [])) ?> visible</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $row): ?>
                                        <?php
                                        $query = array_filter([
                                            'q' => $currentFilters['q'] ?? '',
                                            'userType' => $currentFilters['userType'] ?? '',
                                            'status' => $currentFilters['status'] ?? '',
                                            'user_id' => $row['id'] ?? null,
                                        ], static fn ($value) => $value !== null && $value !== '');
                                        $isSelected = (int) ($row['id'] ?? 0) === $selectedUserIdValue;
                                        ?>
                                        <tr class="<?= $isSelected ? 'table-active' : '' ?>" onclick="window.location.href='<?= base_url('admin/user-analytics?' . http_build_query($query)) ?>'" style="cursor:pointer;">
                                            <td>
                                                <strong><?= esc(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ('User #' . ($row['id'] ?? '-'))) ?></strong>
                                                <div class="text-muted small"><?= esc($row['email'] ?? '-') ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= esc(str_replace('_', ' ', $row['user_type'] ?? 'user')) ?></span></td>
                                            <td><span class="badge bg-<?= ($row['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>"><?= esc($row['status'] ?? 'unknown') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">No users matched the analytics filters.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-pie"></i> User Insight Panel</span>
                    <?php if (!empty($selectedUser)): ?>
                        <span class="badge bg-<?= ($selectedUser['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>"><?= esc($selectedUser['status'] ?? 'unknown') ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($selectedUser)): ?>
                        <h5 class="mb-2"><?= esc(trim(($selectedUser['first_name'] ?? '') . ' ' . ($selectedUser['last_name'] ?? '')) ?: ('User #' . ($selectedUser['id'] ?? '-'))) ?></h5>
                        <p class="text-muted mb-3"><?= esc($selectedUser['email'] ?? 'No email') ?></p>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Role</div>
                                    <strong><?= esc(str_replace('_', ' ', $selectedUser['user_type'] ?? 'user')) ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Phone</div>
                                    <strong><?= esc($selectedUser['phone'] ?? '-') ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Joined</div>
                                    <strong><?= esc($selectedUser['created_at'] ?? '-') ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Status</div>
                                    <strong><?= esc($selectedUser['status'] ?? 'unknown') ?></strong>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-muted mb-3">Dashboard Metrics</h6>
                        <?php if (!empty($selectedMetrics)): ?>
                            <div class="row g-3">
                                <?php foreach (array_slice($selectedMetrics, 0, 12, true) as $metricKey => $metricValue): ?>
                                    <?php $isMoneyMetric = preg_match('/revenue|spent|earnings|amount|collected|commission|payout/i', (string) $metricKey) === 1; ?>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small"><?= esc(str_replace('_', ' ', (string) $metricKey)) ?></div>
                                            <strong><?= $isMoneyMetric ? formatCurrency((float) $metricValue) : esc(is_bool($metricValue) ? ($metricValue ? 'Yes' : 'No') : (string) $metricValue) ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="border rounded p-3 text-muted">No dashboard analytics were returned for this user yet.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-pie fa-2x mb-3 opacity-50"></i>
                            <p class="mb-0">Select a user to inspect role-specific dashboard metrics.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/page_footer') ?>
