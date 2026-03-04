<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Settings</h3>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="mb-2"><strong>Environment:</strong> <?= esc($environment ?? '-') ?></p>
            <p class="mb-2"><strong>Base URL:</strong> <?= esc($baseUrl ?? '-') ?></p>
            <p class="mb-0"><strong>Current Role:</strong> <?= esc(ucfirst($role ?? '-')) ?></p>
        </div>
    </div>
</div>
</body>
</html>
