<?= view('layouts/page_header', ['pageTitle' => (isset($record) && $record ? 'Edit' : 'Create') . ' Service Record']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-file-invoice"></i>
                        <?= isset($record) && $record ? 'Edit' : 'Create' ?> Service Record
                    </h3>
                    <div>
                        <a href="<?= base_url('admin/records') ?>" class="btn btn-outline-secondary btn-sm">Back to Records</a>
                        <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i>
                <ul class="mb-0">
                    <?php foreach (session('errors') as $err): ?>
                        <li><?= esc($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-form"></i> Record Details
            </div>
            <div class="card-body">
                <form method="post" action="<?= isset($record) && $record ? base_url('admin/records/update/' . $record['id']) : '#' ?>">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                <option value="">-- Select Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= esc($c['id']) ?>" <?= (old('customer_id', $record['customer_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                        <?= esc($c['first_name'] . ' ' . $c['last_name']) ?> (<?= esc($c['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="service_id" id="service_id" class="form-select" required>
                                <option value="">-- Select Service --</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= esc($s['id']) ?>" <?= (old('service_id', $record['service_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                                        <?= esc($s['name']) ?> (<?= esc($s['category'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="provider_id" class="form-label">Provider / Worker</label>
                            <select name="provider_id" id="provider_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach ($workers as $w): ?>
                                    <option value="<?= esc($w['id']) ?>" <?= (old('provider_id', $record['provider_id'] ?? '') == $w['id']) ? 'selected' : '' ?>>
                                        <?= esc($w['first_name'] . ' ' . $w['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <?php
                                    $statuses = ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'];
                                    $currentStatus = old('status', $record['status'] ?? 'pending');
                                    foreach ($statuses as $st):
                                ?>
                                    <option value="<?= $st ?>" <?= $currentStatus === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <?php
                                    $payStatuses = ['unpaid', 'partial', 'paid', 'refunded'];
                                    $currentPay = old('payment_status', $record['payment_status'] ?? 'unpaid');
                                    foreach ($payStatuses as $ps):
                                ?>
                                    <option value="<?= $ps ?>" <?= $currentPay === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="scheduled_at" class="form-label">Scheduled At</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                                   value="<?= old('scheduled_at', isset($record['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($record['scheduled_at'])) : '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="payment_ref" class="form-label">Payment Reference</label>
                            <input type="text" name="payment_ref" id="payment_ref" class="form-control" maxlength="100"
                                   value="<?= esc(old('payment_ref', $record['payment_ref'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="labor_fee" class="form-label">Labor Fee (₱)</label>
                            <input type="number" step="0.01" min="0" name="labor_fee" id="labor_fee" class="form-control"
                                   value="<?= esc(old('labor_fee', $record['labor_fee'] ?? '0.00')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="platform_fee" class="form-label">Platform Fee (₱)</label>
                            <input type="number" step="0.01" min="0" name="platform_fee" id="platform_fee" class="form-control"
                                   value="<?= esc(old('platform_fee', $record['platform_fee'] ?? '0.00')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount (₱)</label>
                            <input type="text" class="form-control" id="total_display" readonly
                                   value="<?= number_format((float)($record['total_amount'] ?? 0), 2) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address_text" class="form-label">Address</label>
                        <input type="text" name="address_text" id="address_text" class="form-control" maxlength="1000"
                               value="<?= esc(old('address_text', $record['address_text'] ?? '')) ?>">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="customer_note" class="form-label">Customer Note</label>
                            <textarea name="customer_note" id="customer_note" class="form-control" rows="3"><?= esc(old('customer_note', $record['customer_note'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="provider_note" class="form-label">Provider Note</label>
                            <textarea name="provider_note" id="provider_note" class="form-control" rows="3"><?= esc(old('provider_note', $record['provider_note'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="admin_note" class="form-label">Admin Note</label>
                            <textarea name="admin_note" id="admin_note" class="form-control" rows="3"><?= esc(old('admin_note', $record['admin_note'] ?? '')) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" <?= isset($record) && $record ? '' : 'disabled' ?>>
                            <i class="fas fa-save"></i>
                            <?= isset($record) && $record ? 'Update Record' : 'Create Disabled' ?>
                        </button>
                        <a href="<?= base_url('admin/records') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-calculate total
        const laborEl = document.getElementById('labor_fee');
        const platformEl = document.getElementById('platform_fee');
        const totalEl = document.getElementById('total_display');

        function updateTotal() {
            const labor = parseFloat(laborEl.value) || 0;
            const platform = parseFloat(platformEl.value) || 0;
            totalEl.value = (labor + platform).toFixed(2);
        }
        laborEl.addEventListener('input', updateTotal);
        platformEl.addEventListener('input', updateTotal);
    </script>

<?= view('layouts/page_footer') ?>
