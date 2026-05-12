<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3c72;
            --primary-2: #2a5298;
            --text: #333;
            --muted: #999;
            --danger: #b9382b;
            --success: #157347;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            place-items: center;
            justify-content: center;
            padding: 20px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: var(--text);
        }

        .register-shell {
            width: 100%;
            max-width: 650px;
        }

        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        .card-inner {
            padding: 32px 18px 20px;
        }

        .brand-row {
            display: block;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 14px;
            text-align: center;
        }

        .brand-mark {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
            box-shadow: 0 10px 30px rgba(30, 60, 114, 0.3);
            margin: 0 auto 12px;
            align-items: center;
            justify-content: center;
        }

        .brand-text h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .brand-text p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        .screen {
            transition: opacity 220ms ease, transform 220ms ease;
        }

        .screen.is-hidden {
            opacity: 0;
            transform: translateY(14px);
            pointer-events: none;
            position: absolute;
            inset: 0;
        }

        .screen.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .selector-title {
            text-align: center;
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
        }

        .selector-copy {
            text-align: center;
            margin: 0 auto 12px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .type-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .type-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 14px;
            text-align: center;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            min-height: 100%;
        }

        .type-card:hover {
            transform: translateY(-2px);
            border-color: #2a5298;
            background: white;
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.15);
        }

        .type-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 0.95rem;
            margin-bottom: 0;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
        }

        .type-card.worker .type-icon {
            background: linear-gradient(135deg, #0f7a55 0%, #1cc88a 100%);
        }

        .type-card h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .type-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.4;
            font-size: 12px;
            max-width: 200px;
        }

        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-top: 2px;
            padding: 0;
            border-radius: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #36507a;
            background: transparent;
        }

        .register-panel {
            display: grid;
            gap: 18px;
        }

        .panel-head {
            display: block;
        }

        .panel-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
        }

        .panel-subtitle {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.55;
            text-align: center;
        }

        .back-button {
            border: none;
            background: transparent;
            color: #2a5298;
            padding: 0;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: color 180ms ease, transform 180ms ease;
            margin-top: 12px;
        }

        .back-button:hover {
            color: #1e3c72;
            transform: translateY(-1px);
        }

        .auth-notice {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .auth-notice.error {
            color: var(--danger);
            background: #fff4f2;
            border: 1px solid #f4cdc6;
        }

        .auth-notice.success {
            color: var(--success);
            background: #eefaf4;
            border: 1px solid #c7ebd5;
        }

        .auth-notice i {
            margin-top: 2px;
            flex-shrink: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .form-grid.two-col {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-grid.two-col {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #334155;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="number"],
        input[type="file"],
        textarea {
            width: 100%;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
            color: var(--text);
            font: inherit;
            font-size: 14px;
            padding: 12px 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1);
            background: #fff;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 0;
            background: transparent;
            color: #8492a6;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: color 180ms ease;
        }

        .toggle-password:hover {
            color: var(--primary-2);
        }

        .toggle-password svg {
            width: 18px;
            height: 18px;
            display: block;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .toggle-password .icon-show {
            display: none;
        }

        .toggle-password.is-visible .icon-show {
            display: block;
        }

        .toggle-password.is-visible .icon-hide {
            display: none;
        }

        .worker-box {
            padding: 0;
            border: none;
            border-radius: 0;
            background: transparent;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .skill-card {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #333;
            min-height: 48px;
        }

        .skill-card:hover {
            transform: translateY(-1px);
            border-color: #2a5298;
            background: white;
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.15);
        }

        .skill-card.is-selected {
            background: #eef4ff;
            border-color: #2a5298;
        }

        .skill-card input {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--primary-2);
        }

        .helper {
            margin-top: 8px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .primary-btn,
        .secondary-btn {
            border: 0;
            border-radius: 8px;
            padding: 13px 18px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .primary-btn {
            flex: 1 1 auto;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.4);
            margin-top: 10px;
        }

        .primary-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.4);
        }

        .secondary-btn {
            flex: 0 0 auto;
            background: transparent;
            color: #2a5298;
            border-top: 1px solid #e0e0e0;
            padding-top: 18px;
            margin-top: 18px;
        }

        .secondary-btn:hover {
            transform: none;
            box-shadow: none;
        }

        .login-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            flex-wrap: nowrap;
            white-space: nowrap;
            margin-top: 10px;
            color: var(--muted);
            font-size: 12px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        .login-link a {
            color: var(--primary-2);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        small.error-text {
            color: var(--danger);
            font-size: 13px;
        }

        .is-hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .card-inner {
                padding: 40px 24px 28px;
            }

            .type-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-grid.two-col {
                grid-template-columns: 1fr;
            }

            .skills-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .selector-title {
                font-size: 28px;
            }

            .brand-text h1,
            .panel-title {
                font-size: 28px;
            }

            .back-button {
                width: auto;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .login-link {
                flex-wrap: wrap;
                white-space: normal;
            }
        }
    </style>
</head>
<body>
<?php
    $skillOptions = $skillOptions ?? [];
    $oldSkills = old('skills');
    if (!is_array($oldSkills)) {
        $oldSkills = [];
    }
?>
    <div class="register-shell">
        <div class="register-card">
            <div class="card-inner">
                <div class="brand-row">
                    <div class="brand-mark"><i class="fas fa-link"></i></div>
                    <div class="brand-text">
                        <h1>SkillLink</h1>
                        <p>Create your account and start connecting</p>
                    </div>
                </div>

                <?php if (session()->has('error')): ?>
                    <div class="auth-notice error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?= esc(session('error')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('success')): ?>
                    <div class="auth-notice success">
                        <i class="fas fa-circle-check"></i>
                        <span><?= esc(session('success')) ?></span>
                    </div>
                <?php endif; ?>

                <div id="selector-screen" class="screen is-visible">
                    <h2 class="selector-title">Register As</h2>
                    <p class="selector-copy">
                        Choose the account type that matches how you want to use SkillLink.
                    </p>

                    <div class="type-grid">
                        <button type="button" class="type-card customer" data-select-type="customer">
                            <div class="type-icon"><i class="fas fa-user"></i></div>
                            <h2>Customer</h2>
                            <p>Book services, manage requests, and get started right away.</p>
                            <span class="mode-badge"><i class="fas fa-arrow-right"></i> Start as customer</span>
                        </button>

                        <button type="button" class="type-card worker" data-select-type="worker">
                            <div class="type-icon"><i class="fas fa-briefcase"></i></div>
                            <h2>Worker</h2>
                            <p>Submit your application with your skills and experience for review.</p>
                            <span class="mode-badge"><i class="fas fa-arrow-right"></i> Apply as worker</span>
                        </button>
                    </div>
                </div>

                <div id="form-screen" class="screen is-hidden">
                    <div class="panel-head">
                        <div>
                            <h2 id="form-title" class="panel-title">Customer Registration</h2>
                            <p id="form-subtitle" class="panel-subtitle">
                                Create your account with the essential details to log in and start using SkillLink.
                            </p>
                        </div>
                    </div>

                    <form action="<?= base_url('auth/doRegister') ?>" method="POST" enctype="multipart/form-data" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_type" id="user_type" value="<?= esc(old('user_type')) ?>">

                        <div class="form-grid two-col">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" value="<?= esc(old('first_name')) ?>" placeholder="John" required>
                                <?php if (isset($errors['first_name'])): ?><small class="error-text"><?= esc($errors['first_name']) ?></small><?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" value="<?= esc(old('last_name')) ?>" placeholder="Doe" required>
                                <?php if (isset($errors['last_name'])): ?><small class="error-text"><?= esc($errors['last_name']) ?></small><?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= esc(old('email')) ?>"
                                    placeholder="you@example.com"
                                    required
                                    pattern="^[A-Za-z0-9][A-Za-z0-9._-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
                                    title="Invalid email address. Allowed: letters, numbers, dots (not as first character), hyphens (-) and underscores (_)."
                                    oninvalid="this.setCustomValidity('Invalid email address. Allowed: letters, numbers, dots (not as first character), hyphens (-) and underscores (_).')"
                                    oninput="this.setCustomValidity('')"
                                >
                                <?php if (isset($errors['email'])): ?><small class="error-text"><?= esc($errors['email']) ?></small><?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="<?= esc(old('phone')) ?>"
                                    placeholder="11-digit mobile number"
                                    inputmode="numeric"
                                    maxlength="11"
                                    pattern="^(09\d{9}|\+639\d{8})$"
                                    title="Invalid phone number."
                                    oninvalid="this.setCustomValidity('Invalid phone number.')"
                                    oninput="this.setCustomValidity(''); if (this.value.startsWith('+')) { this.value = '+' + this.value.slice(1).replace(/\D/g, '').slice(0, 11); } else { this.value = this.value.replace(/\D/g, '').slice(0, 11); }"
                                >
                                <?php if (isset($errors['phone'])): ?><small class="error-text"><?= esc($errors['phone']) ?></small><?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="password">Password *</label>
                                <div class="password-field">
                                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                                    <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                                        <svg class="icon-show" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="icon-hide" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 3l18 18"></path>
                                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                            <path d="M9.5 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.2 4.1"></path>
                                            <path d="M6.6 6.6C4 8.1 2.6 10.6 2 12c0 0 3.5 7 10 7a10.8 10.8 0 0 0 5.4-1.4"></path>
                                        </svg>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?><small class="error-text"><?= esc($errors['password']) ?></small><?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="password_confirm">Confirm Password *</label>
                                <div class="password-field">
                                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Re-enter password" required>
                                    <button type="button" class="toggle-password" data-target="password_confirm" aria-label="Show password">
                                        <svg class="icon-show" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="icon-hide" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 3l18 18"></path>
                                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                            <path d="M9.5 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.2 4.1"></path>
                                            <path d="M6.6 6.6C4 8.1 2.6 10.6 2 12c0 0 3.5 7 10 7a10.8 10.8 0 0 0 5.4-1.4"></path>
                                        </svg>
                                    </button>
                                </div>
                                <?php if (isset($errors['password_confirm'])): ?><small class="error-text"><?= esc($errors['password_confirm']) ?></small><?php endif; ?>
                            </div>
                        </div>

                        <div id="customer-fields" class="form-grid mt-4 is-hidden">
                            <div class="form-group full">
                                <div class="worker-box">
                                    <label for="customer_note">Account Details</label>
                                    <p class="helper mb-0">Customer accounts are ready to use right after registration. You can log in immediately after submitting this form.</p>
                                </div>
                            </div>
                        </div>

                        <div id="worker-fields" class="worker-box mt-4 is-hidden">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="address">Address *</label>
                                    <textarea id="address" name="address" placeholder="Enter your full address"><?= esc(old('address')) ?></textarea>
                                    <?php if (isset($errors['address'])): ?><small class="error-text"><?= esc($errors['address']) ?></small><?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="experience_years">Years of Experience *</label>
                                    <input type="number" id="experience_years" name="experience_years" value="<?= esc(old('experience_years')) ?>" min="0" max="99" placeholder="0">
                                    <?php if (isset($errors['experience_years'])): ?><small class="error-text"><?= esc($errors['experience_years']) ?></small><?php endif; ?>
                                </div>

                                <div class="form-group full">
                                    <label class="d-block">Skills *</label>
                                    <div class="skills-grid" id="skills-grid">
                                        <?php foreach ($skillOptions as $value => $label): ?>
                                            <label class="skill-card">
                                                <input type="checkbox" name="skills[]" value="<?= esc($value) ?>" <?= in_array($value, $oldSkills, true) ? 'checked' : '' ?>>
                                                <span><?= esc($label) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="helper">Select the skills that match the services you provide.</p>
                                    <?php if (isset($errors['skills'])): ?><small class="error-text"><?= esc($errors['skills']) ?></small><?php endif; ?>
                                </div>

                                <div class="form-group full">
                                    <label for="resume_upload">Resume Upload</label>
                                    <input type="file" id="resume_upload" name="resume_upload" accept="application/pdf,.pdf">
                                    <p class="helper">Upload a valid PDF only. Other file types will be rejected.</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-4">
                            <button type="submit" id="submit-button" class="primary-btn">
                                <i class="fas fa-user-plus me-1"></i>
                                <span id="submit-label">Register</span>
                            </button>
                            <button type="button" id="back-button" class="secondary-btn is-hidden">
                                Back
                            </button>
                        </div>
                    </form>

                </div>
                <div class="login-link mt-4">
                    Already have an account? <a href="<?= base_url('auth/login') ?>">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const selectorScreen = document.getElementById('selector-screen');
            const formScreen = document.getElementById('form-screen');
            const userTypeInput = document.getElementById('user_type');
            const formTitle = document.getElementById('form-title');
            const formSubtitle = document.getElementById('form-subtitle');
            const submitLabel = document.getElementById('submit-label');
            const submitButton = document.getElementById('submit-button');
            const backButton = document.getElementById('back-button');
            const customerFields = document.getElementById('customer-fields');
            const workerFields = document.getElementById('worker-fields');
            const typeButtons = document.querySelectorAll('[data-select-type]');
            const skillCards = document.querySelectorAll('.skill-card');
            function setMode(mode) {
                const isWorker = mode === 'worker';

                userTypeInput.value = mode;
                selectorScreen.classList.add('is-hidden');
                selectorScreen.classList.remove('is-visible');
                formScreen.classList.remove('is-hidden');
                formScreen.classList.add('is-visible');

                formTitle.textContent = isWorker ? 'Worker Registration' : 'Customer Registration';
                formSubtitle.textContent = isWorker
                    ? 'Complete your application with your contact details, skills, and resume.'
                    : 'Create your account with the essential details to log in and start using SkillLink.';
                submitLabel.textContent = isWorker ? 'Submit Application' : 'Register';
                submitButton.querySelector('i').className = isWorker ? 'fas fa-paper-plane me-1' : 'fas fa-user-plus me-1';

                workerFields.classList.toggle('is-hidden', !isWorker);
                customerFields.classList.toggle('is-hidden', isWorker);
                backButton.classList.remove('is-hidden');

                const experience = document.getElementById('experience_years');
                const phone = document.getElementById('phone');
                const address = document.getElementById('address');
                const resume = document.getElementById('resume_upload');
                if (experience) experience.required = isWorker;
                if (phone) phone.required = isWorker;
                if (address) address.required = isWorker;
                if (resume) resume.required = false;

                skillCards.forEach((card) => {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    if (!checkbox) return;
                    card.classList.toggle('is-selected', checkbox.checked);
                });
            }

            typeButtons.forEach((button) => {
                button.addEventListener('click', () => setMode(button.getAttribute('data-select-type')));
            });

            const initialUserType = userTypeInput.value;
            if (initialUserType === 'customer' || initialUserType === 'worker') {
                setMode(initialUserType);
            }

            backButton.addEventListener('click', () => {
                userTypeInput.value = '';
                selectorScreen.classList.remove('is-hidden');
                selectorScreen.classList.add('is-visible');
                formScreen.classList.add('is-hidden');
                formScreen.classList.remove('is-visible');
                backButton.classList.add('is-hidden');
            });

            skillCards.forEach((card) => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                if (!checkbox) return;

                card.classList.toggle('is-selected', checkbox.checked);
                checkbox.addEventListener('change', () => {
                    card.classList.toggle('is-selected', checkbox.checked);
                });
            });

            document.querySelectorAll('.toggle-password').forEach((toggleButton) => {
                toggleButton.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    if (!input) return;

                    const show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    this.classList.toggle('is-visible', show);
                    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });

        })();
    </script>
</body>
</html>