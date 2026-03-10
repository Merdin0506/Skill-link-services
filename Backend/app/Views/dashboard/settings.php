<?= view('layouts/page_header', ['pageTitle' => 'Settings']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-cog"></i> Settings</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i> Application Settings
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Environment:</strong> <?= esc($environment ?? '-') ?></p>
                <p class="mb-2"><strong>Base URL:</strong> <?= esc($baseUrl ?? '-') ?></p>
                <p class="mb-0"><strong>Current Role:</strong> <?= esc(ucfirst($role ?? '-')) ?></p>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
