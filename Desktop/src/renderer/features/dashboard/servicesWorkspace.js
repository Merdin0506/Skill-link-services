import { getElementById } from '../../core/dom.js';

export function getDefaultServiceFilters(role) {
  return {
    status: role === 'admin' || role === 'super_admin' ? 'active' : 'active'
  };
}

function getCategories(state) {
  return Array.isArray(state.serviceCategories)
    ? state.serviceCategories
    : Object.keys(state.serviceCategories || {});
}

function getServices(state) {
  return Array.isArray(state.services)
    ? state.services
    : Object.values(state.services || {});
}

function createCustomerServicesView(state, helpers) {
  const { createPageHeading, formatValue, formatCurrency } = helpers;
  const categories = getCategories(state);
  const services = getServices(state);
  const selectedService = state.selectedService || null;

  return `
    ${createPageHeading('fas fa-list', 'Available Services')}
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card">
          <i class="fas fa-briefcase stat-icon" style="color: var(--primary);"></i>
          <div class="stat-value">${formatValue(services.length)}</div>
          <div class="stat-label">Visible Services</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card info">
          <i class="fas fa-layer-group stat-icon" style="color: var(--info);"></i>
          <div class="stat-value">${formatValue(categories.length)}</div>
          <div class="stat-label">Categories</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card success">
          <i class="fas fa-peso-sign stat-icon" style="color: var(--success);"></i>
          <div class="stat-value">${services.length ? formatCurrency(Math.min(...services.map((service) => Number(service.base_price || 0)))) : '-'}</div>
          <div class="stat-label">Starting Price</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card warning">
          <i class="fas fa-clock stat-icon" style="color: var(--warning);"></i>
          <div class="stat-value">${selectedService?.estimated_duration || '-'}</div>
          <div class="stat-label">Selected Duration</div>
        </div>
      </div>
    </div>

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-list"></i> Service Categories
      </div>
      <div class="card-body desktop-card-body">
        <div class="desktop-chip-row">
          <button type="button" class="ghost-button desktop-chip ${!state.serviceCategory ? 'is-active' : ''}" data-category="">
            All
          </button>
          ${categories.map((category) => `
            <button type="button" class="ghost-button desktop-chip ${state.serviceCategory === category ? 'is-active' : ''}" data-category="${category}">
              ${String(category).replace(/_/g, ' ')}
            </button>
          `).join('')}
        </div>
      </div>
    </div>

    <div class="desktop-service-grid">
      ${services.length ? services.map((service) => `
        <div class="card desktop-card desktop-service-card service-card position-relative">
          <div class="card-body desktop-card-body">
            <span class="badge bg-primary category-badge desktop-category-badge">${service.category || 'general'}</span>
            <div class="desktop-service-head">
              <div>
                <h5>${service.name || 'Service'}</h5>
              </div>
            </div>
            <p class="text-muted mb-3">${service.description || 'No description provided.'}</p>
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted"><i class="fas fa-peso-sign"></i> Base Price:</span>
                <strong class="text-primary">${formatCurrency(service.base_price ?? 0)}</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted"><i class="fas fa-clock"></i> Duration:</span>
                <span>${service.estimated_duration || '-'} mins</span>
              </div>
            </div>
            <div class="d-flex gap-2 mt-3">
              <button type="button" class="ghost-button w-100" data-service-view="${service.id}">Details</button>
              <button type="button" class="action-button w-100" data-service-book="${service.id}">Book Now</button>
            </div>
          </div>
        </div>
      `).join('') : `
        <div class="card desktop-card">
          <div class="card-body desktop-card-body">
            <div class="chart-placeholder desktop-placeholder-card">No services available for this category yet.</div>
          </div>
        </div>
      `}
    </div>

    <div class="card desktop-card mt-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-eye"></i> Service Details
      </div>
      <div class="card-body desktop-card-body">
        ${selectedService ? `
          <div class="row g-3">
            <div class="col-lg-8">
              <h5 class="mb-2">${selectedService.name || 'Service'}</h5>
              <p class="text-muted mb-3">${selectedService.description || 'No description provided.'}</p>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary">${selectedService.category || 'general'}</span>
                <span class="badge bg-secondary">${selectedService.status || 'active'}</span>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="border rounded p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="text-muted">Base Price</span>
                  <strong class="text-primary">${formatCurrency(selectedService.base_price ?? 0)}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted">Estimated Duration</span>
                  <span>${selectedService.estimated_duration || '-'} mins</span>
                </div>
              </div>
            </div>
          </div>
        ` : `
          <div class="text-center text-muted py-4">
            <i class="fas fa-list fa-2x mb-3 opacity-50"></i>
            <p class="mb-0">Choose a service to preview its details here.</p>
          </div>
        `}
      </div>
    </div>

    <div class="card desktop-card mt-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-calendar-plus"></i> Create Booking
      </div>
      <div class="card-body desktop-card-body">
        <form id="bookingCreateForm" class="desktop-form-grid">
          <input id="booking_service_id" name="service_id" type="hidden" value="${state.selectedService?.id || ''}" />
          <div class="form-row">
            <div class="field">
              <label for="booking_title">Title</label>
              <input id="booking_title" name="title" type="text" value="${state.selectedService ? `Booking for ${state.selectedService.name}` : ''}" required />
            </div>
            <div class="field">
              <label for="booking_priority">Priority</label>
              <select id="booking_priority" name="priority" required>
                ${['low', 'medium', 'high', 'urgent'].map((priority) => `
                  <option value="${priority}" ${priority === 'medium' ? 'selected' : ''}>${priority}</option>
                `).join('')}
              </select>
            </div>
          </div>
          <div class="field">
            <label for="booking_description">Description</label>
            <textarea id="booking_description" name="description">${state.selectedService?.description || ''}</textarea>
          </div>
          <div class="field">
            <label for="booking_location_address">Location Address</label>
            <textarea id="booking_location_address" name="location_address" required>${state.profile.address || ''}</textarea>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="booking_scheduled_date">Scheduled Date</label>
              <input id="booking_scheduled_date" name="scheduled_date" type="date" required />
            </div>
            <div class="field">
              <label for="booking_scheduled_time">Scheduled Time</label>
              <input id="booking_scheduled_time" name="scheduled_time" type="time" required />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="booking_labor_fee">Labor Fee</label>
              <input id="booking_labor_fee" name="labor_fee" type="number" min="1" step="0.01" value="${state.selectedService?.base_price || ''}" required />
            </div>
            <div class="field">
              <label for="booking_materials_fee">Materials Fee</label>
              <input id="booking_materials_fee" name="materials_fee" type="number" min="0" step="0.01" value="0" />
            </div>
          </div>
          <div class="field">
            <label for="booking_notes">Notes</label>
            <textarea id="booking_notes" name="notes"></textarea>
          </div>
          <div class="desktop-form-actions">
            <button id="bookingCreateButton" type="submit" class="action-button">Create Booking</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createAdminServiceEditor(state, helpers) {
  const { escapeHtml } = helpers;
  const service = state.selectedService || {};
  const isEditing = state.serviceEditorMode === 'edit' && service.id;
  const categories = getCategories(state);
  const categoryOptions = categories.length
    ? categories
    : ['mechanic', 'electrician', 'plumber', 'technician', 'general'];

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-screwdriver-wrench"></i> ${isEditing ? 'Edit Service' : 'Create Service'}</span>
        ${isEditing ? '<button type="button" class="ghost-button" id="serviceCreateNewButton">New Service</button>' : ''}
      </div>
      <div class="card-body desktop-card-body">
        <form id="serviceEditorForm" class="desktop-form-grid">
          ${isEditing ? `<input type="hidden" name="id" value="${escapeHtml(service.id || '')}" />` : ''}
          <div class="form-row">
            <div class="field">
              <label for="service_name">Service Name</label>
              <input id="service_name" name="name" type="text" value="${escapeHtml(service.name || '')}" required />
            </div>
            <div class="field">
              <label for="service_category">Category</label>
              <select id="service_category" name="category" required>
                ${categoryOptions.map((category) => `
                  <option value="${escapeHtml(category)}" ${String(service.category || '') === String(category) ? 'selected' : ''}>
                    ${escapeHtml(String(category).replace(/_/g, ' '))}
                  </option>
                `).join('')}
              </select>
            </div>
          </div>
          <div class="field">
            <label for="service_description">Description</label>
            <textarea id="service_description" name="description">${escapeHtml(service.description || '')}</textarea>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="service_base_price">Base Price</label>
              <input id="service_base_price" name="base_price" type="number" min="1" step="0.01" value="${escapeHtml(service.base_price ?? '')}" required />
            </div>
            <div class="field">
              <label for="service_estimated_duration">Estimated Duration (mins)</label>
              <input id="service_estimated_duration" name="estimated_duration" type="number" min="1" step="1" value="${escapeHtml(service.estimated_duration ?? '')}" />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="service_status">Status</label>
              <select id="service_status" name="status" ${isEditing ? '' : 'disabled'}>
                ${['active', 'inactive'].map((status) => `
                  <option value="${status}" ${String(service.status || 'active') === status ? 'selected' : ''}>${status}</option>
                `).join('')}
              </select>
            </div>
          </div>
          ${isEditing ? '' : '<small class="text-muted">New services are created as active by the backend.</small>'}
          <div class="desktop-form-actions">
            <button id="serviceSaveButton" type="submit" class="action-button">${isEditing ? 'Save Service' : 'Create Service'}</button>
            ${isEditing && String(service.status || 'active') === 'active' ? '<button id="serviceDeactivateButton" type="button" class="ghost-button desktop-danger-button">Deactivate Service</button>' : ''}
          </div>
        </form>
      </div>
    </div>
  `;
}

