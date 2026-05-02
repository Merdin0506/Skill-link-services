<?= $this->extend('layouts/security_base') ?>

<?= $this->section('content') ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-0"><i class="fas fa-ban"></i> Blocked IPs</h3>
                    <small class="text-muted">Active IP blocks enforced by the security system</small>
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
                    <?php if (empty($blockedIps)) : ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No active blocked IPs.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Reason</th>
                                    <th>Temporary</th>
                                    <th>Blocked Until</th>
                                    <th>Attempts</th>
                                    <th>Last Attempt</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($blockedIps as $row) : ?>
                                    <tr>
                                        <td><code><?= esc((string) ($row['ip_address'] ?? '')) ?></code></td>
                                        <td><?= esc((string) ($row['reason'] ?? '')) ?></td>
                                        <td><?= !empty($row['is_temporary']) ? 'Yes' : 'No' ?></td>
                                        <td><?= esc((string) ($row['blocked_until'] ?? '—')) ?></td>
                                        <td><?= esc((string) ($row['attempts_count'] ?? '0')) ?></td>
                                        <td><?= esc((string) ($row['last_attempt'] ?? '—')) ?></td>
                                        <td class="text-end">
                                            <?php if (!empty($row['id'])) : ?>
                                                <form method="post" action="/security/unblock-ip/<?= (int) $row['id'] ?>" class="d-inline">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-unlock"></i> Unblock
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

<?= $this->endSection() ?>
