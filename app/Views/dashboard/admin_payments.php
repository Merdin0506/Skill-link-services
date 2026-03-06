<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Payments</h3>
        <div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
            <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Booking</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $row): ?>
                            <tr>
                                <td><?= esc($row['payment_reference'] ?? '-') ?></td>
                                <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                <td><?= esc($row['payment_method'] ?? '-') ?></td>
                                <td><?= esc($row['payment_type'] ?? '-') ?></td>
                                <td><span class="badge bg-secondary"><?= esc($row['status'] ?? '-') ?></span></td>
                                <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                <td><?= esc($row['payment_date'] ?? $row['created_at'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4">No payments found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
