<?= view('layouts/page_header', ['pageTitle' => 'Rate Booking']) ?>

<div class="page-content">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0"><i class="fas fa-star"></i> Rate Completed Booking</h3>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Booking Details
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <strong>Reference:</strong> <?= esc($booking['booking_reference'] ?? '-') ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Service:</strong> <?= esc($booking['service_name'] ?? '-') ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Worker:</strong> <?= esc(trim(($booking['worker_first_name'] ?? '') . ' ' . ($booking['worker_last_name'] ?? ''))) ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Completed:</strong> <?= !empty($booking['completed_at']) ? date('M d, Y', strtotime($booking['completed_at'])) : '-' ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-edit"></i> Your Rating
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('customer/reviews/store/' . ($booking['id'] ?? '')) ?>">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Overall Rating</label>
                        <select name="rating" class="form-select" required>
                            <option value="">Select rating</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('rating') == $i ? 'selected' : '' ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Service Quality</label>
                        <select name="service_quality" class="form-select" required>
                            <option value="">Select score</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('service_quality') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Timeliness</label>
                        <select name="timeliness" class="form-select" required>
                            <option value="">Select score</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('timeliness') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Professionalism</label>
                        <select name="professionalism" class="form-select" required>
                            <option value="">Select score</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('professionalism') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label d-block">Would you recommend this worker?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="would_recommend" id="recommendYes" value="1" <?= old('would_recommend') === '1' ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="recommendYes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="would_recommend" id="recommendNo" value="0" <?= old('would_recommend') === '0' ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="recommendNo">No</label>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Comment (optional)</label>
                        <textarea name="comment" class="form-control" rows="4" maxlength="1000" placeholder="Share your experience..."><?= esc(old('comment') ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Rating
                    </button>
                    <a href="<?= base_url('customer/bookings') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= view('layouts/page_footer') ?>
