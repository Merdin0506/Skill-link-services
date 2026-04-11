<?= view('layouts/page_header', ['pageTitle' => 'Record Worker Payout', 'suppressFlashMessages' => true]) ?>

    <!-- Page Content -->
    <div class="page-content">
        <?php $errors = session('errors') ?? []; ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-hand-holding-usd"></i> Record Worker Payout</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Booking Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Booking Reference:</strong> <?= esc($booking['booking_reference']) ?></p>
                        <p><strong>Worker:</strong> <?= esc($booking['worker_first_name'] . ' ' . $booking['worker_last_name']) ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Service:</strong> <?= esc($booking['title']) ?></p>
                        <p><strong>Worker Earnings:</strong> <span class="text-success fs-5">₱<?= number_format($booking['worker_earnings'], 2) ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-form"></i> Payout Details
            </div>
            <div class="card-body">
                <?php if (session()->has('error')): ?>
                    <div class="compact-form-notice error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?= esc(session('error')) ?></span>
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('finance/payouts/store/' . $booking['id']) ?>">
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Payout Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" id="amount" name="amount" 
                                       step="0.01" min="0" max="<?= $booking['worker_earnings'] ?>" 
                                       value="<?= old('amount', $booking['worker_earnings']) ?>" required>
                            </div>
                            <small class="text-muted">Maximum: ₱<?= number_format($booking['worker_earnings'], 2) ?></small>
                            <?php if (isset($errors['amount'])): ?><div class="field-error"><?= esc($errors['amount']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" id="payment_method" name="payment_method" required>
                                <option value="">-- Select Method --</option>
                                <option value="cash" <?= old('payment_method') === 'cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="gcash" <?= old('payment_method') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                                <option value="paymaya" <?= old('payment_method') === 'paymaya' ? 'selected' : '' ?>>PayMaya</option>
                                <option value="bank_transfer" <?= old('payment_method') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="credit_card" <?= old('payment_method') === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                            </select>
                            <?php if (isset($errors['payment_method'])): ?><div class="field-error"><?= esc($errors['payment_method']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" id="notes" name="notes" rows="3" 
                                  maxlength="500" placeholder="Add any notes about this payout"><?= old('notes') ?></textarea>
                        <small class="text-muted">Max 500 characters</small>
                        <?php if (isset($errors['notes'])): ?><div class="field-error"><?= esc($errors['notes']) ?></div><?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Record Payout
                        </button>
                        <a href="<?= base_url('finance/payouts') ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
