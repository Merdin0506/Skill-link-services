<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Profile</h3>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Name:</strong> <?= esc(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                <div class="col-md-6"><strong>Email:</strong> <?= esc($user['email'] ?? '-') ?></div>
                <div class="col-md-6"><strong>Role:</strong> <?= esc(ucfirst($role ?? ($user['user_type'] ?? ''))) ?></div>
                <div class="col-md-6"><strong>Phone:</strong> <?= esc($user['phone'] ?? '-') ?></div>
                <div class="col-12"><strong>Address:</strong> <?= esc($user['address'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
