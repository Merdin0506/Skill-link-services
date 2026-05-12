<?= view('layouts/page_header', ['pageTitle' => 'Worker Details']) ?>

<?php
    $resumePath = (string) ($worker['resume_path'] ?? '');
    $resumePaths = [];
    if ($resumePath !== '') {
        $decodedResumePath = json_decode($resumePath, true);
        if (is_array($decodedResumePath)) {
            $resumePaths = array_values(array_filter(array_map('strval', $decodedResumePath)));
        } else {
            $resumePaths = [$resumePath];
        }
    }
    $workerId = (int) ($worker['id'] ?? 0);
    $latestResumeIndex = !empty($resumePaths) ? count($resumePaths) - 1 : 0;
    $latestResumePreviewUrl = $workerId > 0 ? base_url('admin/pending-workers/preview/' . $workerId . '?file=' . $latestResumeIndex) : '#';
    $latestResumeDownloadUrl = $workerId > 0 ? base_url('admin/pending-workers/resume/' . $workerId . '?file=' . $latestResumeIndex) : '#';
?>

<style>
    .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-radius: 10px;
        margin-bottom: 25px;
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background: white;
        border-bottom: 1px solid #e8e8e8;
        border-radius: 10px 10px 0 0;
        padding: 20px;
        font-weight: 600;
        color: var(--text-dark);
    }

    .card-header i {
        margin-right: 10px;
        color: var(--primary-color);
    }

    .card-body {
        padding: 25px;
    }

    .badge {
        padding: 8px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .page-content {
        padding: 30px;
    }

    .main-content {
        padding: 15px;
    }

    .footer {
        text-align: center;
        padding: 20px;
        color: var(--text-muted);
        font-size: 12px;
        margin-top: 30px;
    }

    .role-badge {
        display: inline-block;
        padding: 8px 15px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .bg-light {
        background-color: #f8f9fa !important;
    }

    textarea.form-control {
        resize: vertical;
        font-size: 14px;
    }

    .btn {
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255,255,255,0.6);
        border-top-color: rgba(255,255,255,1);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        vertical-align: -0.12em;
        margin-right: .5rem;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .btn-loading { opacity: 0.95; transform: translateY(0); }

    .email-status { transition: opacity .25s ease, transform .25s ease; }

    @media (max-width: 768px) {
        .page-content {
            padding: 15px;
        }

        .card-body {
            padding: 16px;
        }
    }
</style>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user"></i> Personal Information
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">First Name</label>
                                <p class="mb-0"><strong><?= esc($worker['first_name'] ?? '') ?></strong></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Last Name</label>
                                <p class="mb-0"><strong><?= esc($worker['last_name'] ?? '') ?></strong></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Email</label>
                                <p class="mb-0"><a href="mailto:<?= esc($worker['email'] ?? '') ?>"><?= esc($worker['email'] ?? '') ?></a></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Phone</label>
                                <p class="mb-0"><strong><?= esc($worker['phone'] ?? 'N/A') ?></strong></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Address</label>
                                <p class="mb-0"><strong><?= esc($worker['address'] ?? 'N/A') ?></strong></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">User ID</label>
                                <p class="mb-0"><strong><?= (int) ($worker['id'] ?? 0) ?></strong></p>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Application Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-hourglass-half"></i> <?= ucfirst($worker['status'] ?? 'unknown') ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Applied Date</label>
                                <p class="mb-0"><strong><?= date('M d, Y h:i A', strtotime($worker['created_at'] ?? date('Y-m-d'))) ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tools"></i> Skills & Experience
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Selected Skills</label>
                            <div>
                                <?php if (!empty($skills ?? [])): ?>
                                    <?php foreach ($skills as $skill): ?>
                                        <span class="badge bg-primary me-2 mb-2"><?= esc($skill) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No skills selected</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="text-muted small">Years of Experience</label>
                                <p class="mb-0"><strong><?= (int) ($worker['experience_years'] ?? 0) ?> years</strong></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Commission Rate</label>
                                <p class="mb-0"><strong><?= (float) ($worker['commission_rate'] ?? 20) ?>%</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-arrow-down"></i> Resume
                    </div>
                    <div class="card-body">
                        <?php if (!empty($resumePaths)): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($resumePaths as $index => $resumeFilePath): ?>
                                    <?php $fileName = basename($resumeFilePath); ?>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm align-self-start resume-preview-trigger"
                                        data-resume-preview-url="<?= esc(base_url('admin/pending-workers/preview/' . $workerId . '?file=' . $index)) ?>"
                                        data-resume-download-url="<?= esc(base_url('admin/pending-workers/resume/' . $workerId . '?file=' . $index)) ?>"
                                        data-resume-file-name="<?= esc($fileName) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#resumePreviewModal">
                                        <i class="fas fa-file-pdf me-1"></i> <?= esc($fileName) ?>
                                    </button>
                                <?php endforeach; ?>
                                <p class="mb-0 text-muted small">Each uploaded PDF stays in the list. Click any file to preview it in the pop-up.</p>
                            </div>
                        <?php else: ?>
                            <p class="mb-0 text-muted">No resume has been uploaded for this worker.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-envelope"></i> Send Email
                    </div>
                    <div class="card-body">
                        <form id="emailForm" action="<?= base_url('admin/pending-workers/send-email/' . (int) ($worker['id'] ?? 0)) ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label for="emailMessage" class="form-label">Message</label>
                                <textarea id="emailMessage" name="email_message" class="form-control" rows="6" placeholder="Enter your message to the worker..." required></textarea>
                            </div>
                            <button type="submit" id="sendEmailButton" class="btn btn-primary w-100">
                                <span id="sendSpinner" style="display:none" class="btn-spinner" aria-hidden="true"></span>
                                <i id="sendIcon" class="fas fa-paper-plane"></i>
                                <span id="sendText"> Send Email</span>
                            </button>
                            <div id="emailLastSent" class="text-muted small mt-2" style="display:none">Last sent: <span id="emailLastSentTime"></span></div>
                        </form>
                        <div id="emailStatus" style="display: none;" class="mt-3"></div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-check-double"></i> Actions
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="text-muted small mb-3">Choose an action to proceed with this application:</p>
                        </div>

                        <form action="/admin/workers/approve/<?= (int) ($worker['id'] ?? 0) ?>" method="post" class="mb-2">
                            <?= csrf_field() ?>
                            <button type="button" class="btn btn-success w-100 confirm-action" data-action="approve" data-worker-name="<?= esc($worker['first_name'] . ' ' . $worker['last_name']) ?>">
                                <i class="fas fa-check-circle"></i> Approve Application
                            </button>
                        </form>

                        <form action="/admin/workers/reject/<?= (int) ($worker['id'] ?? 0) ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="button" class="btn btn-danger w-100 confirm-action" data-action="reject" data-worker-name="<?= esc($worker['first_name'] . ' ' . $worker['last_name']) ?>">
                                <i class="fas fa-times-circle"></i> Reject Application
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="fas fa-info-circle"></i> Information</h6>
                        <ul class="list-unstyled text-muted small mb-0">
                            <li><i class="fas fa-circle-check text-success"></i> <strong>Approve:</strong> Worker gains access to platform</li>
                            <li><i class="fas fa-circle-xmark text-danger"></i> <strong>Reject:</strong> Worker will be notified and can reapply</li>
                            <li><i class="fas fa-envelope"></i> <strong>Email:</strong> Send custom message before action</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($resumePath !== ''): ?>
    <div class="modal fade" id="resumePreviewModal" tabindex="-1" aria-labelledby="resumePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resumePreviewModalLabel">
                        <i class="fas fa-file-lines me-2"></i> Resume Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9 bg-light rounded overflow-hidden border">
                        <iframe id="resumePreviewFrame" src="<?= esc($latestResumePreviewUrl) ?>" title="Resume preview" style="border:0;"></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="resumeDownloadLink" href="<?= esc($latestResumeDownloadUrl) ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function formatNumber(value) {
        return new Intl.NumberFormat('en-US').format(value);
    }

    const resumePreviewFrame = document.getElementById('resumePreviewFrame');
    const resumeDownloadLink = document.getElementById('resumeDownloadLink');
    document.querySelectorAll('.resume-preview-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            if (resumePreviewFrame) {
                resumePreviewFrame.src = this.dataset.resumePreviewUrl || resumePreviewFrame.src;
            }
            if (resumeDownloadLink) {
                resumeDownloadLink.href = this.dataset.resumeDownloadUrl || resumeDownloadLink.href;
            }
            const modalTitle = document.getElementById('resumePreviewModalLabel');
            if (modalTitle) {
                const fileName = this.dataset.resumeFileName || 'Resume Preview';
                modalTitle.innerHTML = '<i class="fas fa-file-lines me-2"></i> ' + fileName;
            }
        });
    });

    const emailForm = document.getElementById('emailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const sendButton = document.getElementById('sendEmailButton');
            const statusBox = document.getElementById('emailStatus');

            if (sendButton) {
                sendButton.disabled = true;
            }

            if (statusBox) {
                statusBox.style.display = 'block';
                statusBox.className = 'mt-3 alert alert-info';
                statusBox.textContent = 'Sending email...';
            }

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Failed to send email.');
                }

                this.reset();

                if (statusBox) {
                    statusBox.className = 'mt-3 alert alert-success';
                    statusBox.textContent = result.message || 'Email sent successfully.';
                }
            } catch (error) {
                if (statusBox) {
                    statusBox.className = 'mt-3 alert alert-danger';
                    statusBox.textContent = error.message || 'Failed to send email.';
                }
            } finally {
                if (sendButton) {
                    sendButton.disabled = false;
                }
            }
        });
    }
</script>

<?= view('layouts/page_footer') ?>
