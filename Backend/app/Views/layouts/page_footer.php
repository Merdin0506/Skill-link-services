        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Skill Link Services. All rights reserved.</p>
        </div>
    </div> <!-- End Main Content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Centralized Sidebar Toggle Script -->
    <script src="<?= base_url('js/sidebar-toggle.js') ?>"></script>

    <?php if (isset($customJs)): ?>
        <script src="<?= base_url($customJs) ?>"></script>
    <?php endif; ?>
</body>
</html>
