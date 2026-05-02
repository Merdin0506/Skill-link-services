<?php

namespace App\Controllers;

use App\Libraries\ActivityLogger;
use App\Libraries\AuditLogger;
use App\Controllers\SecurityController;
use App\Models\UserModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Auth extends BaseController
{
    protected $session;
    protected $userModel;
    protected $sessionTracker;
    protected ActivityLogger $activityLogger;

    public function __construct()
    {
        $this->session = session();
        $this->userModel = new UserModel();
        $this->sessionTracker = service('sessiontracker');
        $this->activityLogger = new ActivityLogger();
    }

    /**
     * Show login page
     */
    public function login()
    {
        if ($this->session->has('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    /**
     * Show registration page
     */
    public function register()
    {
        if ($this->session->has('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/register');
    }

    /**
     * Process login - connects to backend API
     */
    public function doLogin()
    {
        $validation = \Config\Services::validation();
        $email = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        log_message('debug', 'Login attempt started. email={email}, ip={ip}', [
            'email' => $email,
            'ip' => $this->request->getIPAddress(),
        ]);

        $validation->setRules([
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]',
            'password' => 'required|min_length[8]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            log_message('debug', 'Login validation failed. email={email}, passwordLength={passwordLength}, errors={errors}', [
                'email' => $email,
                'passwordLength' => strlen($password),
                'errors' => json_encode($errors),
            ]);
            $this->activityLogger->record('auth', 'login_attempt', 'validation_failed', null, null, [
                'email' => $email,
                'errors' => $errors,
            ], 'web');
            
            // Also log to security events system
            $securityController = new SecurityController();
            $securityController->logEvent(
                'login_failed',
                'medium',
                'Login validation failed: ' . json_encode($errors),
                null,
                $email,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()
            );

            return redirect()->back()
                ->withInput()
                ->with('errors', $errors)
                ->with('error', 'Please check your login details and try again.');
        }

        try {
            $user = $this->userModel->where('email', $email)->first();
            log_message('debug', 'Login user lookup completed. email={email}, found={found}', [
                'email' => $email,
                'found' => $user ? 'yes' : 'no',
            ]);

            if ($user && $this->userModel->isLocked($user)) {
                $this->activityLogger->record('auth', 'login_attempt', 'locked', null, (int) $user['id'], [
                    'email' => $email,
                    'locked_until' => $user['locked_until'] ?? null,
                ], 'web');

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Too many login tries for now. Please wait a bit and try again.');
            }

            if (!$user || !password_verify($password, $user['password'])) {
                if ($user) {
                    $this->userModel->recordFailedLogin((int) $user['id']);
                }

                log_message('notice', 'Login failed: invalid credentials. email={email}', [
                    'email' => $email,
                ]);
                
                // Also log to security events system
                $securityController = new SecurityController();
                $securityController->logEvent(
                    'login_failed',
                    'medium',
                    'Invalid credentials - login attempt failed',
                    null,
                    $email,
                    $this->request->getIPAddress(),
                    $this->request->getUserAgent()
                );
                $this->activityLogger->record('auth', 'login_attempt', 'failed', null, $user ? (int) $user['id'] : null, [
                    'email' => $email,
                    'reason' => 'invalid_credentials',
                ], 'web');

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'That email or password does not look right.');
            }

            if (($user['status'] ?? null) !== 'active') {
                log_message('notice', 'Login blocked: inactive account. email={email}, status={status}', [
                    'email' => $email,
                    'status' => (string) ($user['status'] ?? 'null'),
                ]);
                $this->activityLogger->record('auth', 'login_attempt', 'blocked', null, (int) $user['id'], [
                    'email' => $email,
                    'reason' => 'inactive_account',
                    'status' => (string) ($user['status'] ?? 'null'),
                ], 'web');

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Your account is not active right now. Please contact support.');
            }

            $this->userModel->clearFailedLogins((int) $user['id']);

            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            try {
                $this->userModel->update($user['id'], [
                    'otp' => $otp,
                    'otp_expire' => $expire,
                    'otp_attempts' => 0
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Database update failed for OTP: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Database error: Could not save OTP code.');
            }

            $this->session->set('temp_user_id', $user['id']);
            $this->session->set('temp_email', $user['email']);

            try {
                if ($this->sendOtpEmail($user['email'], $otp, 'login')) {
                    return redirect()->to('/auth/verify-otp')->with('success', 'A 6-digit verification code has been sent to your email.');
                }

                return redirect()->to('/auth/verify-otp')->with('error', 'Failed to send verification email. Please try Resending.');
            } catch (\Throwable $e) {
                log_message('error', 'SMTP ERROR: ' . $e->getMessage());
                return redirect()->to('/auth/verify-otp')->with('error', 'We encountered an error sending your email. Please click Resend.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Login exception for email={email}. message={message} line={line}', [
                'email' => $email,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'We could not log you in right now. Please try again in a moment.');
        }
    }

    /**
     * Process registration - connects to backend API
     */
    public function doRegister()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'user_type' => 'required|in_list[customer,worker]',
            'phone' => 'permit_empty|max_length[20]|regex_match[/^\+?[0-9]+$/]',
            'address' => 'max_length[500]'
        ];

        // Add worker-specific validation
        if ($this->request->getPost('user_type') === 'worker') {
            $rules['skills'] = 'required';
            $rules['experience_years'] = 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[99]';
        }

        $validation->setRules($rules);

        if (!$validation->withRequest($this->request)->run()) {
            $this->activityLogger->record('account', 'user_registration', 'validation_failed', null, null, [
                'email' => $this->request->getPost('email'),
                'errors' => $validation->getErrors(),
            ], 'web');

            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $email = (string) $this->request->getPost('email');

            if ($this->userModel->where('email', $email)->first()) {
                throw new \Exception('That email is already being used.');
            }

            $postData = [
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'email' => $email,
                'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'user_type' => $this->request->getPost('user_type'),
                'phone' => $this->request->getPost('phone'),
                'address' => $this->request->getPost('address'),
                'status' => 'active',
                'password_changed_at' => date('Y-m-d H:i:s'),
            ];

            // Add worker-specific fields
            if ($this->request->getPost('user_type') === 'worker') {
                $postData['skills'] = $this->request->getPost('skills');
                $postData['experience_years'] = $this->request->getPost('experience_years');
                $postData['commission_rate'] = 20.00;
            }

            $userId = $this->userModel->insert($postData);
            if (!$userId) {
                throw new \Exception('We could not create your account. Please try again.');
            }

            $this->activityLogger->record('account', 'user_registered', 'success', (int) $userId, (int) $userId, [
                'created_fields' => $this->activityLogger->changedFields([], $postData, array_keys($postData)),
            ], 'web');

            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));
            
            if (!$this->userModel->update($userId, [
                'otp' => $otp,
                'otp_expire' => $expire,
                'otp_attempts' => 0
            ])) {
                throw new \Exception('Failed to generate verification code.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            $this->session->set('temp_user_id', $userId);
            $this->session->set('temp_email', $email);

            try {
                if ($this->sendOtpEmail($email, $otp, 'register')) {
                    return redirect()->to('/auth/verify-otp')->with('success', 'Registration successful! Please enter the code sent to your email.');
                }
                return redirect()->to('/auth/verify-otp')->with('error', 'Account created, but we couldn\'t send the verification email. Please try Resending.');
            } catch (\Throwable $e) {
                return redirect()->to('/auth/verify-otp')->with('error', 'Account created! Error sending email. Please use Resend OTP.');
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'We could not create your account right now. Please try again.');
        }
    }

    /**
     * Show OTP verification page
     */
    public function verifyOtp()
    {
        if (!$this->session->has('temp_user_id')) {
            return redirect()->to('/auth/login');
        }

        return view('auth/verify_otp');
    }

    /**
     * Process OTP verification
     */
    public function doVerifyOtp()
    {
        if (!$this->session->has('temp_user_id')) {
            return redirect()->to('/auth/login');
        }

        $userId = $this->session->get('temp_user_id');
        $otpInput = (string) $this->request->getPost('otp');

        $db = \Config\Database::connect();
        
        // Use direct query instead of UserModel->find() to bypass events
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

        if (!$user) {
            return redirect()->to('/auth/login');
        }

        // Security Checks
        if (($user['otp_attempts'] ?? 0) >= 3) {
            return redirect()->to('/auth/login')->with('error', 'Max attempts reached. Please login again.');
        }

        if ($user['otp_expire'] && strtotime((string) $user['otp_expire']) < time()) {
            return redirect()->to('/auth/login')->with('error', 'OTP has expired. Please login again.');
        }

        if ($user['otp'] === $otpInput && !empty($user['otp'])) {
            $db->transStart();

            try {
                // Direct update
                $db->table('users')->where('id', $userId)->update([
                    'otp' => null,
                    'otp_expire' => null,
                    'otp_attempts' => 0,
                    'email_verified_at' => $user['email_verified_at'] ?: date('Y-m-d H:i:s'),
                ]);

                $this->session->set([
                    'user_id' => $user['id'],
                    'role' => $user['user_type'],
                    'user_role' => $user['user_type'],
                    'email' => $user['email'],
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'user' => [
                        'id' => $user['id'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'user_type' => $user['user_type'],
                    ],
                    'logged_in' => true,
                ]);

                /* Temporarily bypassed for debugging ACID transactions
                $trackedSessionKey = $this->sessionTracker->startWebSession($user);
                $token = $this->createApiToken((int) $user['id'], (string) $user['user_type'], $trackedSessionKey);
                
                $this->session->set('tracked_session_key', $trackedSessionKey);
                $this->session->set('api_token', $token);
                
                $this->activityLogger->record('auth', 'login_attempt', 'success', (int) $user['id'], (int) $user['id'], [
                    'email' => $user['email'],
                    'session_type' => 'web',
                ], 'web', $trackedSessionKey);
                */

                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \Exception('Transaction failed');
                }

                $this->session->remove('temp_user_id');
                $this->session->remove('temp_email');

                // Record successful login in security events.
                $securityController = new SecurityController();
                $securityController->logEvent(
                    'login_success',
                    'low',
                    'User login verified via OTP',
                    (int) $user['id'],
                    (string) $user['email']
                );

                // Explicitly save session before redirect
                session_write_close();

                return redirect()->to('/dashboard')->with('success', 'Verification successful. Welcome!');
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', 'Login Error: ' . $e->getMessage());
                return redirect()->to('/auth/login')->with('error', 'System error during login.');
            }
        } else {
            $newAttempts = ($user['otp_attempts'] ?? 0) + 1;
            $db->table('users')->where('id', $userId)->update(['otp_attempts' => $newAttempts]);
            return redirect()->back()->with('error', 'Invalid OTP code. Tries left: ' . (3 - $newAttempts));
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp()
    {
        if (!$this->session->has('temp_user_id')) {
            return redirect()->to('/auth/login');
        }

        $userId = $this->session->get('temp_user_id');
        $user = $this->userModel->find($userId);

        $otp = sprintf("%06d", mt_rand(0, 999999));
        $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $this->userModel->update($userId, [
            'otp' => $otp,
            'otp_expire' => $expire,
            'otp_attempts' => 0
        ]);

        try {
            $this->sendOtpEmail($user['email'], $otp, 'resend');

            return redirect()->back()->with('success', 'A new OTP has been sent to your email.');
        } catch (Exception $e) {
            log_message('error', 'Resend SMTP ERROR: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to resend email. Please try again later.');
        }
    }

    private function sendOtpEmail(string $recipientEmail, string $otp, string $context): bool
    {
        if (!class_exists(PHPMailer::class)) {
            log_message('error', 'PHPMailer is not installed.');
            return false;
        }

        $smtpUser = $this->getEnvValue('SMTP_USER', 'email.SMTPUser');
        $smtpPass = $this->normalizeSmtpPassword($this->getEnvValue('SMTP_PASS', 'email.SMTPPass'));
        $fromName = $this->getEnvValue('SMTP_FROM_NAME', 'email.fromName') ?: 'SkillLink Services';

        if (!$smtpUser || !$smtpPass) {
            log_message('error', 'SMTP credentials are missing for OTP email sending.');
            return false;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($smtpUser, $fromName);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = match ($context) {
            'register' => 'Verify your SkillLink account',
            'login' => 'SkillLink login verification code',
            default => 'Your new SkillLink verification code',
        };
        $mail->Body = $this->generateOtpEmailMessage($otp, $recipientEmail, $context);
        $mail->AltBody = "Your SkillLink verification code is {$otp}. It expires in 5 minutes.";

        return $mail->send();
    }

    private function getEnvValue(string $primaryKey, ?string $fallbackKey = null): ?string
    {
        $value = getenv($primaryKey);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if ($fallbackKey !== null) {
            $fallbackValue = getenv($fallbackKey);
            if (is_string($fallbackValue) && trim($fallbackValue) !== '') {
                return trim($fallbackValue);
            }
        }

        return null;
    }

    private function normalizeSmtpPassword(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $value);

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    private function generateOtpEmailMessage(string $otp, string $email, string $context): string
    {
        $apiKey = getenv('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return $this->buildDefaultOtpEmail($otp, $context);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
        $action = $context === 'register' ? 'verifying a newly created SkillLink account' : 'logging into SkillLink Services';
        $prompt = "Write a very short, professional and welcoming security email for a user with email $email who is $action. 
                   The 6-digit verification code is $otp. 
                   Mention it expires in 5 minutes. 
                   Keep it very concise (max 3 sentences). 
                   Use HTML with a short greeting and the <b> tag for the code.";

        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout para hindi mabagal
            
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                return $this->buildDefaultOtpEmail($otp, $context);
            }
            curl_close($ch);

            $result = json_decode($response, true);
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? $this->buildDefaultOtpEmail($otp, $context);
            
            if (strpos($text, $otp) === false) {
                return $this->buildDefaultOtpEmail($otp, $context);
            }
            
            return $text;
        } catch (\Exception $e) {
            return $this->buildDefaultOtpEmail($otp, $context);
        }
    }

    private function buildDefaultOtpEmail(string $otp, string $context): string
    {
        $intro = $context === 'register'
            ? 'Thanks for creating your SkillLink account.'
            : 'We received a request to verify your SkillLink sign-in.';

        return "<p>{$intro}</p><p>Your verification code is <b>{$otp}</b>.</p><p>This code expires in 5 minutes.</p>";
    }

    private function createApiToken(int $userId, string $userType, ?string $sessionKey = null): ?string
    {
        if (!class_exists('Firebase\\JWT\\JWT')) {
            log_message('warning', 'JWT class is missing. Skipping API token generation for userId={userId}', [
                'userId' => $userId,
            ]);

            return null;
        }

        $key = getenv('JWT_SECRET') ?: 'skilllink_default_secret_key_2026';
        if (!$key) {
            log_message('warning', 'JWT_SECRET is not configured. Skipping API token generation for userId={userId}', [
                'userId' => $userId,
            ]);

            return null;
        }

        $payload = [
            'iss' => 'skilllink_backend',
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
            'user_type' => $userType,
            'sid' => $sessionKey,
        ];

        return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Logout
     */
    public function logout()
    {
        $userId = $this->session->get('user_id');
        $email = (string) ($this->session->get('email') ?? '');

        // Record logout before session teardown.
        $securityController = new SecurityController();
        $securityController->logEvent(
            'logout',
            'low',
            'User logged out from web session',
            is_numeric($userId) ? (int) $userId : null,
            $email !== '' ? $email : null
        );

        $trackedSessionKey = $this->session->get('tracked_session_key');
        $this->activityLogger->record('auth', 'logout', 'success', is_numeric($userId) ? (int) $userId : null, is_numeric($userId) ? (int) $userId : null, [
            'session_type' => 'web',
        ], 'web', is_string($trackedSessionKey) ? $trackedSessionKey : null);
        $this->sessionTracker->endWebSession();
        $this->session->destroy();
        return redirect()->to('/auth/login')->with('success', 'You have been logged out.');
    }
}
