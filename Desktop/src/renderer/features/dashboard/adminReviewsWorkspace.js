import { getElementById } from '../../core/dom.js';

export function getDefaultAdminReviewFilters() {
  return {
    q: '',
    status: '',
    workerId: ''
  };
}

function normalizeStatus(value) {
  return String(value || 'published').trim().toLowerCase();
}

function getReviewWorkerName(review) {
  const fullName = String(review?.worker_name || '').trim();
  if (fullName) {
    return fullName;
  }

  return [review?.worker_first_name, review?.worker_last_name].filter(Boolean).join(' ').trim() || 'Unknown worker';
}

function getReviewCustomerName(review) {
  const fullName = String(review?.customer_name || '').trim();
  if (fullName) {
    return fullName;
  }

  return [review?.customer_first_name, review?.customer_last_name].filter(Boolean).join(' ').trim() || 'Unknown customer';
}

function getReviewServiceName(review) {
  return String(review?.service_name || review?.booking_title || review?.title || 'Unspecified service').trim();
}

function getStatusTone(status) {
  const normalized = normalizeStatus(status);
  if (normalized === 'flagged') {
    return 'danger';
  }
  if (normalized === 'hidden') {
    return 'secondary';
  }
  return 'success';
}

function getDistributionCount(distribution, rating) {
  if (!distribution || typeof distribution !== 'object') {
    return 0;
  }

  return Number(distribution[rating] ?? distribution[String(rating)] ?? 0);
}

function dedupeReviews(reviews = []) {
  const byId = new Map();
  reviews.forEach((review) => {
    const id = Number(review?.id || 0);
    if (!id) {
      return;
    }

    const existing = byId.get(id) || {};
    byId.set(id, { ...existing, ...review });
  });
  return Array.from(byId.values());
}

