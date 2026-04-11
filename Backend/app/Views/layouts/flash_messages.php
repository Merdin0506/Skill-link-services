<?php
$flashError = session('error');
$flashSuccess = session('success');
$validationErrors = session('errors');

if (is_array($validationErrors)) {
    $validationErrors = array_filter($validationErrors, static fn ($message) => is_string($message) && trim($message) !== '');
}
?>

<?php if (!empty($flashSuccess)): ?>
    <div class="skilllink-alert skilllink-alert-success alert-dismissible fade show" role="alert">
        <div class="skilllink-alert-icon">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
        </div>
        <div class="skilllink-alert-body">
            <strong>All set</strong>
            <p class="mb-0"><?= esc($flashSuccess) ?></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="skilllink-alert skilllink-alert-error alert-dismissible fade show" role="alert">
        <div class="skilllink-alert-icon">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        </div>
        <div class="skilllink-alert-body">
            <strong>Something went wrong</strong>
            <p class="mb-0"><?= esc($flashError) ?></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($validationErrors)): ?>
    <div class="skilllink-alert skilllink-alert-error" role="alert">
        <div class="skilllink-alert-icon">
            <i class="fas fa-list-check" aria-hidden="true"></i>
        </div>
        <div class="skilllink-alert-body">
            <strong>Please check a few details</strong>
            <ul class="mb-0">
                <?php foreach ($validationErrors as $message): ?>
                    <li><?= esc($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
