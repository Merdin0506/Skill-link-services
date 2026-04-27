<?php
$customCss = null;
?>
<?= view('layouts/page_header', ['pageTitle' => 'Service Details']) ?>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-clipboard-list"></i> Service Details</h3>
        <a href="<?= base_url('customer/services') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Services
        </a>
    </div>
    <style>
        .leaflet-container {
            font-family: inherit;
        }
    </style>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-tools"></i> <?= esc($service['name'] ?? 'Service') ?></span>
                    <span class="badge bg-primary"><?= esc(ucfirst($service['category'] ?? 'general')) ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4"><?= esc($service['description'] ?? 'No description available.') ?></p>

                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fas fa-peso-sign"></i> Base Price</span>
                        <strong class="text-primary fs-5">₱<?= number_format((float) ($service['base_price'] ?? 0), 2) ?></strong>
                    </div>

                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fas fa-clock"></i> Estimated Duration</span>
                        <span><?= esc($service['estimated_duration'] ?? 0) ?> mins</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Average Rating</span>
                        <strong><?= number_format((float) ($reviewStats['average_rating'] ?? 0), 1) ?>/5</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Total Reviews</span>
                        <strong><?= (int) ($reviewStats['total_reviews'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted">Total Bookings</span>
                        <strong><?= (int) ($reviewStats['total_bookings'] ?? 0) ?></strong>
                    </div>

                    <button class="btn btn-primary w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#bookingModal"
                            data-service-id="<?= esc($service['id']) ?>"
                            data-service-name="<?= esc($service['name']) ?>"
                            data-service-price="<?= esc($service['base_price']) ?>"
                            data-service-duration="<?= esc($service['estimated_duration']) ?>">
                        <i class="fas fa-calendar-plus"></i> Book This Service
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-star"></i> Customer Reviews
                </div>
                <div class="card-body">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?= esc(($review['customer_first_name'] ?? 'Customer') . ' ' . ($review['customer_last_name'] ?? '')) ?></h6>
                                        <small class="text-muted">
                                            Worker: <?= esc(($review['worker_first_name'] ?? 'N/A') . ' ' . ($review['worker_last_name'] ?? '')) ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'] ?? date('Y-m-d'))) ?></small>
                                </div>

                                <div class="mb-2 text-warning">
                                    <?php $rating = (int) ($review['rating'] ?? 0); ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $rating ? 'fas fa-star' : 'far fa-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-dark ms-2"><?= $rating ?>/5</span>
                                </div>

                                <p class="mb-0 text-muted"><?= esc($review['comment'] ?? 'No comment provided.') ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0">No reviews for this service yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <div class="alert alert-info">
                        <h6 class="mb-2"><strong>Service:</strong> <span id="modal_service_name"></span></h6>
                        <p class="mb-1"><strong>Base Price:</strong> ₱<span id="modal_service_price"></span></p>
                        <p class="mb-0"><strong>Estimated Duration:</strong> <span id="modal_service_duration"></span> mins</p>
                    </div>

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
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary js-use-location">
                                    <i class="fas fa-location-crosshairs"></i> Use My Current Location
                                </button>
                                <a href="#" class="btn btn-sm btn-outline-secondary d-none js-location-preview" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-map-location-dot"></i> Preview on Map
                                </a>
                            </div>
                            <textarea class="form-control" id="location_address" name="location_address" rows="2"
                                      placeholder="Enter complete address where service will be performed"
                                      required minlength="5"><?= esc(old('location_address', $user['address'] ?? '')) ?></textarea>
                            <input type="hidden" id="latitude" name="latitude" value="<?= esc(old('latitude')) ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?= esc(old('longitude')) ?>">
                            <small class="text-muted d-block mt-2 js-location-status">Use your saved address or turn on location so we can help workers find you faster.</small>
                            <div class="mt-3">
                                <input type="text" class="form-control mb-2 js-map-search" placeholder="Search for a place or landmark on the map">
                                <div class="border rounded overflow-hidden js-map-wrapper" style="height: 280px; background: #f8fafc;">
                                    <div id="booking_location_map" style="height: 100%; width: 100%;"></div>
                                </div>
                                <small class="text-muted d-block mt-2">Drag the marker on the map to pinpoint the exact service location.</small>
                            </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const bookingModal = document.getElementById('bookingModal');
        const bookingForm = document.getElementById('bookingForm');
        const locationTextarea = document.getElementById('location_address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const previewLinks = document.querySelectorAll('.js-location-preview');
        const locationStatus = document.querySelector('.js-location-status');
        const searchInput = document.querySelector('.js-map-search');
        const mapContainer = document.getElementById('booking_location_map');
        const defaultAddress = <?= json_encode(old('location_address', $user['address'] ?? '')) ?>;
        let map;
        let marker;
        let mapInitialized = false;
        let suppressAddressSync = false;
        let searchDebounceId = null;

        function setLocationStatus(message, tone) {
            if (!locationStatus) {
                return;
            }

            locationStatus.className = 'text-muted d-block mt-2 js-location-status';
            if (tone === 'error') {
                locationStatus.classList.add('text-danger');
            } else if (tone === 'success') {
                locationStatus.classList.add('text-success');
            }
            locationStatus.textContent = message;
        }

        function updateMapPreview(latitude, longitude, address) {
            const query = latitude && longitude ? `${latitude},${longitude}` : address;
            const url = query ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}` : '';

            previewLinks.forEach((link) => {
                if (!url) {
                    link.classList.add('d-none');
                    link.removeAttribute('href');
                    return;
                }

                link.href = url;
                link.classList.remove('d-none');
            });
        }

        function setCoordinates(latitude, longitude) {
            latitudeInput.value = latitude;
            longitudeInput.value = longitude;
            updateMapPreview(latitude, longitude, locationTextarea.value.trim());
        }

        function updateMarkerPosition(latitude, longitude) {
            if (!marker) {
                return;
            }

            const latLng = [Number(latitude), Number(longitude)];
            marker.setLatLng(latLng);
            map?.setView(latLng, 17);
        }

        async function reverseGeocode(latitude, longitude) {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`);
            if (!response.ok) {
                throw new Error('Reverse geocoding failed.');
            }

            const data = await response.json();
            return data.display_name || `Pinned location near ${latitude}, ${longitude}`;
        }

        async function applyCoordinates(latitude, longitude, preferExistingAddress = false) {
            setCoordinates(latitude, longitude);
            updateMarkerPosition(latitude, longitude);

            if (preferExistingAddress && locationTextarea.value.trim()) {
                updateMapPreview(latitude, longitude, locationTextarea.value.trim());
                return;
            }

            try {
                suppressAddressSync = true;
                locationTextarea.value = await reverseGeocode(latitude, longitude);
                updateMapPreview(latitude, longitude, locationTextarea.value.trim());
            } catch (error) {
                suppressAddressSync = true;
                locationTextarea.value = `Pinned location near ${latitude}, ${longitude}`;
                updateMapPreview(latitude, longitude, locationTextarea.value.trim());
            } finally {
                suppressAddressSync = false;
            }
        }

        async function geocodeAddress(address) {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(address)}`);
            if (!response.ok) {
                throw new Error('Address search failed.');
            }

            const data = await response.json();
            if (!Array.isArray(data) || data.length === 0) {
                throw new Error('No matching location found.');
            }

            return data[0];
        }

        function initializeLeafletMap() {
            if (mapInitialized || typeof L === 'undefined' || !mapContainer) {
                return;
            }

            const initialLat = Number(latitudeInput.value || 14.5995124);
            const initialLng = Number(longitudeInput.value || 120.9842195);
            const initialCenter = [initialLat, initialLng];

            map = L.map(mapContainer, {
                center: initialCenter,
                zoom: latitudeInput.value && longitudeInput.value ? 17 : 13,
                zoomControl: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            marker = L.marker(initialCenter, {
                draggable: true
            }).addTo(map);

            marker.on('dragend', async function(event) {
                const position = event.target.getLatLng();
                const latitude = position.lat.toFixed(8);
                const longitude = position.lng.toFixed(8);
                await applyCoordinates(latitude, longitude);
                setLocationStatus('Map pin updated. Please confirm the location before submitting.', 'success');
            });

            map.on('click', async function(event) {
                const latitude = event.latlng.lat.toFixed(8);
                const longitude = event.latlng.lng.toFixed(8);
                await applyCoordinates(latitude, longitude);
                setLocationStatus('Map pin updated. Please confirm the location before submitting.', 'success');
            });

            mapInitialized = true;
            updateMapPreview(latitudeInput.value, longitudeInput.value, locationTextarea.value.trim());
        }

        function captureCurrentLocation() {
            if (!navigator.geolocation) {
                setLocationStatus('This browser does not support location access. Please enter your address manually.', 'error');
                return;
            }

            setLocationStatus('Getting your current location...', null);

            navigator.geolocation.getCurrentPosition(async (position) => {
                const latitude = position.coords.latitude.toFixed(8);
                const longitude = position.coords.longitude.toFixed(8);
                await applyCoordinates(latitude, longitude);
                setLocationStatus('Current location captured. Please review the address before submitting.', 'success');
            }, () => {
                setLocationStatus('Location access was denied. Please type your address manually or allow location access.', 'error');
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        }

        document.querySelectorAll('.js-use-location').forEach((button) => {
            button.addEventListener('click', captureCurrentLocation);
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceId);

            const query = searchInput.value.trim();
            if (query.length < 4) {
                return;
            }

            searchDebounceId = window.setTimeout(async function() {
                try {
                    const result = await geocodeAddress(query);
                    suppressAddressSync = true;
                    locationTextarea.value = result.display_name || query;
                    suppressAddressSync = false;
                    await applyCoordinates(Number(result.lat).toFixed(8), Number(result.lon).toFixed(8), true);
                    setLocationStatus('Map updated from your search result.', 'success');
                } catch (error) {
                    setLocationStatus('We could not find that location. Try a more complete address or landmark.', 'error');
                }
            }, 500);
        });

        locationTextarea.addEventListener('input', function() {
            if (suppressAddressSync) {
                return;
            }

            updateMapPreview(latitudeInput.value, longitudeInput.value, locationTextarea.value.trim());
        });

        bookingModal.addEventListener('show.bs.modal', function(event) {
            initializeLeafletMap();
            const button = event.relatedTarget;
            const serviceId = button.getAttribute('data-service-id');
            const serviceName = button.getAttribute('data-service-name');
            const servicePrice = button.getAttribute('data-service-price');
            const serviceDuration = button.getAttribute('data-service-duration');

            document.getElementById('modal_service_id').value = serviceId;
            document.getElementById('modal_service_name').textContent = serviceName;
            document.getElementById('modal_service_price').textContent = parseFloat(servicePrice).toFixed(2);
            document.getElementById('modal_service_duration').textContent = serviceDuration;

            const titleInput = document.getElementById('title');
            if (!titleInput.value) {
                titleInput.value = serviceName;
            }

            updateMapPreview(latitudeInput.value, longitudeInput.value, locationTextarea.value.trim());
            if (mapInitialized) {
                setTimeout(function() {
                    map.invalidateSize();
                    updateMarkerPosition(latitudeInput.value || 14.5995124, longitudeInput.value || 120.9842195);
                }, 120);
            }
        });

        bookingModal.addEventListener('hidden.bs.modal', function() {
            bookingForm.reset();
            latitudeInput.value = '';
            longitudeInput.value = '';
            locationTextarea.value = defaultAddress;
            searchInput.value = '';
            setLocationStatus('Use your saved address or turn on location so we can help workers find you faster.', null);
            updateMapPreview('', '', locationTextarea.value.trim());
            if (mapInitialized) {
                updateMarkerPosition(14.5995124, 120.9842195);
                map.setView([14.5995124, 120.9842195], 13);
            }
        });

        updateMapPreview(latitudeInput.value, longitudeInput.value, locationTextarea.value.trim());
    });
</script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<?= view('layouts/page_footer') ?>