function applyReviewFilters(reviews = [], filters = {}) {
  const query = String(filters.q || '').trim().toLowerCase();
  const status = normalizeStatus(filters.status || '');
  const workerId = String(filters.workerId || '').trim();

  return (reviews || []).filter((review) => {
    const matchesStatus = !status || normalizeStatus(review.status) === status;
    const matchesWorker = !workerId || String(review.worker_id || '') === workerId;
    const searchableText = [
      review.booking_reference,
      review.comment,
      getReviewCustomerName(review),
      getReviewWorkerName(review),
      getReviewServiceName(review)
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();
    const matchesQuery = !query || searchableText.includes(query);

    return matchesStatus && matchesWorker && matchesQuery;
  });
}

function createReviewFiltersCard(state, helpers) {
  const { escapeHtml } = helpers;
  const filters = state.adminReviewFilters || getDefaultAdminReviewFilters();
  const workers = Array.isArray(state.routeReviewWorkers) ? state.routeReviewWorkers : [];

  return `
    <div class="card desktop-card mb-4">
      <div class="card-header desktop-card-header">
        <i class="fas fa-filter"></i> Review Filters
      </div>
      <div class="card-body desktop-card-body">
        <form id="adminReviewFilterForm" class="desktop-filter-grid">
          <div class="field">
            <label for="adminReviewQuery">Search</label>
            <input id="adminReviewQuery" name="q" type="text" value="${escapeHtml(filters.q || '')}" placeholder="Booking, customer, worker, or comment" />
          </div>
          <div class="field">
            <label for="adminReviewStatus">Status</label>
            <select id="adminReviewStatus" name="status">
              <option value="" ${!filters.status ? 'selected' : ''}>All statuses</option>
              ${['published', 'hidden', 'flagged'].map((status) => `
                <option value="${status}" ${normalizeStatus(filters.status) === status ? 'selected' : ''}>${status}</option>
              `).join('')}
            </select>
          </div>
          <div class="field">
            <label for="adminReviewWorker">Worker</label>
            <select id="adminReviewWorker" name="workerId">
              <option value="">All workers</option>
              ${workers.map((worker) => `
                <option value="${worker.id}" ${String(filters.workerId || '') === String(worker.id) ? 'selected' : ''}>
                  ${escapeHtml([worker.first_name, worker.last_name].filter(Boolean).join(' ') || worker.name || `Worker #${worker.id}`)}
                </option>
              `).join('')}
            </select>
          </div>
          <div class="desktop-form-actions">
            <button type="submit" class="action-button">Apply Filters</button>
            <button type="button" class="ghost-button" id="adminReviewResetFiltersButton">Reset</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function createReviewEmptyPanel() {
  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header">
        <i class="fas fa-star"></i> Review Details
      </div>
      <div class="card-body desktop-card-body">
        <div class="chart-placeholder desktop-placeholder-card">
          <div>
            <h5 class="mb-2">Select a review</h5>
            <p class="mb-0 text-muted">Inspect the full review here, then update its visibility status if moderation is needed.</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function createReviewDetailPanel(state, helpers) {
  const { escapeHtml, formatDate, formatValue } = helpers;
  const review = state.selectedAdminReview || null;

  if (!review) {
    return createReviewEmptyPanel();
  }

  const rating = Number(review.rating || 0);
  const detailRatings = review.detailed_ratings || {};
  const currentStatus = normalizeStatus(review.status);

  return `
    <div class="card desktop-card">
      <div class="card-header desktop-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-star"></i> Review Details</span>
        <span class="badge bg-${getStatusTone(currentStatus)}">${escapeHtml(currentStatus)}</span>
      </div>
      <div class="card-body desktop-card-body">
        <h5 class="mb-2">${escapeHtml(getReviewServiceName(review))}</h5>
        <p class="text-muted mb-3">${escapeHtml(review.booking_reference || `Review #${review.id}`)}</p>

        <div class="desktop-insight-grid">
          <div class="desktop-insight-tile">
            <span>Customer</span>
            <strong>${escapeHtml(getReviewCustomerName(review))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Worker</span>
            <strong>${escapeHtml(getReviewWorkerName(review))}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Overall Rating</span>
            <strong>${formatValue(rating)}/5</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Recommend</span>
            <strong>${review.would_recommend === 0 || String(review.would_recommend) === '0' ? 'No' : 'Yes'}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Service Quality</span>
            <strong>${formatValue(detailRatings.service_quality ?? review.service_quality ?? '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Timeliness</span>
            <strong>${formatValue(detailRatings.timeliness ?? review.timeliness ?? '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Professionalism</span>
            <strong>${formatValue(detailRatings.professionalism ?? review.professionalism ?? '-')}</strong>
          </div>
          <div class="desktop-insight-tile">
            <span>Created</span>
            <strong>${formatDate(review.created_at)}</strong>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Comment</h6>
          <div class="border rounded p-3">${escapeHtml(review.comment || 'No written comment provided for this review.')}</div>
        </div>

        <div class="mt-4">
          <h6 class="text-muted mb-2">Moderation</h6>
          <div class="desktop-form-actions">
            ${['published', 'hidden', 'flagged'].map((status) => `
              <button
                type="button"
                class="${currentStatus === status ? 'action-button' : 'ghost-button'}"
                data-admin-review-status="${status}"
                data-review-id="${review.id}"
                ${currentStatus === status ? 'disabled' : ''}
              >
                Mark ${escapeHtml(status)}
              </button>
            `).join('')}
          </div>
        </div>
      </div>
    </div>
  `;
}

function buildViewHelpers(tools) {
  return {
    createInfoBanner: tools.createInfoBanner,
    createMetricGrid: tools.createMetricGrid,
    createPageHeading: tools.createPageHeading,
    escapeHtml: tools.escapeHtml,
    formatDate: tools.formatDate,
    formatValue: tools.formatValue
  };
}

export function createAdminReviewsView(state, helpers) {
  const { createInfoBanner, createMetricGrid, createPageHeading, escapeHtml, formatDate, formatValue } = helpers;
  const stats = state.routeReviewStats || {};
  const distribution = stats.rating_distribution || {};
  const filteredReviews = applyReviewFilters(state.routeReviews || [], state.adminReviewFilters || getDefaultAdminReviewFilters());
  const selectedReviewId = Number(state.selectedAdminReviewId || 0);
  const topWorkers = Array.isArray(state.routeTopReviewWorkers) ? state.routeTopReviewWorkers.slice(0, 5) : [];

  return `
    ${createPageHeading('fas fa-star', 'Rates')}
    ${createInfoBanner(state.routeNotice || 'Moderate customer reviews, inspect flagged feedback, and manage review visibility from Desktop.')}
    ${createMetricGrid([
      { icon: 'fas fa-star', value: stats.total_reviews ?? filteredReviews.length, label: 'Published Reviews', tone: 'success' },
      { icon: 'fas fa-triangle-exclamation', value: stats.flagged_reviews ?? 0, label: 'Flagged Reviews', tone: 'danger' },
      { icon: 'fas fa-scale-balanced', value: stats.average_rating ?? 0, label: 'Average Rating', tone: 'info' },
      { icon: 'fas fa-list-check', value: filteredReviews.length, label: 'Visible in Queue', tone: 'primary' }
    ])}

    ${createReviewFiltersCard(state, helpers)}

    <div class="row mb-4">
      <div class="col-xl-8">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-chart-bar"></i> Rating Distribution
          </div>
          <div class="card-body desktop-card-body">
            <div class="desktop-insight-grid">
              ${[5, 4, 3, 2, 1].map((rating) => `
                <div class="desktop-insight-tile">
                  <span>${rating} Star${rating === 1 ? '' : 's'}</span>
                  <strong>${formatValue(getDistributionCount(distribution, rating))}</strong>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card desktop-card">
          <div class="card-header desktop-card-header">
            <i class="fas fa-trophy"></i> Top Workers
          </div>
          <div class="card-body desktop-card-body">
            ${topWorkers.length ? topWorkers.map((worker) => `
              <div class="desktop-insight-row">
                <div>
                  <strong>${escapeHtml([worker.first_name, worker.last_name].filter(Boolean).join(' ') || worker.name || `Worker #${worker.id}`)}</strong>
                  <div class="desktop-table-subtext">${formatValue(worker.review_count ?? 0)} reviews</div>
                </div>
                <span class="badge bg-primary">${formatValue(worker.average_rating ?? worker.rating ?? '-')}</span>
              </div>
            `).join('') : `
              <div class="chart-placeholder desktop-placeholder-card">No top worker review data was returned.</div>
            `}
          </div>
        </div>
      </div>
    </div>

    <div class="desktop-admin-split">
      <div class="card desktop-card">
        <div class="card-header desktop-card-header">
          <i class="fas fa-comments"></i> Review Queue
        </div>
        <div class="card-body desktop-card-body">
          <div class="table-responsive desktop-users-list-scroll">
            <table class="table table-hover desktop-users-table">
              <thead>
                <tr>
                  <th>Review</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                ${filteredReviews.length ? filteredReviews.map((review) => `
                  <tr
                    class="${selectedReviewId === Number(review.id) ? 'desktop-row-selected' : ''} desktop-clickable-row"
                    data-admin-review-row="true"
                    data-review-id="${review.id}"
                  >
                    <td>
                      <strong class="desktop-user-name">${escapeHtml(review.booking_reference || `Review #${review.id}`)}</strong>
                      <div class="desktop-table-subtext">${escapeHtml(getReviewWorkerName(review))} • ${escapeHtml(getReviewServiceName(review))}</div>
                    </td>
                    <td><span class="badge bg-${getStatusTone(review.status)}">${escapeHtml(normalizeStatus(review.status))}</span></td>
                    <td>${formatDate(review.created_at)}</td>
                  </tr>
                `).join('') : `
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No reviews matched the current moderation filters.</td>
                  </tr>
                `}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        ${createReviewDetailPanel(state, helpers)}
      </div>
    </div>
  `;
}

function rerenderAdminReviewsView(state, session, bridge, tools) {
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  contentElement.innerHTML = createAdminReviewsView(state, buildViewHelpers(tools));
  bindAdminReviewsView(state, session, bridge, tools);
}

export async function loadAdminReviewsRouteData(state, session, bridge, performAuthenticatedRequest) {
  state.adminReviewFilters = {
    ...getDefaultAdminReviewFilters(),
    ...(state.adminReviewFilters || {})
  };

  const [recentResponse, flaggedResponse, statisticsResponse, topWorkersResponse] = await Promise.all([
    performAuthenticatedRequest(session, bridge, '/api/reviews/recent?limit=50', { method: 'GET' }).catch(() => ({ data: [] })),
    performAuthenticatedRequest(session, bridge, '/api/reviews/flagged', { method: 'GET' }).catch(() => ({ data: [] })),
    performAuthenticatedRequest(session, bridge, '/api/reviews/statistics', { method: 'GET' }).catch(() => ({ data: {} })),
    performAuthenticatedRequest(session, bridge, '/api/reviews/top-workers?limit=10', { method: 'GET' }).catch(() => ({ data: [] }))
  ]);

  const mergedReviews = dedupeReviews([
    ...(recentResponse?.data || []),
    ...(flaggedResponse?.data || [])
  ]);

  state.routeReviews = mergedReviews;
  state.routeReviewStats = statisticsResponse?.data || {};
  state.routeTopReviewWorkers = topWorkersResponse?.data || [];
  state.routeReviewWorkers = dedupeReviews(mergedReviews.map((review) => ({
    id: review.worker_id,
    first_name: review.worker_first_name,
    last_name: review.worker_last_name,
    name: getReviewWorkerName(review)
  }))).filter((worker) => Number(worker.id || 0));
  state.routeNotice = mergedReviews.length
    ? 'Recent and flagged reviews are loaded for moderation here. Use filters to narrow what needs attention.'
    : 'No recent or flagged reviews were returned from the backend right now.';

  const selectedStillExists = mergedReviews.some((review) => Number(review.id) === Number(state.selectedAdminReviewId || 0));
  if (!selectedStillExists) {
    state.selectedAdminReviewId = Number(mergedReviews[0]?.id || 0) || null;
  }

  if (!state.selectedAdminReviewId) {
    state.selectedAdminReview = null;
    return;
  }

  try {
    const detailResponse = await performAuthenticatedRequest(
      session,
      bridge,
      `/api/reviews/${state.selectedAdminReviewId}`,
      { method: 'GET' }
    );
    state.selectedAdminReview = detailResponse?.data || null;
  } catch {
    state.selectedAdminReview = mergedReviews.find((review) => Number(review.id) === Number(state.selectedAdminReviewId)) || null;
  }
}

export function bindAdminReviewsView(state, session, bridge, tools) {
  const { performAuthenticatedRequest, renderRoute, setStatus, updateInlineStatus } = tools;
  const contentElement = getElementById('desktopContentArea');
  if (!contentElement) {
    return;
  }

  const filterForm = getElementById('adminReviewFilterForm');
  filterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(filterForm);
    state.adminReviewFilters = {
      q: String(formData.get('q') || '').trim(),
      status: String(formData.get('status') || '').trim(),
      workerId: String(formData.get('workerId') || '').trim()
    };
    rerenderAdminReviewsView(state, session, bridge, tools);
    updateInlineStatus('Review filters applied.', 'success');
  });

  getElementById('adminReviewResetFiltersButton')?.addEventListener('click', () => {
    state.adminReviewFilters = getDefaultAdminReviewFilters();
    rerenderAdminReviewsView(state, session, bridge, tools);
    updateInlineStatus('Review filters reset.', 'success');
  });

  contentElement.querySelectorAll('[data-admin-review-row]').forEach((row) => {
    row.addEventListener('click', async () => {
      const reviewId = Number(row.getAttribute('data-review-id'));
      if (!reviewId) {
        return;
      }

      const selectedFromList = (state.routeReviews || []).find((review) => Number(review.id) === reviewId) || null;
      state.selectedAdminReviewId = reviewId;
      state.selectedAdminReview = selectedFromList;
      rerenderAdminReviewsView(state, session, bridge, tools);

      try {
        const detailResponse = await performAuthenticatedRequest(
          session,
          bridge,
          `/api/reviews/${reviewId}`,
          { method: 'GET' }
        );
        const detailedReview = detailResponse?.data || null;
        if (Number(state.selectedAdminReviewId || 0) !== reviewId || !detailedReview) {
          return;
        }

        state.selectedAdminReview = detailedReview;
        rerenderAdminReviewsView(state, session, bridge, tools);
      } catch {
        // Keep the locally available review row data visible if detail loading fails.
      }
    });
  });

  contentElement.querySelectorAll('[data-admin-review-status]').forEach((button) => {
    button.addEventListener('click', async () => {
      const reviewId = Number(button.getAttribute('data-review-id'));
      const status = String(button.getAttribute('data-admin-review-status') || '').trim();
      if (!reviewId || !status) {
        return;
      }

      button.disabled = true;
      const previousLabel = button.textContent;
      button.textContent = 'Saving...';

      try {
        const response = await performAuthenticatedRequest(session, bridge, `/api/reviews/${reviewId}/status`, {
          method: 'PUT',
          body: { status }
        });

        updateInlineStatus(response?.message || 'Review status updated successfully.', 'success');
        setStatus(response?.message || 'Review status updated successfully.', 'success');
        await renderRoute(state, session, bridge);
      } catch (error) {
        updateInlineStatus(error.message || 'Failed to update review status.', 'error');
        setStatus(error.message || 'Failed to update review status.', 'error');
      } finally {
        button.disabled = false;
        button.textContent = previousLabel;
      }
    });
  });
}
