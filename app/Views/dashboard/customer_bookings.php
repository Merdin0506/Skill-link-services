<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">My Bookings</h3>
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
                            <th>Service</th>
                            <th>Worker</th>
                            <th>Scheduled Date</th>
                            <th>Status</th>
                            <th>Total Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $row): ?>
                            <tr>
                                <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                <td>
                                    <?= esc($row['service_name'] ?? '-') ?>
                                    <?php if (!empty($row['service_category'])): ?>
                                        <small class="text-muted d-block"><?= esc($row['service_category']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc(trim(($row['worker_first_name'] ?? '') . ' ' . ($row['worker_last_name'] ?? '')) ?: 'Not assigned yet') ?></td>
                                <td><?= !empty($row['scheduled_date']) ? date('M d, Y', strtotime($row['scheduled_date'])) : '-' ?></td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-info',
                                        'in_progress' => 'bg-primary',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $status = $row['status'] ?? 'pending';
                                    ?>
                                    <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>"><?= esc(ucfirst(str_replace('_', ' ', $status))) ?></span>
                                </td>
                                <td><strong>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">
                            <p class="text-muted mb-0">No bookings yet.</p>
                            <a href="<?= base_url('customer/services') ?>" class="btn btn-primary btn-sm mt-2">Browse Services</a>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
