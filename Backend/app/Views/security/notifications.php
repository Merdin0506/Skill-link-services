<?= $this->extend('layouts/security_base') ?>

<?= $this->section('content') ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-0"><i class="fas fa-bell"></i> Notifications</h3>
                    <small class="text-muted">Recent security alerts for administrators</small>
                </div>
            </div>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($notifications)) : ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-bell-slash fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No notifications found.</p>
                        </div>
                    <?php else : ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification) : ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div class="me-3">
                                            <div class="fw-semibold"><?= esc((string) ($notification['title'] ?? 'Security Alert')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($notification['message'] ?? '')) ?></div>
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-clock"></i>
                                                <?= esc((string) ($notification['created_at'] ?? '')) ?>
                                                <?php if (!empty($notification['ip_address'])) : ?>
                                                    <span class="ms-2"><i class="fas fa-network-wired"></i> <?= esc((string) $notification['ip_address']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php $priority = strtolower((string) ($notification['priority'] ?? 'medium')); ?>
                                            <?php $badge = match ($priority) {
                                                'critical' => 'bg-danger',
                                                'high' => 'bg-warning text-dark',
                                                'low' => 'bg-success',
                                                default => 'bg-info',
                                            }; ?>
                                            <span class="badge <?= $badge ?>"><?= esc($priority) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

<?= $this->endSection() ?>
