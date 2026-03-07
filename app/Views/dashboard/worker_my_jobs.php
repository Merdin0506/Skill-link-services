<?= view('layouts/page_header', ['pageTitle' => 'My Jobs']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-tasks"></i> My Jobs</h3>
            </div>
        </div>

        <div class="alert alert-primary">
            <i class="fas fa-info-circle"></i> <strong>Job Workflow:</strong> 
            Accept Job → Start Job → <strong>Complete & Collect Payment from Customer</strong> → Get Paid by Finance
        </div>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= session('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= session('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Assigned Jobs
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Title</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Earnings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $row): ?>
                            <tr onclick="window.location.href='<?= base_url('worker/job/' . ($row['id'] ?? 0)) ?>'" style="cursor: pointer;">
                                <td><strong><?= esc($row['booking_reference'] ?? '-') ?></strong></td>
                                <td><?= esc($row['title'] ?? '-') ?></td>
                                <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                <td><?= esc($row['service_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'assigned' => 'info',
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$row['status'] ?? ''] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusColor ?>"><?= esc(ucfirst(str_replace('_', ' ', $row['status'] ?? '-'))) ?></span>
                                </td>
                                <td><strong>₱<?= number_format((float)($row['worker_earnings'] ?? 0), 2) ?></strong></td>
                                <td>
                                    <a href="<?= base_url('worker/job/' . ($row['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm me-1" onclick="event.stopPropagation();">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                    <?php if ($row['status'] === 'assigned'): ?>
                                        <form action="<?= base_url('worker/start-job/' . ($row['id'] ?? 0)) ?>" method="POST" style="display:inline;" onsubmit="event.stopPropagation();">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Start working on this job?');">
                                                <i class="fas fa-play"></i> Start
                                            </button>
                                        </form>
                                    <?php elseif ($row['status'] === 'in_progress'): ?>
                                        <a href="<?= base_url('worker/complete-job-form/' . ($row['id'] ?? 0)) ?>" 
                                           class="btn btn-success btn-sm" 
                                           onclick="event.stopPropagation();"
                                           title="Complete job and record payment collected from customer">
                                            <i class="fas fa-check-circle"></i> Complete & Collect Payment
                                        </a>
                                    <?php elseif ($row['status'] === 'completed'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Done</span>
                                    <?php elseif ($row['status'] === 'cancelled'): ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No assigned jobs yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
