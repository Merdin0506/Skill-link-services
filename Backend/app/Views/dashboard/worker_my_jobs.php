<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">My Jobs</h3>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($jobs)): ?>
                        <?php foreach ($jobs as $row): ?>
                        <tr>
                            <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                            <td><?= esc($row['title'] ?? '-') ?></td>
                            <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                            <td><?= esc($row['service_name'] ?? '-') ?></td>
                            <td><span class="badge bg-info text-dark"><?= esc($row['status'] ?? '-') ?></span></td>
                            <td>₱<?= number_format((float)($row['worker_earnings'] ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">No assigned jobs yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
