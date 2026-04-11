        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Skill Link Services. All rights reserved.</p>
        </div>
    </div> <!-- End Main Content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Centralized Sidebar Toggle Script -->
    <script src="<?= base_url('js/sidebar-toggle.js') ?>"></script>

    <div class="modal fade skilllink-confirm" id="skilllinkConfirmModal" tabindex="-1" aria-labelledby="skilllinkConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="skilllinkConfirmTitle">
                        <span class="confirm-icon-shell" id="skilllinkConfirmIconShell">
                            <i class="fas fa-sparkles" id="skilllinkConfirmIcon"></i>
                        </span>
                        <span id="skilllinkConfirmTitleText">Confirm Action</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="confirm-lead" id="skilllinkConfirmLead">Please confirm this action.</p>
                    <p class="confirm-text" id="skilllinkConfirmText">This action will continue once you confirm.</p>
                    <div class="confirm-chip" id="skilllinkConfirmChip">
                        <i class="fas fa-shield-heart"></i>
                        Review before continuing
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="skilllinkConfirmAction">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('skilllinkConfirmModal');
            if (!modalElement || typeof bootstrap === 'undefined') {
                return;
            }

            const confirmModal = new bootstrap.Modal(modalElement);
            const confirmLead = document.getElementById('skilllinkConfirmLead');
            const confirmText = document.getElementById('skilllinkConfirmText');
            const confirmButton = document.getElementById('skilllinkConfirmAction');
            const confirmTitleText = document.getElementById('skilllinkConfirmTitleText');
            const confirmIcon = document.getElementById('skilllinkConfirmIcon');
            const confirmChip = document.getElementById('skilllinkConfirmChip');
            let pendingAction = null;

            function resolveTone(confirmClass) {
                if ((confirmClass || '').includes('danger')) {
                    return {
                        tone: 'danger',
                        icon: 'fa-triangle-exclamation',
                        chip: 'This action needs extra care'
                    };
                }

                if ((confirmClass || '').includes('success')) {
                    return {
                        tone: 'success',
                        icon: 'fa-circle-check',
                        chip: 'Looks ready to continue'
                    };
                }

                if ((confirmClass || '').includes('warning')) {
                    return {
                        tone: 'warning',
                        icon: 'fa-rotate',
                        chip: 'Please double-check this step'
                    };
                }

                return {
                    tone: 'default',
                    icon: 'fa-sparkles',
                    chip: 'Review before continuing'
                };
            }

            function openConfirm(options) {
                const toneState = resolveTone(options.confirmClass);

                modalElement.dataset.tone = toneState.tone;
                confirmTitleText.textContent = options.header || 'Confirm Action';
                confirmIcon.className = `fas ${toneState.icon}`;
                confirmLead.textContent = options.title || 'Please confirm this action.';
                confirmText.textContent = options.message || 'This action will continue once you confirm.';
                confirmChip.innerHTML = `<i class="fas ${toneState.icon}"></i>${toneState.chip}`;
                confirmButton.textContent = options.confirmLabel || 'Continue';
                confirmButton.className = `btn ${options.confirmClass || 'btn-primary'}`;
                pendingAction = options.onConfirm || null;
                confirmModal.show();
            }

            function extractLegacyConfirmMessage(handlerText) {
                if (!handlerText || !handlerText.includes('confirm(')) {
                    return null;
                }

                const start = handlerText.indexOf("confirm('");
                const end = handlerText.lastIndexOf("')");

                if (start !== -1 && end > start) {
                    return handlerText
                        .slice(start + 9, end)
                        .replace(/\\n/g, '\n')
                        .replace(/\\'/g, "'");
                }

                return 'Please confirm this action.';
            }

            confirmButton.addEventListener('click', function () {
                const action = pendingAction;
                pendingAction = null;
                confirmModal.hide();
                if (typeof action === 'function') {
                    action();
                }
            });

            modalElement.addEventListener('hidden.bs.modal', function () {
                pendingAction = null;
            });

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-confirm-message]');
                if (!trigger) {
                    return;
                }

                const isSubmitButton = trigger.tagName === 'BUTTON' && trigger.type === 'submit';
                if (!isSubmitButton) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const form = trigger.form || trigger.closest('form');
                if (!form) {
                    return;
                }

                openConfirm({
                    header: trigger.getAttribute('data-confirm-header') || 'Confirm Action',
                    title: trigger.getAttribute('data-confirm-title') || 'Please confirm this action.',
                    message: trigger.getAttribute('data-confirm-message'),
                    confirmLabel: trigger.getAttribute('data-confirm-label') || 'Continue',
                    confirmClass: trigger.getAttribute('data-confirm-class') || 'btn-primary',
                    onConfirm: function () {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    }
                });
            });

            document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function (form) {
                if (form.dataset.confirmMessage) {
                    return;
                }

                form.dataset.confirmMessage = extractLegacyConfirmMessage(form.getAttribute('onsubmit')) || 'Please confirm this action.';
                form.dataset.confirmLabel = form.dataset.confirmLabel || 'Continue';
                form.removeAttribute('onsubmit');
            });

            document.querySelectorAll('button[onclick*="confirm("]').forEach(function (button) {
                if (button.dataset.confirmMessage) {
                    return;
                }

                button.dataset.confirmMessage = extractLegacyConfirmMessage(button.getAttribute('onclick')) || 'Please confirm this action.';
                button.dataset.confirmLabel = button.dataset.confirmLabel || 'Continue';
                button.removeAttribute('onclick');
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || form.dataset.confirmHandled === 'true' || !form.dataset.confirmMessage) {
                    return;
                }

                event.preventDefault();
                form.dataset.confirmHandled = 'true';

                openConfirm({
                    header: form.dataset.confirmHeader || 'Confirm Action',
                    title: form.dataset.confirmTitle || 'Please confirm this action.',
                    message: form.dataset.confirmMessage,
                    confirmLabel: form.dataset.confirmLabel || 'Continue',
                    confirmClass: form.dataset.confirmClass || 'btn-primary',
                    onConfirm: function () {
                        form.submit();
                    }
                });

                setTimeout(function () {
                    delete form.dataset.confirmHandled;
                }, 0);
            });
        });
    </script>

    <?php if (isset($customJs)): ?>
        <script src="<?= base_url($customJs) ?>"></script>
    <?php endif; ?>
</body>
</html>
