<?= view('layouts/page_header', ['pageTitle' => 'Booking Details']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-calendar-check"></i> Booking Details</h3>
                    <a href="<?= base_url('customer/bookings') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Booking Info Card -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <h5 class="mb-0"><i class="fas fa-book"></i> Booking Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Booking Reference:</strong></p>
                                <p class="mb-3"><?= esc($booking['booking_reference'] ?? '-') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Status:</strong></p>
                                <p class="mb-3">
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-info',
                                        'in_progress' => 'bg-primary',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $status = $booking['status'] ?? 'pending';
                                    ?>
                                    <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>"><?= esc(ucfirst(str_replace('_', ' ', $status))) ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Service:</strong></p>
                                <p class="mb-3">
                                    <?= esc($booking['service_name'] ?? '-') ?>
                                    <?php if (!empty($booking['service_category'])): ?>
                                        <br><small class="text-muted"><?= esc($booking['service_category']) ?></small>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Assigned Worker:</strong></p>
                                <p class="mb-3">
                                    <?php if ($booking['worker_id']): ?>
                                        <a href="<?= base_url('worker/profile/' . $booking['worker_id']) ?>" class="text-decoration-none">
                                            <?= esc(trim(($booking['worker_first_name'] ?? '') . ' ' . ($booking['worker_last_name'] ?? ''))) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned yet</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Title:</strong></p>
                                <p class="mb-3"><?= esc($booking['title'] ?? '-') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Priority:</strong></p>
                                <p class="mb-3">
                                    <?php 
                                    $priorityClass = [
                                        'low' => 'bg-success',
                                        'medium' => 'bg-info',
                                        'high' => 'bg-warning',
                                        'urgent' => 'bg-danger'
                                    ];
                                    $priority = strtolower($booking['priority'] ?? 'medium');
                                    ?>
                                    <span class="badge <?= $priorityClass[$priority] ?? 'bg-secondary' ?>"><?= esc(ucfirst($priority)) ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p class="text-muted mb-1"><strong>Description:</strong></p>
                            <p class="mb-0"><?= esc($booking['description'] ?? 'No description provided') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Location Card -->
                <div class="card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Location</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-1"><strong>Address:</strong></p>
                        <p class="mb-3"><?= esc($booking['location_address'] ?? '-') ?></p>

                        <?php if (!empty($booking['latitude']) && !empty($booking['longitude'])): ?>
                            <div id="map" style="height: 300px; border-radius: 8px; margin-top: 15px;"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Schedule Card -->
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Scheduled Date & Time</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Date:</strong></p>
                                <p class="mb-3">
                                    <?php if (!empty($booking['scheduled_date'])): ?>
                                        <?= date('F d, Y', strtotime($booking['scheduled_date'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1"><strong>Time:</strong></p>
                                <p class="mb-3">
                                    <?php if (!empty($booking['scheduled_time'])): ?>
                                        <?= date('h:i A', strtotime('2000-01-01 ' . $booking['scheduled_time'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <h5 class="mb-0"><i class="fas fa-receipt"></i> Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Created:</span>
                                <span><?= date('M d, Y', strtotime($booking['created_at'] ?? 'now')) ?></span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Total Fee:</span>
                                <span class="h5 mb-0" style="color: #2a5298;"><strong>₱<?= number_format((float)($booking['total_fee'] ?? 0), 2) ?></strong></span>
                            </div>
                        </div>

                        <hr>

                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <?php if (($booking['status'] ?? '') !== 'completed' && ($booking['status'] ?? '') !== 'cancelled'): ?>
                                <button type="button" class="btn btn-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                            <?php elseif (($booking['status'] ?? '') === 'completed' && empty($booking['review_id'])): ?>
                                <a href="<?= base_url('customer/reviews/create/' . ($booking['id'] ?? '')) ?>" class="btn btn-warning btn-sm w-100">
                                    <i class="fas fa-star"></i> Leave a Review
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('bookings/cancel/' . ($booking['id'] ?? '')) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this booking?</p>
                        <p class="text-muted small">Reference: <?= esc($booking['booking_reference'] ?? '-') ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep it</button>
                        <button type="submit" class="btn btn-danger">Yes, Cancel Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($booking['latitude']) && !empty($booking['longitude'])): ?>
                const map = L.map('map').setView([<?= $booking['latitude'] ?>, <?= $booking['longitude'] ?>], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                L.marker([<?= $booking['latitude'] ?>, <?= $booking['longitude'] ?>])
                    .bindPopup('<?= esc($booking['location_address'] ?? 'Location') ?>')
                    .addTo(map);
            <?php endif; ?>
        });
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<?= view('layouts/page_footer') ?>
