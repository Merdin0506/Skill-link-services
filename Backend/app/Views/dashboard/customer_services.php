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
                cursor: pointer;
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
                        <div class="card service-card h-100 shadow-sm position-relative"
                             onclick="window.location.href='<?= base_url('customer/services/' . $service['id']) ?>'"
                             onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='<?= base_url('customer/services/' . $service['id']) ?>'; }"
                             role="button"
                             tabindex="0"
                             aria-label="View details for <?= esc($service['name']) ?>">
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
                                <div class="d-flex gap-2">
                                    <a href="<?= base_url('customer/services/' . $service['id']) ?>" class="btn btn-outline-secondary w-100" onclick="event.stopPropagation();">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                    <button class="btn btn-primary w-100"
                                            onclick="event.stopPropagation();"
                                            data-bs-toggle="modal"
                                            data-bs-target="#bookingModal"
                                            data-service-id="<?= esc($service['id']) ?>"
                                            data-service-name="<?= esc($service['name']) ?>"
                                            data-service-price="<?= esc($service['base_price']) ?>"
                                            data-service-duration="<?= esc($service['estimated_duration']) ?>">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </button>
                                </div>
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

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?= base_url('bookings/create') ?>" method="POST" id="bookingForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="service_id" id="modal_service_id">
                    
                    <div class="modal-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <h5 class="modal-title" id="bookingModalLabel">
                            <i class="fas fa-calendar-plus"></i> Book Service
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Service Info -->
                        <div class="alert alert-info">
                            <h6 class="mb-2"><strong>Service:</strong> <span id="modal_service_name"></span></h6>
                            <p class="mb-1"><strong>Base Price:</strong> ₱<span id="modal_service_price"></span></p>
                            <p class="mb-0"><strong>Estimated Duration:</strong> <span id="modal_service_duration"></span> mins</p>
                        </div>

                        <?php if (session()->has('error')): ?>
                            <div class="alert alert-danger">
                                <?= session('error') ?>
                            </div>
                        <?php endif; ?>

                        <!-- Booking Form -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="title" class="form-label">Service Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="e.g., Fix broken electrical socket" required
                                       value="<?= old('title') ?>" minlength="3" maxlength="255">
                                <small class="text-muted">Brief description of what you need</small>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Provide more details about the service you need..." 
                                          maxlength="1000"><?= old('description') ?></textarea>
                                <small class="text-muted">Optional: Provide additional details</small>
                            </div>

                            <div class="col-md-12">
                                <label for="location_address" class="form-label">Service Location <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="location_address" name="location_address" rows="2"
                                          placeholder="Enter complete address where service will be performed" 
                                          required minlength="5"><?= old('location_address') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" 
                                       required min="<?= date('Y-m-d') ?>" value="<?= old('scheduled_date') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" 
                                       required value="<?= old('scheduled_time', '09:00') ?>">
                            </div>

                            <div class="col-md-12">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low" <?= old('priority') === 'low' ? 'selected' : '' ?>>Low - Can wait a few days</option>
                                    <option value="medium" <?= old('priority', 'medium') === 'medium' ? 'selected' : '' ?>>Medium - Within this week</option>
                                    <option value="high" <?= old('priority') === 'high' ? 'selected' : '' ?>>High - Need it soon</option>
                                    <option value="urgent" <?= old('priority') === 'urgent' ? 'selected' : '' ?>>Urgent - Emergency</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"
                                          placeholder="Any special instructions or requirements..."><?= old('notes') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Submit Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Populate modal with service data when Book Now is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const bookingModal = document.getElementById('bookingModal');
            
            bookingModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const serviceId = button.getAttribute('data-service-id');
                const serviceName = button.getAttribute('data-service-name');
                const servicePrice = button.getAttribute('data-service-price');
                const serviceDuration = button.getAttribute('data-service-duration');
                
                // Update modal content
                document.getElementById('modal_service_id').value = serviceId;
                document.getElementById('modal_service_name').textContent = serviceName;
                document.getElementById('modal_service_price').textContent = parseFloat(servicePrice).toFixed(2);
                document.getElementById('modal_service_duration').textContent = serviceDuration;
                
                // Set default title to service name if empty
                const titleInput = document.getElementById('title');
                if (!titleInput.value) {
                    titleInput.value = serviceName;
                }
            });
            
            // Clear form when modal is hidden
            bookingModal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('bookingForm').reset();
            });
        });
    </script>

<?= view('layouts/page_footer') ?>
