<?= view('layouts/page_header', ['pageTitle' => 'Services']) ?>

<?php
$currentFilters = $filters ?? ['status' => 'active', 'category' => ''];
$selectedServiceValue = $selectedService ?? null;
?>

<div class="page-content">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0"><i class="fas fa-list"></i> Services</h3>
            <p class="text-muted mb-0">Manage the service catalog using the same create, edit, and deactivate flow available on Desktop.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Visible Services</div>
                <h2 class="mb-0"><?= esc((string) ($serviceStats['visible_services'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="text-muted small">Categories</div>
                <h2 class="mb-0"><?= esc((string) ($serviceStats['categories'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="text-muted small">Active Services</div>
                <h2 class="mb-0"><?= esc((string) ($serviceStats['active_services'] ?? 0)) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="text-muted small">Inactive Services</div>
                <h2 class="mb-0"><?= esc((string) ($serviceStats['inactive_services'] ?? 0)) ?></h2>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> Service Filters
        </div>
        <div class="card-body">
            <form method="get" action="<?= base_url('admin/services') ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="serviceStatusFilter" class="form-label">Status</label>
                        <select id="serviceStatusFilter" name="status" class="form-select">
                            <?php foreach (['active', 'inactive'] as $statusOption): ?>
                                <option value="<?= esc($statusOption) ?>" <?= ($currentFilters['status'] ?? 'active') === $statusOption ? 'selected' : '' ?>>
                                    <?= esc($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="serviceCategoryFilter" class="form-label">Category</label>
                        <select id="serviceCategoryFilter" name="category" class="form-select">
                            <option value="">All categories</option>
                            <?php foreach (($serviceCategories ?? []) as $categoryKey => $categoryLabel): ?>
                                <option value="<?= esc($categoryKey) ?>" <?= ($currentFilters['category'] ?? '') === (string) $categoryKey ? 'selected' : '' ?>>
                                    <?= esc($categoryLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                        <a href="<?= base_url('admin/services') ?>" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-rectangle-list"></i> Service Catalog</span>
                    <a href="<?= base_url('admin/services?' . http_build_query(array_filter(['status' => $currentFilters['status'] ?? 'active', 'category' => $currentFilters['category'] ?? '']))) ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Service
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Base Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                        <?php
                                        $query = array_filter([
                                            'status' => $currentFilters['status'] ?? 'active',
                                            'category' => $currentFilters['category'] ?? '',
                                            'service_id' => $service['id'] ?? null,
                                        ], static fn ($value) => $value !== null && $value !== '');
                                        $isSelected = (int) ($selectedServiceValue['id'] ?? 0) === (int) ($service['id'] ?? 0);
                                        ?>
                                        <tr class="<?= $isSelected ? 'table-active' : '' ?>" onclick="window.location.href='<?= base_url('admin/services?' . http_build_query($query)) ?>'" style="cursor:pointer;">
                                            <td>
                                                <strong><?= esc($service['name'] ?? 'Service') ?></strong>
                                                <div class="text-muted small"><?= esc($service['description'] ?? 'No description provided.') ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= esc($service['category'] ?? 'general') ?></span></td>
                                            <td><span class="badge bg-<?= ($service['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>"><?= esc($service['status'] ?? 'active') ?></span></td>
                                            <td><?= formatCurrency((float) ($service['base_price'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No services match the current admin filters.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-screwdriver-wrench"></i> <?= !empty($selectedServiceValue) ? 'Edit Service' : 'Create Service' ?>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= !empty($selectedServiceValue) ? base_url('admin/services/update/' . (int) ($selectedServiceValue['id'] ?? 0)) : base_url('admin/services/store') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect_status" value="<?= esc($currentFilters['status'] ?? 'active') ?>">
                        <input type="hidden" name="redirect_category" value="<?= esc($currentFilters['category'] ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="serviceName" class="form-label">Service Name</label>
                                <input id="serviceName" name="name" type="text" class="form-control" required value="<?= esc(old('name', $selectedServiceValue['name'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="serviceCategory" class="form-label">Category</label>
                                <select id="serviceCategory" name="category" class="form-select" required>
                                    <?php foreach (($serviceCategories ?? []) as $categoryKey => $categoryLabel): ?>
                                        <option value="<?= esc($categoryKey) ?>" <?= old('category', $selectedServiceValue['category'] ?? '') === (string) $categoryKey ? 'selected' : '' ?>>
                                            <?= esc($categoryLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="serviceDescription" class="form-label">Description</label>
                                <textarea id="serviceDescription" name="description" class="form-control" rows="4"><?= esc(old('description', $selectedServiceValue['description'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="serviceBasePrice" class="form-label">Base Price</label>
                                <input id="serviceBasePrice" name="base_price" type="number" min="1" step="0.01" class="form-control" required value="<?= esc(old('base_price', $selectedServiceValue['base_price'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="serviceDuration" class="form-label">Estimated Duration (mins)</label>
                                <input id="serviceDuration" name="estimated_duration" type="number" min="1" step="1" class="form-control" value="<?= esc(old('estimated_duration', $selectedServiceValue['estimated_duration'] ?? '')) ?>">
                            </div>
                            <?php if (!empty($selectedServiceValue)): ?>
                                <div class="col-md-6">
                                    <label for="serviceStatus" class="form-label">Status</label>
                                    <select id="serviceStatus" name="status" class="form-select">
                                        <?php foreach (['active', 'inactive'] as $statusOption): ?>
                                            <option value="<?= esc($statusOption) ?>" <?= old('status', $selectedServiceValue['status'] ?? 'active') === $statusOption ? 'selected' : '' ?>>
                                                <?= esc($statusOption) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= !empty($selectedServiceValue) ? 'Save Service' : 'Create Service' ?>
                            </button>
                            <?php if (!empty($selectedServiceValue) && ($selectedServiceValue['status'] ?? 'active') === 'active'): ?>
                                <button type="submit" class="btn btn-outline-danger" formaction="<?= base_url('admin/services/deactivate/' . (int) ($selectedServiceValue['id'] ?? 0)) ?>" formmethod="post" formnovalidate data-confirm-title="Deactivate service?" data-confirm-message="This service will be hidden from active listings but kept in the catalog." data-confirm-label="Deactivate" data-confirm-class="btn-danger">
                                    <i class="fas fa-toggle-off"></i> Deactivate Service
                                </button>
                            <?php endif; ?>
                            <a href="<?= base_url('admin/services?' . http_build_query(array_filter(['status' => $currentFilters['status'] ?? 'active', 'category' => $currentFilters['category'] ?? '']))) ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-plus"></i> New Service
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($selectedServiceValue)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-eye"></i> Service Preview
                    </div>
                    <div class="card-body">
                        <h5 class="mb-2"><?= esc($selectedServiceValue['name'] ?? 'Service') ?></h5>
                        <p class="text-muted mb-3"><?= esc($selectedServiceValue['description'] ?? 'No description provided.') ?></p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Category</div>
                                    <strong><?= esc($selectedServiceValue['category'] ?? 'general') ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Status</div>
                                    <strong><?= esc($selectedServiceValue['status'] ?? 'active') ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Base Price</div>
                                    <strong><?= formatCurrency((float) ($selectedServiceValue['base_price'] ?? 0)) ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Duration</div>
                                    <strong><?= esc((string) ($selectedServiceValue['estimated_duration'] ?? '-')) ?> mins</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= view('layouts/page_footer') ?>
