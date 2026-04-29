<?php
    $formatActivityText = static function (?string $value): string {
        $value = (string) $value;
        return ucwords(str_replace('_', ' ', $value));
    };

    $formatActivityUser = static function (array $log, string $prefix): string {
        $name = trim((string) ($log[$prefix . '_first_name'] ?? '') . ' ' . (string) ($log[$prefix . '_last_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return (string) ($log[$prefix . '_email'] ?? 'Unknown');
    };

    $formatActivityDetails = static function (array $details): string {
        if (empty($details)) {
            return '-';
        }

        if (isset($details['reason'])) {
            return ucwords(str_replace('_', ' ', (string) $details['reason']));
        }

        if (isset($details['changed_fields']) && is_array($details['changed_fields'])) {
            $fields = array_keys($details['changed_fields']);
            return $fields ? implode(', ', array_map(static fn ($field) => ucwords(str_replace('_', ' ', (string) $field)), $fields)) : '-';
        }

        if (isset($details['created_fields']) && is_array($details['created_fields'])) {
            $fields = array_keys($details['created_fields']);
            return $fields ? 'Created: ' . implode(', ', array_map(static fn ($field) => ucwords(str_replace('_', ' ', (string) $field)), $fields)) : '-';
        }

        if (isset($details['method'])) {
            return ucwords(str_replace('_', ' ', (string) $details['method']));
        }

        return 'Details available';
    };
?>

<?= view('layouts/page_header', ['pageTitle' => 'Settings']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-cog"></i> Settings</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i> Application Settings
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Environment:</strong> <?= esc($environment ?? '-') ?></p>
                <p class="mb-2"><strong>Base URL:</strong> <?= esc($baseUrl ?? '-') ?></p>
                <p class="mb-0"><strong>Current Role:</strong> <?= esc(ucfirst($role ?? '-')) ?></p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-user-clock"></i> Current Session
            </div>
            <div class="card-body">
                <?php if (!empty($currentSession)): ?>
                    <p class="mb-2"><strong>Session Type:</strong> <?= esc(ucfirst($currentSession['session_type'] ?? 'web')) ?></p>
                    <p class="mb-2"><strong>Logged In At:</strong> <?= esc($currentSession['logged_in_at'] ?? '-') ?></p>
                    <p class="mb-2"><strong>Last Activity:</strong> <?= esc($currentSession['last_activity_at'] ?? '-') ?></p>
                    <p class="mb-2"><strong>IP Address:</strong> <?= esc($currentSession['ip_address'] ?? '-') ?></p>
                    <p class="mb-0"><strong>Device:</strong> <?= esc($currentSession['device_label'] ?? '-') ?></p>
                <?php else: ?>
                    <p class="mb-0 text-muted">No tracked session information is available for the current login.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($myActiveSessions) && count($myActiveSessions) > 1): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-layer-group"></i> My Active Sessions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
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
                                <?php foreach ($myActiveSessions as $trackedSession): ?>
                                    <tr>
                                        <td><?= esc(ucfirst($trackedSession['session_type'] ?? 'web')) ?></td>
                                        <td><?= esc($trackedSession['logged_in_at'] ?? '-') ?></td>
                                        <td><?= esc($trackedSession['last_activity_at'] ?? '-') ?></td>
                                        <td><?= esc($trackedSession['ip_address'] ?? '-') ?></td>
                                        <td><?= esc($trackedSession['device_label'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($recentActiveSessions)): ?>
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-shield-alt"></i> Active Session Monitor</span>
                    <span class="badge bg-primary">Total Active: <?= esc((string) ($activeSessionCount ?? 0)) ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
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
                                <?php foreach ($recentActiveSessions as $trackedSession): ?>
                                    <tr>
                                        <td><?= esc(trim(($trackedSession['first_name'] ?? '') . ' ' . ($trackedSession['last_name'] ?? '')) ?: ($trackedSession['email'] ?? 'Unknown User')) ?></td>
                                        <td><?= esc(ucfirst($trackedSession['user_type'] ?? '-')) ?></td>
                                        <td><?= esc(ucfirst($trackedSession['session_type'] ?? 'web')) ?></td>
                                        <td><?= esc($trackedSession['last_activity_at'] ?? '-') ?></td>
                                        <td><?= esc($trackedSession['ip_address'] ?? '-') ?></td>
                                        <td><?= esc($trackedSession['device_label'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-history"></i> My Account Activity
            </div>
            <div class="card-body">
                <?php if (!empty($myActivityLogs)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
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
                                <?php foreach ($myActivityLogs as $activityLog): ?>
                                    <tr>
                                        <td><?= esc($activityLog['created_at'] ?? '-') ?></td>
                                        <td><?= esc($formatActivityText($activityLog['event_type'] ?? '-')) ?></td>
                                        <td><?= esc($formatActivityText($activityLog['action'] ?? '-')) ?></td>
                                        <td>
                                            <?php
                                                $outcome = (string) ($activityLog['outcome'] ?? '-');
                                                $badgeClass = $outcome === 'success' ? 'bg-success' : (in_array($outcome, ['failed', 'blocked', 'locked', 'validation_failed'], true) ? 'bg-danger' : 'bg-secondary');
                                            ?>
                                            <span class="badge <?= esc($badgeClass) ?>"><?= esc($formatActivityText($outcome)) ?></span>
                                        </td>
                                        <td><?= esc(strtoupper((string) ($activityLog['source'] ?? '-'))) ?></td>
                                        <td><?= esc($formatActivityDetails($activityLog['details'] ?? [])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0 text-muted">No account activity has been recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($recentActivityLogs)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Recent System Activity
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
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
                                <?php foreach ($recentActivityLogs as $activityLog): ?>
                                    <tr>
                                        <td><?= esc($activityLog['created_at'] ?? '-') ?></td>
                                        <td><?= esc($formatActivityUser($activityLog, 'actor')) ?></td>
                                        <td><?= esc($formatActivityUser($activityLog, 'target')) ?></td>
                                        <td><?= esc($formatActivityText($activityLog['event_type'] ?? '-')) ?></td>
                                        <td><?= esc($formatActivityText($activityLog['action'] ?? '-')) ?></td>
                                        <td>
                                            <?php
                                                $outcome = (string) ($activityLog['outcome'] ?? '-');
                                                $badgeClass = $outcome === 'success' ? 'bg-success' : (in_array($outcome, ['failed', 'blocked', 'locked', 'validation_failed'], true) ? 'bg-danger' : 'bg-secondary');
                                            ?>
                                            <span class="badge <?= esc($badgeClass) ?>"><?= esc($formatActivityText($outcome)) ?></span>
                                        </td>
                                        <td><?= esc(strtoupper((string) ($activityLog['source'] ?? '-'))) ?></td>
                                        <td><?= esc($formatActivityDetails($activityLog['details'] ?? [])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?= view('layouts/page_footer') ?>
