<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background-color: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .hidden {
            display: none;
        }

        .worker-fields {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 20px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        small {
            display: block;
            margin-top: 5px;
            color: #c33;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SkillLink</h1>
        <p class="subtitle">Create your account</p>

        <?php if (session()->has('error')): ?>
            <div class="error-message">
                <?= session('error') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->has('success')): ?>
            <div class="success-message">
                <?= session('success') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('auth/doRegister') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        value="<?= old('first_name') ?>"
                        required
                    >
                    <?php if (isset($errors['first_name'])): ?>
                        <small><?= $errors['first_name'] ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        value="<?= old('last_name') ?>"
                        required
                    >
                    <?php if (isset($errors['last_name'])): ?>
                        <small><?= $errors['last_name'] ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= old('email') ?>"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <small><?= $errors['email'] ?></small>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <small><?= $errors['password'] ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required
                    >
                    <?php if (isset($errors['password_confirm'])): ?>
                        <small><?= $errors['password_confirm'] ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?= old('phone') ?>"
                        placeholder="+1234567890"
                    >
                    <?php if (isset($errors['phone'])): ?>
                        <small><?= $errors['phone'] ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="user_type">Account Type *</label>
                    <select id="user_type" name="user_type" required onchange="toggleWorkerFields()">
                        <option value="">Select account type</option>
                        <option value="customer" <?= old('user_type') == 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="worker" <?= old('user_type') == 'worker' ? 'selected' : '' ?>>Skilled Worker</option>
                    </select>
                    <?php if (isset($errors['user_type'])): ?>
                        <small><?= $errors['user_type'] ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea 
                    id="address" 
                    name="address" 
                    placeholder="Enter your full address"
                ><?= old('address') ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <small><?= $errors['address'] ?></small>
                <?php endif; ?>
            </div>

            <!-- Worker-specific fields -->
            <div id="worker-fields" class="worker-fields hidden">
                <div class="form-group">
                    <label for="skills">Skills *</label>
                    <textarea 
                        id="skills" 
                        name="skills" 
                        placeholder="Enter your skills (e.g., electrical, plumbing, carpentry)"
                    ><?= old('skills') ?></textarea>
                    <?php if (isset($errors['skills'])): ?>
                        <small><?= $errors['skills'] ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="experience_years">Years of Experience *</label>
                    <input 
                        type="number" 
                        id="experience_years" 
                        name="experience_years" 
                        value="<?= old('experience_years') ?>"
                        min="0"
                        max="99"
                    >
                    <?php if (isset($errors['experience_years'])): ?>
                        <small><?= $errors['experience_years'] ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="<?= base_url('auth/login') ?>">Login here</a>
        </div>
    </div>

    <script>
        function toggleWorkerFields() {
            const userType = document.getElementById('user_type').value;
            const workerFields = document.getElementById('worker-fields');
            const skillsInput = document.getElementById('skills');
            const experienceInput = document.getElementById('experience_years');

            if (userType === 'worker') {
                workerFields.classList.remove('hidden');
                skillsInput.required = true;
                experienceInput.required = true;
            } else {
                workerFields.classList.add('hidden');
                skillsInput.required = false;
                experienceInput.required = false;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', toggleWorkerFields);
    </script>
</body>
</html>
