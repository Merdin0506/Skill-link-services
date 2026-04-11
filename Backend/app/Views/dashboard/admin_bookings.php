<?= view('layouts/page_header', ['pageTitle' => 'Bookings']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-calendar-check"></i> Bookings Management</h3>

                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Bookings
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-fit table-bookings mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Title</th>
                                <th>Customer</th>
                                <th>Worker</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Total Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $row): ?>
                                <tr>
                                    <td><strong><?= esc($row['booking_reference'] ?? '-') ?></strong></td>
                                    <td><?= esc($row['title'] ?? '-') ?></td>
                                    <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                    <td>
                                        <?php if (!empty($row['worker_id'])): ?>
                                            <span class="badge bg-success">
                                                <?= esc(trim(($row['worker_first_name'] ?? '') . ' ' . ($row['worker_last_name'] ?? ''))) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= esc($row['service_name'] ?? '-') ?>
                                        <?php if (!empty($row['service_category'])): ?>
                                            <br><small class="text-muted"><i class="fas fa-tag"></i> <?= esc(ucfirst($row['service_category'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'assigned' => 'info',
                                            'in_progress' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'rejected' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$row['status'] ?? ''] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>"><?= esc(ucfirst(str_replace('_', ' ', $row['status'] ?? '-'))) ?></span>
                                    </td>
                                    <td><strong>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></strong></td>
                                    <td>
                                        <?php if (in_array($row['status'], ['pending', 'assigned'])): ?>
                                            <?php 
                                            // Get workers matching this booking's service category
                                            $categoryWorkers = $workersByCategory[$row['service_category'] ?? ''] ?? $workers ?? [];
                                            ?>
                                            <form action="<?= base_url('admin/assign-worker') ?>" method="POST" class="d-inline-flex align-items-center gap-2">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                                <select name="worker_id" class="form-select form-select-sm w-auto" required>
                                                    <option value="">Select Worker</option>
                                                    <?php if (!empty($categoryWorkers)): ?>
                                                        <?php foreach ($categoryWorkers as $worker): ?>
                                                            <option value="<?= $worker['id'] ?>" <?= ($row['worker_id'] ?? 0) == $worker['id'] ? 'selected' : '' ?>>
                                                                <?= esc($worker['first_name'] . ' ' . $worker['last_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="" disabled>No matching workers</option>
                                                    <?php endif; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm" <?= empty($categoryWorkers) ? 'disabled' : '' ?>>
                                                    <i class="fas fa-user-check"></i> Assign
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4">No bookings found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
