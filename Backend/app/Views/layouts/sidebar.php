<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="brand">
        <i class="fas fa-link" style="font-size: 28px;"></i>
        <h5>SkillLink</h5>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= base_url('dashboard') ?>" class="<?= (current_url() == base_url('dashboard')) ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if (isset($role) && in_array($role, ['admin', 'super_admin'], true)): ?>
            <a href="<?= base_url('admin/users') ?>" class="<?= (strpos(current_url(), '/admin/users') !== false) ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="<?= base_url('admin/bookings') ?>" class="<?= (strpos(current_url(), '/admin/bookings') !== false) ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Bookings</span>
            </a>
            <a href="<?= base_url('admin/payments') ?>" class="<?= (strpos(current_url(), '/admin/payments') !== false) ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="<?= base_url('admin/backups') ?>" class="<?= (strpos(current_url(), '/admin/backups') !== false) ? 'active' : '' ?>">
                <i class="fas fa-database"></i>
                <span>Backups</span>
            </a>
            <a href="<?= base_url('admin/records') ?>" class="<?= (strpos(current_url(), '/admin/records') !== false) ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Service Records</span>
            </a>

            <a href="<?= base_url('security/dashboard') ?>" class="<?= (strpos(current_url(), '/security/') !== false) ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i>
                <span>Security</span>
            </a>
        <?php elseif (isset($role) && $role === 'worker'): ?>
            <a href="<?= base_url('worker/available-jobs') ?>" class="<?= (strpos(current_url(), '/worker/available-jobs') !== false) ? 'active' : '' ?>">
                <i class="fas fa-briefcase"></i>
                <span>Available Jobs</span>
            </a>
            <a href="<?= base_url('worker/my-jobs') ?>" class="<?= (strpos(current_url(), '/worker/my-jobs') !== false) ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i>
                <span>My Jobs</span>
            </a>
            <a href="<?= base_url('worker/earnings') ?>" class="<?= (strpos(current_url(), '/worker/earnings') !== false) ? 'active' : '' ?>">
                <i class="fas fa-wallet"></i>
                <span>Earnings</span>
            </a>
        <?php elseif (isset($role) && $role === 'customer'): ?>
            <a href="<?= base_url('customer/bookings') ?>" class="<?= (strpos(current_url(), '/customer/bookings') !== false) ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>My Bookings</span>
            </a>
            <a href="<?= base_url('customer/services') ?>" class="<?= (strpos(current_url(), '/customer/services') !== false) ? 'active' : '' ?>">
                <i class="fas fa-list"></i>
                <span>Services</span>
            </a>
            <a href="<?= base_url('customer/payments') ?>" class="<?= (strpos(current_url(), '/customer/payments') !== false) ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
        <?php elseif (isset($role) && $role === 'finance'): ?>
            <a href="<?= base_url('finance/payments') ?>" class="<?= (strpos(current_url(), '/finance/payments') !== false) ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Payments</span>
            </a>
            <a href="<?= base_url('finance/payouts') ?>" class="<?= (strpos(current_url(), '/finance/payouts') !== false) ? 'active' : '' ?>">
                <i class="fas fa-hand-holding-usd"></i>
                <span>Worker Payouts</span>
            </a>
            <a href="<?= base_url('finance/reports') ?>" class="<?= (strpos(current_url(), '/finance/reports') !== false) ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Financial Reports</span>
            </a>
        <?php endif; ?>
        
        <a href="<?= base_url('profile') ?>" class="<?= (strpos(current_url(), '/profile') !== false) ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
        </a>
        <a href="<?= base_url('settings') ?>" class="<?= (strpos(current_url(), '/settings') !== false) ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="<?= base_url('logout') ?>" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
