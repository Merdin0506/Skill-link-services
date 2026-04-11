<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: 0 10px 30px rgba(30, 60, 114, 0.3);
        }

        h1 {
            color: #333;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #999;
            font-size: 15px;
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .password-field {
            position: relative;
        }

        .password-field input[type="password"],
        .password-field input[type="text"] {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            top: 0;
            bottom: 0;
            right: 12px;
            margin: auto 0;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
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

        .toggle-password:hover {
            color: #2a5298;
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #2a5298;
            background: white;
            box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1);
        }

        .auth-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.45;
        }

        .auth-notice.error {
            color: #b9382b;
            background: #fff3f1;
            border: 1px solid #f3c4bd;
        }

        .auth-notice.success {
            color: #157347;
            background: #eefaf3;
            border: 1px solid #bfe7cf;
        }

        .auth-notice i {
            font-size: 15px;
            flex-shrink: 0;
        }

        button {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }

        small {
            display: block;
            margin-top: 5px;
            color: #e74c3c;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php $errors = session('errors') ?? []; ?>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-tools"></i>
            </div>
            <h1>SkillLink</h1>
            <p class="subtitle">Connect with skilled professionals</p>
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

        <form action="<?= base_url('auth/doLogin') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= old('email') ?>"
                    placeholder="Enter your email"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <small><?= $errors['email'] ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                    >
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
                <?php if (isset($errors['password'])): ?>
                    <small><?= $errors['password'] ?></small>
                <?php endif; ?>
            </div>

            <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="<?= base_url('auth/register') ?>">Register here</a>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function (toggleButton) {
            toggleButton.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const show = input.type === 'password';

                input.type = show ? 'text' : 'password';
                this.classList.toggle('is-visible', show);
                this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
