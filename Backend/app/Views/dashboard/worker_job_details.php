<?= view('layouts/page_header', ['pageTitle' => 'Job Details']) ?>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-briefcase"></i> Job Details</h3>
        <a href="<?= base_url(($booking['status'] ?? '') === 'pending' ? 'worker/available-jobs' : 'worker/my-jobs') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if (session()->has('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-alt"></i> <?= esc($booking['booking_reference'] ?? 'N/A') ?></span>
                    <span class="badge bg-<?= ($booking['status'] ?? '') === 'pending' ? 'secondary' : (($booking['status'] ?? '') === 'assigned' ? 'info' : (($booking['status'] ?? '') === 'in_progress' ? 'warning' : (($booking['status'] ?? '') === 'completed' ? 'success' : 'dark'))) ?>">
                        <?= esc(ucfirst(str_replace('_', ' ', $booking['status'] ?? 'unknown'))) ?>
                    </span>
                </div>
                <div class="card-body">
                    <h5 class="mb-3"><?= esc($booking['title'] ?? 'Untitled Job') ?></h5>
                    <p class="text-muted mb-4"><?= esc($booking['description'] ?? 'No additional description provided.') ?></p>

                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Service</span>
                        <strong><?= esc($booking['service_name'] ?? 'N/A') ?></strong>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Category</span>
                        <span><?= esc(ucfirst($booking['service_category'] ?? 'general')) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Customer</span>
                        <span><?= esc(($booking['customer_first_name'] ?? '') . ' ' . ($booking['customer_last_name'] ?? '')) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Schedule</span>
                        <span><?= esc(($booking['scheduled_date'] ?? '-') . ' ' . ($booking['scheduled_time'] ?? '-')) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Location</span>
                        <span class="text-end" style="max-width: 60%;"><?= esc($booking['location_address'] ?? 'N/A') ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Total Fee</span>
                        <strong class="text-primary">₱<?= number_format((float) ($booking['total_fee'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="mb-4 d-flex justify-content-between">
                        <span class="text-muted">Your Earnings</span>
                        <strong class="text-success">₱<?= number_format((float) ($booking['worker_earnings'] ?? 0), 2) ?></strong>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (($booking['status'] ?? '') === 'pending'): ?>
                            <form action="<?= base_url('worker/accept-job/' . ($booking['id'] ?? 0)) ?>" method="POST" onsubmit="return confirm('Accept this job?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Accept Job
                                </button>
                            </form>
                        <?php elseif (($booking['status'] ?? '') === 'assigned'): ?>
                            <form action="<?= base_url('worker/start-job/' . ($booking['id'] ?? 0)) ?>" method="POST" onsubmit="return confirm('Start this job now?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Start Job
                                </button>
                            </form>
                        <?php elseif (($booking['status'] ?? '') === 'in_progress'): ?>
                            <a href="<?= base_url('worker/complete-job-form/' . ($booking['id'] ?? 0)) ?>" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Complete & Collect Payment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-user-check"></i> Review For This Booking
                </div>
                <div class="card-body">
                    <?php if (!empty($customerReviews)): ?>
                        <?php foreach ($customerReviews as $review): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong><?= esc($review['service_name'] ?? 'Service') ?></strong>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'] ?? date('Y-m-d'))) ?></small>
                                </div>
                                <div class="mb-2 text-warning">
                                    <?php $rating = (int) ($review['rating'] ?? 0); ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $rating ? 'fas fa-star' : 'far fa-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-dark ms-2"><?= $rating ?>/5</span>
                                </div>
                                <p class="mb-1 text-muted"><?= esc($review['comment'] ?? 'No comment provided.') ?></p>
                                <small class="text-muted">Ref: <?= esc($review['booking_reference'] ?? 'N/A') ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comments fa-2x mb-2 opacity-25"></i>
                            <p class="mb-0">This customer has no published review history yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/page_footer') ?>