function createAdminServicesView(state, helpers) {
  const { createInfoBanner, createMetricGrid, createPageHeading, escapeHtml, formatCurrency, formatValue } = helpers;
  const categories = getCategories(state);
  const services = getServices(state);
  const filters = state.serviceFilters || getDefaultServiceFilters(state.role);
  const selectedService = state.selectedService || null;
  const activeCount = services.filter((service) => String(service.status || 'active') === 'active').length;
  const inactiveCount = services.filter((service) => String(service.status || '') === 'inactive').length;

  return `
    ${createPageHeading('fas fa-list', 'Services')}
    ${createInfoBanner(state.routeNotice || 'Manage service offerings from the desktop admin workspace.')}
    ${createMetricGrid([
      { icon: 'fas fa-screwdriver-wrench', value: services.length, label: 'Visible Services', tone: 'primary' },
      { icon: 'fas fa-layer-group', value: categories.length, label: 'Categories', tone: 'info' },
      { icon: 'fas fa-toggle-on', value: activeCount, label: 'Active Services', tone: 'success' },
      { icon: 'fas fa-toggle-off', value: inactiveCount, label: 'Inactive Services', tone: 'warning' }
    ])}

    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> Service Filters
      </div>
      <div class="card-body desktop-card-body">
        <div class="desktop-services-filter-grid">
          <div class="field">
            <label>Status</label>
            <div class="desktop-chip-row">
              ${['active', 'inactive'].map((status) => `
                <button type="button" class="ghost-button desktop-chip ${filters.status === status ? 'is-active' : ''}" data-service-status="${status}">
                  ${status}
                </button>
              `).join('')}
            </div>
          </div>
          <div class="field">
            <label>Category</label>
            <div class="desktop-chip-row">
              <button type="button" class="ghost-button desktop-chip ${!state.serviceCategory ? 'is-active' : ''}" data-category="">
                All
              </button>
              ${categories.map((category) => `
                <button type="button" class="ghost-button desktop-chip ${state.serviceCategory === category ? 'is-active' : ''}" data-category="${category}">
                  ${String(category).replace(/_/g, ' ')}
                </button>
              `).join('')}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-rectangle-list"></i> Service Catalog</span>
          <button type="button" class="action-button" id="serviceCreateButton">Add Service</button>
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>Base Price</th>
                </tr>
              </thead>
              <tbody>
                ${services.length ? services.map((service) => `
                  <tr class="${selectedService?.id === service.id ? 'table-active' : ''} desktop-clickable-row" data-service-select="${service.id}">
                    <td>
                      <strong>${escapeHtml(service.name || 'Service')}</strong>
                      <div class="desktop-table-subtext">${escapeHtml(service.description || 'No description provided.')}</div>
                    </td>
                    <td><span class="badge bg-secondary">${escapeHtml(service.category || 'general')}</span></td>
                    <td><span class="badge badge-${service.status || 'active'}">${escapeHtml(String(service.status || 'active').replace(/_/g, ' '))}</span></td>
                    <td>${formatCurrency(service.base_price ?? 0)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">No services match the current admin filters.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createAdminServiceEditor(state, helpers)}
        ${selectedService ? `
          <div class="card desktop-card mt-4">
            <div class="card-header desktop-card-header">
              <i class="fas fa-eye"></i> Service Preview
            </div>
            <div class="card-body desktop-card-body">
              <h5 class="mb-2">${escapeHtml(selectedService.name || 'Service')}</h5>
              <p class="text-muted mb-3">${escapeHtml(selectedService.description || 'No description provided.')}</p>
              <div class="desktop-insight-grid">
                <div class="desktop-insight-tile">
                  <span>Category</span>
                  <strong>${escapeHtml(selectedService.category || 'general')}</strong>
                </div>
                <div class="desktop-insight-tile">
                  <span>Status</span>
                  <strong>${escapeHtml(selectedService.status || 'active')}</strong>
                </div>
                <div class="desktop-insight-tile">
                  <span>Base Price</span>
                  <strong>${formatCurrency(selectedService.base_price ?? 0)}</strong>
                </div>
                <div class="desktop-insight-tile">
                  <span>Duration</span>
                  <strong>${formatValue(selectedService.estimated_duration)} mins</strong>
                </div>
              </div>
            </div>
          </div>
        ` : ''}
      </div>
    </div>
  `;
}

export function createServicesView(state, helpers) {
  if (state.role === 'admin' || state.role === 'super_admin') {
    return createAdminServicesView(state, helpers);
  }

  return createCustomerServicesView(state, helpers);
}

function refreshSelectedServiceInputs(service) {
  const serviceIdInput = getElementById('booking_service_id');
  const titleInput = getElementById('booking_title');
  const descriptionInput = getElementById('booking_description');
  const laborFeeInput = getElementById('booking_labor_fee');

  if (serviceIdInput) {
    serviceIdInput.value = String(service.id);
  }
  if (titleInput) {
    titleInput.value = `Booking for ${service.name}`;
  }
  if (descriptionInput) {
    descriptionInput.value = service.description || '';
  }
  if (laborFeeInput) {
    laborFeeInput.value = String(service.base_price || '');
  }
}

export function bindServicesView(state, session, bridge, actions) {
  const {
    performAuthenticatedRequest,
    renderRoute,
    setActiveNav,
    setStatus,
    updateInlineStatus
  } = actions;
  const contentElement = getElementById('desktopContentArea');
  const isAdmin = state.role === 'admin' || state.role === 'super_admin';

  if (!contentElement) {
    return;
  }

  contentElement.querySelectorAll('[data-category]').forEach((button) => {
    button.addEventListener('click', async () => {
      state.serviceCategory = button.getAttribute('data-category') || '';
      await renderRoute(state, session, bridge);
    });
  });

  contentElement.querySelectorAll('[data-service-status]').forEach((button) => {
    button.addEventListener('click', async () => {
      state.serviceFilters = {
        ...(state.serviceFilters || getDefaultServiceFilters(state.role)),
        status: button.getAttribute('data-service-status') || 'active'
      };
      await renderRoute(state, session, bridge);
    });
  });

  contentElement.querySelectorAll('[data-service-view], [data-service-select]').forEach((button) => {
    button.addEventListener('click', async () => {
      const serviceId = Number(button.getAttribute('data-service-view') || button.getAttribute('data-service-select'));
      const service = getServices(state).find((item) => Number(item.id) === serviceId);
      if (!service) {
        return;
      }

      state.selectedService = service;
      if (isAdmin) {
        state.serviceEditorMode = 'edit';
      }
      await renderRoute(state, session, bridge);
      updateInlineStatus(
        isAdmin
          ? `Viewing ${service.name}. You can edit it from the admin panel.`
          : `Viewing ${service.name}. You can book it below when ready.`,
        'success'
      );
    });
  });

  contentElement.querySelectorAll('[data-service-book]').forEach((button) => {
    button.addEventListener('click', () => {
      const serviceId = Number(button.getAttribute('data-service-book'));
      const service = getServices(state).find((item) => Number(item.id) === serviceId);
      if (!service) {
        return;
      }

      state.selectedService = service;
      refreshSelectedServiceInputs(service);
      updateInlineStatus(`Selected ${service.name}. Complete the booking form below.`, 'success');
    });
  });

  getElementById('serviceCreateButton')?.addEventListener('click', async () => {
    state.selectedService = null;
    state.serviceEditorMode = 'create';
    await renderRoute(state, session, bridge);
    updateInlineStatus('Ready to create a new service.', 'success');
  });

  getElementById('serviceCreateNewButton')?.addEventListener('click', async () => {
    state.selectedService = null;
    state.serviceEditorMode = 'create';
    await renderRoute(state, session, bridge);
    updateInlineStatus('Ready to create a new service.', 'success');
  });

  const serviceForm = getElementById('serviceEditorForm');
  const serviceSaveButton = getElementById('serviceSaveButton');
  if (isAdmin && serviceForm && serviceSaveButton) {
    serviceForm.onsubmit = async (event) => {
      event.preventDefault();
      const isEditing = state.serviceEditorMode === 'edit' && state.selectedService?.id;
      serviceSaveButton.disabled = true;
      serviceSaveButton.textContent = isEditing ? 'Saving...' : 'Creating...';

      const payload = Object.fromEntries(new FormData(serviceForm).entries());
      if (!isEditing) {
        delete payload.status;
      }

      try {
        const response = await performAuthenticatedRequest(session, bridge, isEditing ? `/api/services/${payload.id}` : '/api/services', {
          method: isEditing ? 'PUT' : 'POST',
          body: payload
        });

        state.serviceCatalogCache = {};
        state.selectedService = response?.data || null;
        state.serviceEditorMode = state.selectedService?.id ? 'edit' : 'create';
        updateInlineStatus(response?.message || `Service ${isEditing ? 'updated' : 'created'} successfully.`, 'success');
        setStatus(response?.message || `Service ${isEditing ? 'updated' : 'created'} successfully.`, 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to save service.', 'error');
        setStatus(error.message || 'Failed to save service.', 'error');
      } finally {
        serviceSaveButton.disabled = false;
        serviceSaveButton.textContent = isEditing ? 'Save Service' : 'Create Service';
      }
    };
  }

  const serviceDeactivateButton = getElementById('serviceDeactivateButton');
  if (isAdmin && serviceDeactivateButton) {
    serviceDeactivateButton.onclick = async () => {
      const serviceId = state.selectedService?.id;
      if (!serviceId) {
        return;
      }

      if (!window.confirm('Deactivate this service? It will be hidden from active listings but not permanently deleted.')) {
        return;
      }

      serviceDeactivateButton.disabled = true;
      serviceDeactivateButton.textContent = 'Deactivating...';

      try {
        const response = await performAuthenticatedRequest(session, bridge, `/api/services/${serviceId}`, {
          method: 'PUT',
          body: {
            name: state.selectedService.name,
            description: state.selectedService.description || '',
            category: state.selectedService.category,
            base_price: state.selectedService.base_price,
            estimated_duration: state.selectedService.estimated_duration || '',
            status: 'inactive'
          }
        });

        state.serviceCatalogCache = {};
        state.selectedService = response?.data || null;
        state.serviceEditorMode = 'create';
        updateInlineStatus(response?.message || 'Service deactivated successfully.', 'success');
        setStatus(response?.message || 'Service deactivated successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to deactivate service.', 'error');
        setStatus(error.message || 'Failed to deactivate service.', 'error');
      } finally {
        serviceDeactivateButton.disabled = false;
        serviceDeactivateButton.textContent = 'Deactivate Service';
      }
    };
  }

  const bookingForm = getElementById('bookingCreateForm');
  const bookingSubmitButton = getElementById('bookingCreateButton');
  if (isAdmin || !bookingForm || !bookingSubmitButton) {
    return;
  }

  bookingForm.onsubmit = async (event) => {
    event.preventDefault();
    bookingSubmitButton.disabled = true;
    bookingSubmitButton.textContent = 'Creating...';

    const payload = Object.fromEntries(new FormData(bookingForm).entries());
    payload.customer_id = String(state.profile.id);

    try {
      const response = await performAuthenticatedRequest(session, bridge, '/api/bookings', {
        method: 'POST',
        body: payload
      });

      bookingForm.reset();
      state.selectedService = null;
      updateInlineStatus(response?.message || 'Booking created successfully.', 'success');
      setStatus('Booking created successfully.', 'success');
      state.currentRoute = 'bookings';
      setActiveNav('bookings');
      await renderRoute(state, session, bridge);
    } catch (error) {
      updateInlineStatus(error.message || 'Failed to create booking.', 'error');
      setStatus(error.message || 'Failed to create booking.', 'error');
    } finally {
      bookingSubmitButton.disabled = false;
      bookingSubmitButton.textContent = 'Create Booking';
    }
  };
}
