<?= view('layouts/page_header', ['pageTitle' => 'Worker Profile']) ?>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-user-tie"></i> Worker Profile</h3>
        <a href="<?= base_url('customer/services') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Services
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:88px;height:88px;font-size:2rem;">
                        <?= esc(strtoupper(substr((string) ($worker['first_name'] ?? 'W'), 0, 1))) ?>
                    </div>
                    <h4 class="mb-1"><?= esc(trim(($worker['first_name'] ?? '') . ' ' . ($worker['last_name'] ?? ''))) ?></h4>
                    <div class="text-muted mb-3">Worker ID: <?= esc((string) ($worker['id'] ?? '-')) ?></div>
                    <div class="mb-3">
                        <span class="badge bg-success px-3 py-2">Active Worker</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#workerBookingModal"
                                data-worker-id="<?= esc((string) ($worker['id'] ?? 0)) ?>"
                                data-worker-name="<?= esc(trim(($worker['first_name'] ?? '') . ' ' . ($worker['last_name'] ?? ''))) ?>">
                            <i class="fas fa-calendar-plus"></i> Book This Worker
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <i class="fas fa-id-card"></i> Basic Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Email:</strong> <?= esc($worker['email'] ?? '-') ?></div>
                        <div class="col-md-6"><strong>Phone:</strong> <?= esc($worker['phone'] ?? '-') ?></div>
                        <div class="col-12"><strong>Address:</strong> <?= esc($worker['address'] ?? '-') ?></div>
                        <div class="col-md-6"><strong>Experience:</strong> <?= esc((string) ($worker['experience_years'] ?? 0)) ?> years</div>
                        <div class="col-md-6"><strong>Commission Rate:</strong> <?= esc((string) ($worker['commission_rate'] ?? 0)) ?>%</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <i class="fas fa-tools"></i> Skills
                </div>
                <div class="card-body">
                    <?php if (!empty($skills)): ?>
                        <?php foreach ($skills as $skill): ?>
                            <span class="badge bg-secondary me-1 mb-1"><?= esc($skill) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">No skills listed.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <i class="fas fa-star"></i> Rating Summary
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Average Rating</div>
                                <div class="fs-3 fw-bold"><?= number_format((float) ($averageRating['average_rating'] ?? 0), 1) ?>/5</div>
                                <div class="text-muted small"><?= (int) ($averageRating['total_reviews'] ?? 0) ?> reviews</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="border rounded p-3 h-100">
                                <div class="row g-2">
                                    <div class="col-4"><div class="text-muted small">Service</div><strong><?= number_format((float) ($detailedRatings['service_quality'] ?? 0), 1) ?>/5</strong></div>
                                    <div class="col-4"><div class="text-muted small">Timeliness</div><strong><?= number_format((float) ($detailedRatings['timeliness'] ?? 0), 1) ?>/5</strong></div>
                                    <div class="col-4"><div class="text-muted small">Professionalism</div><strong><?= number_format((float) ($detailedRatings['professionalism'] ?? 0), 1) ?>/5</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-comments"></i> Recent Reviews
                </div>
                <div class="card-body">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong><?= esc(($review['customer_first_name'] ?? 'Customer') . ' ' . ($review['customer_last_name'] ?? '')) ?></strong>
                                        <div class="text-muted small"><?= esc($review['service_name'] ?? 'Service') ?></div>
                                    </div>
                                    <div class="text-warning fw-bold"><?= (int) ($review['rating'] ?? 0) ?>/5</div>
                                </div>
                                <p class="mb-0 text-muted"><?= esc($review['comment'] ?? 'No comment provided.') ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-star-half-alt fa-2x mb-2 opacity-25"></i>
                            <p class="mb-0">No published reviews yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="workerBookingModal" tabindex="-1" aria-labelledby="workerBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('bookings/create') ?>" method="POST" id="workerBookingForm">
                <?= csrf_field() ?>
                <input type="hidden" name="worker_id" id="modal_worker_id">
                <input type="hidden" name="service_id" id="modal_service_id">

                <div class="modal-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                    <h5 class="modal-title" id="workerBookingModalLabel">
                        <i class="fas fa-calendar-plus"></i> Book <span id="modal_worker_name"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Worker:</strong> <span id="modal_worker_name_display"></span></p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="service_select" class="form-label">Select Service <span class="text-danger">*</span></label>
                            <select class="form-select" id="service_select" name="service_id" required>
                                <option value="">-- Choose a service this worker provides --</option>
                            </select>
                            <small class="text-muted">Only services matching the worker's skills are shown</small>
                        </div>

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
                                <button type="button" class="btn btn-sm btn-outline-primary js-use-location-worker">
                                    <i class="fas fa-location-crosshairs"></i> Use My Current Location
                                </button>
                                <a href="#" class="btn btn-sm btn-outline-secondary d-none js-location-preview-worker" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-map-location-dot"></i> Preview on Map
                                </a>
                            </div>
                            <textarea class="form-control" id="location_address_worker" name="location_address" rows="2"
                                      placeholder="Enter complete address where service will be performed"
                                      required minlength="5"><?= esc(old('location_address', $user['address'] ?? '')) ?></textarea>
                            <input type="hidden" id="latitude_worker" name="latitude" value="<?= esc(old('latitude')) ?>">
                            <input type="hidden" id="longitude_worker" name="longitude" value="<?= esc(old('longitude')) ?>">
                            <small class="text-muted d-block mt-2 js-location-status-worker">Use your saved address or turn on location so we can help workers find you faster.</small>
                            <div class="mt-3">
                                <input type="text" class="form-control mb-2 js-map-search-worker" placeholder="Search for a place or landmark on the map">
                                <div class="border rounded overflow-hidden js-map-wrapper-worker" style="height: 280px; background: #f8fafc;">
                                    <div id="worker_booking_location_map" style="height: 100%; width: 100%;"></div>
                                </div>
                                <small class="text-muted d-block mt-2">Drag the marker on the map to pinpoint the exact service location.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="scheduled_date_worker" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="scheduled_date_worker" name="scheduled_date"
                                   required min="<?= date('Y-m-d') ?>" value="<?= old('scheduled_date') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="scheduled_time_worker" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="scheduled_time_worker" name="scheduled_time"
                                   required value="<?= old('scheduled_time', '09:00') ?>">
                        </div>

                        <div class="col-md-12">
                            <label for="priority_worker" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" id="priority_worker" name="priority" required>
                                <option value="low" <?= old('priority') === 'low' ? 'selected' : '' ?>>Low - Can wait a few days</option>
                                <option value="medium" <?= old('priority', 'medium') === 'medium' ? 'selected' : '' ?>>Medium - Within this week</option>
                                <option value="high" <?= old('priority') === 'high' ? 'selected' : '' ?>>High - Need it soon</option>
                                <option value="urgent" <?= old('priority') === 'urgent' ? 'selected' : '' ?>>Urgent - Emergency</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label for="notes_worker" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes_worker" name="notes" rows="2"
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
        const workerBookingModal = document.getElementById('workerBookingModal');
        const workerBookingForm = document.getElementById('workerBookingForm');
        const serviceSelect = document.getElementById('service_select');
        const locationTextarea = document.getElementById('location_address_worker');
        const latitudeInput = document.getElementById('latitude_worker');
        const longitudeInput = document.getElementById('longitude_worker');
        const previewLinks = document.querySelectorAll('.js-location-preview-worker');
        const locationStatus = document.querySelector('.js-location-status-worker');
        const searchInput = document.querySelector('.js-map-search-worker');
        const mapContainer = document.getElementById('worker_booking_location_map');
        const defaultAddress = <?= json_encode(old('location_address', $user['address'] ?? '')) ?>;
        const workerSkills = <?= json_encode(\App\Models\UserModel::normalizeWorkerSkills($worker['skills'] ?? [])) ?>;
        let map;
        let marker;
        let mapInitialized = false;
        let suppressAddressSync = false;
        let searchDebounceId = null;

        // Get services matching worker skills
        const allServices = <?= json_encode($all_services ?? []) ?>;
        const SERVICE_CATEGORY_SKILL_MAP = <?= json_encode(\App\Models\UserModel::SERVICE_CATEGORY_SKILL_MAP) ?>;
        
        function populateServiceOptions() {
            serviceSelect.innerHTML = '<option value="">-- Choose a service this worker provides --</option>';
            
            allServices.forEach(service => {
                const requiredSkills = SERVICE_CATEGORY_SKILL_MAP[service.category] || [];
                const hasSkills = requiredSkills.length > 0 && requiredSkills.some(skill => workerSkills.includes(skill));
                
                if (hasSkills || requiredSkills.length === 0) {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = service.name + ' - ₱' + parseFloat(service.base_price).toFixed(2);
                    serviceSelect.appendChild(option);
                }
            });
        }

        function setLocationStatus(message, tone) {
            if (!locationStatus) return;
            locationStatus.className = 'text-muted d-block mt-2 js-location-status-worker';
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
            if (!marker) return;
            const latLng = [Number(latitude), Number(longitude)];
            marker.setLatLng(latLng);
            map?.setView(latLng, 17);
        }

        async function reverseGeocode(latitude, longitude) {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`);
            if (!response.ok) throw new Error('Reverse geocoding failed.');
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
            if (!response.ok) throw new Error('Address search failed.');
            const data = await response.json();
            if (!Array.isArray(data) || data.length === 0) throw new Error('No matching location found.');
            return data[0];
        }

        function initializeLeafletMap() {
            if (mapInitialized || typeof L === 'undefined' || !mapContainer) return;
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
            marker = L.marker(initialCenter, { draggable: true }).addTo(map);
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
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        }

        document.querySelectorAll('.js-use-location-worker').forEach((button) => {
            button.addEventListener('click', captureCurrentLocation);
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceId);
            const query = searchInput.value.trim();
            if (query.length < 4) return;
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
            if (suppressAddressSync) return;
            updateMapPreview(latitudeInput.value, longitudeInput.value, locationTextarea.value.trim());
        });

        workerBookingModal.addEventListener('show.bs.modal', function(event) {
            initializeLeafletMap();
            const button = event.relatedTarget;
            const workerId = button.getAttribute('data-worker-id');
            const workerName = button.getAttribute('data-worker-name');

            document.getElementById('modal_worker_id').value = workerId;
            document.getElementById('modal_worker_name').textContent = workerName;
            document.getElementById('modal_worker_name_display').textContent = workerName;

            populateServiceOptions();
            updateMapPreview(latitudeInput.value, longitudeInput.value, locationTextarea.value.trim());
            if (mapInitialized) {
                setTimeout(function() {
                    map.invalidateSize();
                    updateMarkerPosition(latitudeInput.value || 14.5995124, longitudeInput.value || 120.9842195);
                }, 120);
            }
        });

        workerBookingModal.addEventListener('hidden.bs.modal', function() {
            workerBookingForm.reset();
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

