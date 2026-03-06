<?php
// Setup page header with custom CSS
$customCss = null;
?>
<?= view('layouts/page_header', ['pageTitle' => 'Services']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <style>
            .service-card {
                transition: transform 0.2s;
            }
            .service-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
            }
            .category-badge {
                position: absolute;
                top: 10px;
                right: 10px;
            }
        </style>

        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-list"></i> Available Services</h3>
            </div>
        </div>

        <?php if (!empty($services)): ?>
            <div class="row g-4">
                <?php foreach ($services as $service): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card service-card h-100 shadow-sm position-relative">
                            <div class="card-body">
                                <span class="badge bg-primary category-badge"><?= esc(ucfirst($service['category'] ?? 'general')) ?></span>
                                <h5 class="card-title mt-2"><?= esc($service['name']) ?></h5>
                                <p class="card-text text-muted"><?= esc($service['description']) ?></p>
                                
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted"><i class="fas fa-peso-sign"></i> Base Price:</span>
                                        <strong class="text-primary">₱<?= number_format((float)$service['base_price'], 2) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted"><i class="fas fa-clock"></i> Duration:</span>
                                        <span><?= esc($service['estimated_duration']) ?> mins</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <button class="btn btn-primary w-100" onclick="alert('Booking feature coming soon!')">
                                    <i class="fas fa-calendar-plus"></i> Book Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No services available at the moment.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?= view('layouts/page_footer') ?>
