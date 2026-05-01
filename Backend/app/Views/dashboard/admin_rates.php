<?= view('layouts/page_header', ['pageTitle' => 'Rates']) ?>

<div class="page-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="fas fa-star"></i> Rates</h3>
                <small class="text-muted">Admin-only review and rating monitor</small>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-success">
                <div class="card-body p-2 text-center">
                    <h6 class="mb-1 text-muted small">Published</h6>
                    <h3 class="mb-0 text-success"><?= (int) ($statusCounts['published'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-secondary">
                <div class="card-body p-2 text-center">
                    <h6 class="mb-1 text-muted small">Hidden</h6>
                    <h3 class="mb-0 text-secondary"><?= (int) ($statusCounts['hidden'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-danger">
                <div class="card-body p-2 text-center">
                    <h6 class="mb-1 text-muted small">Flagged</h6>
                    <h3 class="mb-0 text-danger"><?= (int) ($statusCounts['flagged'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-dark">
                <div class="card-body p-2 text-center">
                    <h6 class="mb-1 text-muted small">Total</h6>
                    <h3 class="mb-0"><?= (int) (($statusCounts['published'] ?? 0) + ($statusCounts['hidden'] ?? 0) + ($statusCounts['flagged'] ?? 0)) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="get" action="<?= base_url('admin/rates') ?>">
                <!-- Search Row -->
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label small text-muted fw-600">Search</label>
                        <input type="text" name="q" class="form-control" placeholder="Search by booking ref, customer, worker, service, or comment..."
                            value="<?= esc($filters['q'] ?? '') ?>">
                    </div>
                </div>

                <!-- Filter Row -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-600">Worker</label>
                        <select name="worker_id" class="form-select">
                            <option value="">👥 All Workers</option>
                            <?php foreach ($workersWithReviews ?? [] as $worker): ?>
                                <option value="<?= esc($worker['id']) ?>" <?= ($filters['worker_id'] ?? '') == $worker['id'] ? 'selected' : '' ?>>
                                    <?= esc($worker['first_name'] . ' ' . $worker['last_name']) ?> (<?= (int)$worker['review_count'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-600">Status</label>
                        <select name="status" class="form-select">
                            <option value="">📋 All Status</option>
                            <?php foreach (['published', 'hidden', 'flagged'] as $st): ?>
                                <option value="<?= esc($st) ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>>
                                    <?php if ($st === 'published'): ?>
                                        ✓ 
                                    <?php elseif ($st === 'flagged'): ?>
                                        ⚠ 
                                    <?php else: ?>
                                        👁 
                                    <?php endif; ?>
                                    <?= esc(ucfirst($st)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-600">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="<?= base_url('admin/rates') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Display -->
                <?php $hasFilters = ($filters['q'] ?? '') || ($filters['worker_id'] ?? '') || ($filters['status'] ?? ''); ?>
                <?php if ($hasFilters): ?>
                    <div class="row">
                        <div class="col-12">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-2"></i>Filters applied:
                                <?php if ($filters['q'] ?? ''): ?>
                                    <span class="badge bg-light text-dark me-2">Search: "<?= esc(substr($filters['q'], 0, 20)) ?><?= strlen($filters['q']) > 20 ? '...' : '' ?>"</span>
                                <?php endif; ?>
                                <?php if ($filters['worker_id'] ?? ''): ?>
                                    <?php foreach ($workersWithReviews ?? [] as $w): ?>
                                        <?php if ($w['id'] == $filters['worker_id']): ?>
                                            <span class="badge bg-light text-dark me-2">Worker: <?= esc($w['first_name'] . ' ' . $w['last_name']) ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($filters['status'] ?? ''): ?>
                                    <span class="badge bg-light text-dark">Status: <?= esc(ucfirst($filters['status'])) ?></span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Rating Distribution
                    <?php if ($filters['worker_id'] ?? ''): ?>
                        <span class="badge bg-primary ms-2">
                            <?php foreach ($workersWithReviews ?? [] as $w): ?>
                                <?php if ($w['id'] == $filters['worker_id']): ?>
                                    <?= esc($w['first_name'] . ' ' . $w['last_name']) ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php 
                        $totalReviews = array_sum($ratingDistribution);
                        $starsLabels = [
                            5 => 'Excellent',
                            4 => 'Good',
                            3 => 'Average',
                            2 => 'Fair',
                            1 => 'Poor'
                        ];
                    ?>
                    
                    <!-- Total Reviews Summary -->
                    <div class="mb-4 p-3 bg-light rounded text-center">
                        <h5 class="mb-1 text-muted">Total Reviews</h5>
                        <h3 class="mb-0"><?= $totalReviews ?></h3>
                    </div>

                    <!-- Rating Distribution Boxes -->
                    <div class="row g-2">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php 
                                $count = (int) ($ratingDistribution[$i] ?? 0);
                                $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0;
                            ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="border rounded p-3 text-center h-100" style="background-color: #f8f9fa; transition: all 0.2s;">
                                    <div class="text-warning mb-2" style="font-size: 18px;">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="<?= $s <= $i ? 'fas fa-star' : 'far fa-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted d-block mb-2" style="font-size: 11px;">
                                        <?= $starsLabels[$i] ?>
                                    </small>
                                    <h4 class="mb-1" style="font-weight: 700;"><?= $count ?></h4>
                                    <small class="text-muted">reviews</small>
                                    <?php if ($totalReviews > 0): ?>
                                        <div class="mt-2 pt-2 border-top">
                                            <small class="text-primary" style="font-weight: 600;"><?= $percentage ?>%</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <i class="fas fa-list"></i> Review List
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 table-fit">
                    <thead>
                        <tr>
                            <th>Booking Ref</th>
                            <th>Customer</th>
                            <th>Worker</th>
                            <th>Service</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <?php
                                $reviewStatus = (string) ($review['status'] ?? 'hidden');
                                $statusClass = $reviewStatus === 'published' ? 'success' : ($reviewStatus === 'flagged' ? 'danger' : 'secondary');
                                $rating = (int) ($review['rating'] ?? 0);
                                ?>
                                <tr>
                                    <td><strong><?= esc($review['booking_reference'] ?? 'N/A') ?></strong></td>
                                    <td><?= esc(trim(($review['customer_first_name'] ?? '') . ' ' . ($review['customer_last_name'] ?? '')) ?: 'N/A') ?></td>
                                    <td><?= esc(trim(($review['worker_first_name'] ?? '') . ' ' . ($review['worker_last_name'] ?? '')) ?: 'N/A') ?></td>
                                    <td><?= esc($review['service_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="text-warning">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i class="<?= $s <= $rating ? 'fas fa-star' : 'far fa-star' ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <span class="ms-1"><?= $rating ?>/5</span>
                                    </td>
                                    <td><span class="badge bg-<?= $statusClass ?>"><?= esc(ucfirst($reviewStatus)) ?></span></td>
                                    <td><?= esc($review['comment'] ?? '-') ?></td>
                                    <td><?= esc(date('M d, Y', strtotime($review['created_at'] ?? date('Y-m-d')))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No rates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/page_footer') ?>
