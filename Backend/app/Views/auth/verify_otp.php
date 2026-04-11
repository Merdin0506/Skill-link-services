<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - SkillLink Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .otp-container { max-width: 400px; margin: 100px auto; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .btn-primary { background-color: #1976D2; border: none; }
        .btn-primary:hover { background-color: #1565C0; }
    </style>
</head>
<body>

<div class="container otp-container">
    <div class="card">
        <div class="card-body p-5">
            <h3 class="text-center mb-4">Two-Factor Authentication</h3>
            <p class="text-center text-muted">Please enter the 6-digit code sent to your email.</p>

            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger small"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success small"><?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('warning')) : ?>
                <div class="alert alert-warning small"><?= session()->getFlashdata('warning') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('auth/doVerifyOtp') ?>" method="post">
                <div class="mb-3">
                    <input type="text" name="otp" class="form-control form-control-lg text-center fw-bold" 
                           placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Verify Code</button>
            </form>

            <div class="text-center">
                <form action="<?= base_url('auth/resendOtp') ?>" method="post">
                    <button type="submit" class="btn btn-link text-decoration-none">Resend OTP</button>
                </form>
                <a href="<?= base_url('auth/logout') ?>" class="text-muted small">Back to Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
