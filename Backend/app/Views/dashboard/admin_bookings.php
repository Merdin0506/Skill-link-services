<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Bookings</h3>
        <div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
        </div>
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
                            <th>Worker</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Total Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $row): ?>
                            <tr>
                                <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                <td><?= esc($row['title'] ?? '-') ?></td>
                                <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                <td><?= esc(trim(($row['worker_first_name'] ?? '') . ' ' . ($row['worker_last_name'] ?? '')) ?: '-') ?></td>
                                <td><?= esc($row['service_name'] ?? '-') ?></td>
                                <td><span class="badge bg-info text-dark"><?= esc($row['status'] ?? '-') ?></span></td>
                                <td>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4">No bookings found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
