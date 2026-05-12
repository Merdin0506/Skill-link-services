<?= view('layouts/page_header', ['pageTitle' => 'Pending Workers']) ?>

        <!-- Page Content -->
        <div class="page-content">
            <div class="container-fluid">
                <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="mb-0"><i class="fas fa-user-clock"></i> Pending Worker Applications <span class="badge bg-warning ms-2"><?= count($pendingWorkers ?? []) ?></span></h3>
                                <small class="text-muted">Showing <?= count($pendingWorkers ?? []) ?> applications</small>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-list"></i> All Pending Workers
                                    </div>
                                </div>
                            <div class="card-body">
                                <?php if (!empty($pendingWorkers ?? [])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Worker Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Skills</th>
                                                    <th>Experience</th>
                                                    <th>Registered</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingWorkers as $worker): ?>
                                                    <?php
                                                        $workerId = (int) ($worker['id'] ?? 0);
                                                        $workerName = esc((string) ($worker['first_name'] ?? '') . ' ' . ($worker['last_name'] ?? ''));
                                                        $workerEmail = esc((string) ($worker['email'] ?? '-'));
                                                        $workerPhone = esc((string) ($worker['phone'] ?? 'N/A'));
                                                        $workerSkills = is_array($worker['skills'] ?? null) ? array_map('strval', $worker['skills']) : [];
                                                        $experienceYears = (int) ($worker['experience_years'] ?? 0);
                                                        $registeredAt = !empty($worker['created_at']) ? date('M d, Y', strtotime((string) $worker['created_at'])) : 'N/A';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= $workerName ?></strong>
                                                            <div class="text-muted small">ID: <?= $workerId ?></div>
                                                        </td>
                                                        <td><?= $workerEmail ?></td>
                                                        <td><?= $workerPhone ?></td>
                                                        <td>
                                                            <?php if (!empty($workerSkills)): ?>
                                                                <?php foreach ($workerSkills as $skill): ?>
                                                                    <span class="badge bg-secondary me-1 mb-1"><?= esc((string) $skill) ?></span>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted small">None</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark"><?= $experienceYears ?> years</span>
                                                        </td>
                                                        <td><?= $registeredAt ?></td>
                                                        <td class="text-end">
                                                            <a href="/admin/pending-workers/view/<?= $workerId ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-eye"></i> View Details
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-user-check fa-3x mb-3 opacity-50"></i>
                                        <h5 class="text-muted">No Pending Applications</h5>
                                        <p class="text-muted">All worker applications have been reviewed.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?= view('layouts/page_footer') ?>
