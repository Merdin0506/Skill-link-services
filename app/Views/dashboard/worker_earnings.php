<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Earnings</h3>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-1">Total Completed Earnings</h5>
            <h2 class="mb-0 text-success">₱<?= number_format((float)($totalEarnings ?? 0), 2) ?></h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Recent Payouts</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($recentPayouts)): ?>
                        <?php foreach ($recentPayouts as $row): ?>
                        <tr>
                            <td><?= esc($row['payment_reference'] ?? '-') ?></td>
                            <td><span class="badge bg-secondary"><?= esc($row['status'] ?? '-') ?></span></td>
                            <td><?= esc($row['payment_method'] ?? '-') ?></td>
                            <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                            <td><?= esc($row['payment_date'] ?? $row['created_at'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No payout records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
