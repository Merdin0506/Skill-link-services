<?= view('layouts/page_header', ['pageTitle' => 'My Bookings']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-calendar-check"></i> My Bookings</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Booking List
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-fit table-bookings mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Service</th>
                                <th>Worker</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Total Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $row): ?>
                                <tr>
                                    <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                    <td>
                                        <?= esc($row['service_name'] ?? '-') ?>
                                        <?php if (!empty($row['service_category'])): ?>
                                            <small class="text-muted d-block"><?= esc($row['service_category']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc(trim(($row['worker_first_name'] ?? '') . ' ' . ($row['worker_last_name'] ?? '')) ?: 'Not assigned yet') ?></td>
                                    <td><?= !empty($row['scheduled_date']) ? date('M d, Y', strtotime($row['scheduled_date'])) : '-' ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = [
                                            'pending' => 'bg-warning',
                                            'confirmed' => 'bg-info',
                                            'in_progress' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ];
                                        $status = $row['status'] ?? 'pending';
                                        ?>
                                        <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>"><?= esc(ucfirst(str_replace('_', ' ', $status))) ?></span>
                                    </td>
                                    <td><strong>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></strong></td>
                                    <td>
                                        <?php if (($row['status'] ?? '') === 'completed' && empty($row['review_id'])): ?>
                                            <a href="<?= base_url('customer/reviews/create/' . ($row['id'] ?? '')) ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-star"></i> Rate
                                            </a>
                                        <?php elseif (!empty($row['review_id'])): ?>
                                            <span class="badge bg-success">Rated</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">
                                <p class="text-muted mb-0">No bookings yet.</p>
                                <a href="<?= base_url('customer/services') ?>" class="btn btn-primary btn-sm mt-2">Browse Services</a>
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
