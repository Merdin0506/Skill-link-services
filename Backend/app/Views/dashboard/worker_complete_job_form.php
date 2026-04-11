<?= view('layouts/page_header', ['pageTitle' => 'Complete Job & Collect Payment', 'suppressFlashMessages' => true]) ?>

    <!-- Page Content -->
    <div class="page-content">
        <?php $errors = session('errors') ?? []; ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-check-circle"></i> Complete Job & Collect Payment</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Job Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Booking Reference:</strong> <?= esc($booking['booking_reference']) ?></p>
                        <p><strong>Service:</strong> <?= esc($booking['title']) ?></p>
                        <p><strong>Customer:</strong> <?= esc($booking['customer_first_name'] . ' ' . $booking['customer_last_name']) ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Total Amount:</strong> <span class="text-primary fs-5">₱<?= number_format($booking['total_fee'], 2) ?></span></p>
                        <p><strong>Your Earnings:</strong> <span class="text-success fs-5">₱<?= number_format($booking['worker_earnings'], 2) ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-hand-holding-usd"></i> Step 1: Collect Payment from Customer
            </div>
            <div class="card-body">
                <?php if (session()->has('error')): ?>
                    <div class="compact-form-notice error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?= esc(session('error')) ?></span>
                    </div>
                <?php endif; ?>
                <div class="alert alert-warning mb-4">
                    <h5><i class="fas fa-exclamation-triangle"></i> Important Instructions:</h5>
                    <ol class="mb-0">
                        <li><strong>Collect ₱<?= number_format($booking['total_fee'], 2) ?></strong> from the customer (cash, GCash, etc.)</li>
                        <li><strong>Confirm</strong> the customer has paid you</li>
                        <li><strong>Enter</strong> payment details below to record the transaction</li>
                        <li><strong>You will receive ₱<?= number_format($booking['worker_earnings'], 2) ?></strong> as your payout from Finance later</li>
                    </ol>
                </div>

                <form method="POST" action="<?= base_url('worker/complete-job/' . $booking['id']) ?>">
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <h6 class="text-primary"><i class="fas fa-clipboard-check"></i> Step 2: Enter Payment Details You Received</h6>
                            <hr>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="amount_collected" class="form-label">
                                Amount You Collected from Customer <span class="text-danger">*</span>
                                <i class="fas fa-question-circle text-muted" title="Total amount the customer paid you"></i>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-success text-white"><i class="fas fa-peso-sign"></i></span>
                                <input type="number" class="form-control form-control-lg <?= isset($errors['amount_collected']) ? 'is-invalid' : '' ?>" id="amount_collected" name="amount_collected" 
                                       step="0.01" min="0" max="<?= $booking['total_fee'] ?>" 
                                       value="<?= old('amount_collected', $booking['total_fee']) ?>" required>
                            </div>
                            <small class="text-success"><i class="fas fa-check-circle"></i> Expected amount: ₱<?= number_format($booking['total_fee'], 2) ?></small>
                            <?php if (isset($errors['amount_collected'])): ?><div class="field-error"><?= esc($errors['amount_collected']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">
                                How Did Customer Pay? <span class="text-danger">*</span>
                                <i class="fas fa-question-circle text-muted" title="Payment method used by customer"></i>
                            </label>
                            <select class="form-select form-select-lg <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" id="payment_method" name="payment_method" required>
                                <option value="">-- Select Payment Method --</option>
                                <option value="cash" <?= old('payment_method') === 'cash' ? 'selected' : '' ?>><i class="fas fa-money-bill"></i> Cash (Hand to Hand)</option>
                                <option value="gcash" <?= old('payment_method') === 'gcash' ? 'selected' : '' ?>>GCash (Mobile Wallet)</option>
                                <option value="paymaya" <?= old('payment_method') === 'paymaya' ? 'selected' : '' ?>>PayMaya (Mobile Wallet)</option>
                                <option value="bank_transfer" <?= old('payment_method') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            </select>
                            <?php if (isset($errors['payment_method'])): ?><div class="field-error"><?= esc($errors['payment_method']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control <?= isset($errors['payment_notes']) ? 'is-invalid' : '' ?>" id="payment_notes" name="payment_notes" rows="2" 
                                  maxlength="500" placeholder="Example: Customer paid exact amount in cash, Transaction ID: 1234567890"><?= old('payment_notes') ?></textarea>
                        <small class="text-muted">Add any notes about the payment (max 500 characters)</small>
                        <?php if (isset($errors['payment_notes'])): ?><div class="field-error"><?= esc($errors['payment_notes']) ?></div><?php endif; ?>
                    </div>

                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <strong>Before You Submit:</strong>
                        <ul class="mb-0">
                            <li>✓ Confirm you have <strong>collected ₱<?= number_format($booking['total_fee'], 2) ?></strong> from the customer</li>
                            <li>✓ The job is fully completed to customer's satisfaction</li>
                            <li>✓ Payment method selected matches how customer paid you</li>
                        </ul>
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="submit" class="btn btn-success btn-lg px-5" onclick="return confirm('⚠️ CONFIRM:\n\n✓ Have you collected ₱<?= number_format($booking['total_fee'], 2) ?> from the customer?\n✓ Is the job completed?\n✓ Is the payment information correct?\n\nClick OK to complete job and record payment.');">
                            <i class="fas fa-check-double"></i> Yes, I Collected Payment - Complete Job Now
                        </button>
                        <a href="<?= base_url('worker/my-jobs') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3 border-info">
            <div class="card-header bg-info text-white">
                <i class="fas fa-calculator"></i> Money Flow Breakdown
            </div>
            <div class="card-body">
                <h6 class="text-center mb-3"><i class="fas fa-info-circle"></i> Understanding the Payment Process</h6>
                <table class="table table-bordered mb-3">
                    <tr class="table-success">
                        <td><strong><i class="fas fa-user"></i> Customer Pays You (Now):</strong></td>
                        <td class="text-end"><strong class="fs-5">₱<?= number_format($booking['total_fee'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="bg-light">
                            <small><i class="fas fa-arrow-down"></i> This amount is split between you and the company:</small>
                        </td>
                    </tr>
                    <tr class="table-primary">
                        <td>&nbsp;&nbsp;&nbsp;<i class="fas fa-user-check"></i> Your Earnings (You receive later from Finance):</td>
                        <td class="text-end text-success"><strong>₱<?= number_format($booking['worker_earnings'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;<i class="fas fa-building"></i> Company Commission (You remit to company):</td>
                        <td class="text-end text-muted">₱<?= number_format($booking['total_fee'] - $booking['worker_earnings'], 2) ?></td>
                    </tr>
                </table>
                
                <div class="alert alert-info mb-0">
                    <h6><i class="fas fa-lightbulb"></i> How It Works:</h6>
                    <ol class="mb-0 small">
                        <li><strong>You collect</strong> ₱<?= number_format($booking['total_fee'], 2) ?> from customer today</li>
                        <li><strong>System records</strong> the payment automatically when you submit this form</li>
                        <li><strong>Finance processes</strong> your ₱<?= number_format($booking['worker_earnings'], 2) ?> payout (via GCash/Cash/Bank)</li>
                        <li><strong>You remit</strong> ₱<?= number_format($booking['total_fee'] - $booking['worker_earnings'], 2) ?> company commission separately</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
