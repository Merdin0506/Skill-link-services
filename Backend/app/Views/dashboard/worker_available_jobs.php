<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Jobs - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Available Jobs</h3>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Schedule</th>
                            <th>Priority</th>
                            <th>Total Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($jobs)): ?>
                        <?php foreach ($jobs as $row): ?>
                        <tr>
                            <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                            <td><?= esc($row['service_name'] ?? '-') ?></td>
                            <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                            <td><?= esc(($row['scheduled_date'] ?? '-') . ' ' . ($row['scheduled_time'] ?? '')) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= esc($row['priority'] ?? '-') ?></span></td>
                            <td>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">No available jobs right now.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
