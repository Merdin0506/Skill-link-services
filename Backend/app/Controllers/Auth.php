<?php

namespace App\Controllers;

use App\Models\UserModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Auth extends BaseController
{
    protected $session;
    protected $userModel;

    public function __construct()
    {
        $this->session = session();
        $this->userModel = new UserModel();
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

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'That email or password does not look right.');
            }

            if (($user['status'] ?? null) !== 'active') {
                log_message('notice', 'Login blocked: inactive account. email={email}, status={status}', [
                    'email' => $email,
                    'status' => (string) ($user['status'] ?? 'null'),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Your account is not active right now. Please contact support.');
            }

            $this->userModel->clearFailedLogins((int) $user['id']);

            // --- MFA Integration Start ---
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));
            
            // Log for debugging
            log_message('debug', 'Generating OTP for user: ' . $user['email'] . ' code: ' . $otp);

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

            // Send Email
            try {
                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    $mail = new PHPMailer(true);
                    
                    // SMTP Settings - FULL FIX
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('SMTP_USER'); 
                    $mail->Password   = getenv('SMTP_PASS'); 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                    $mail->Port       = 465;
                    
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    $mail->setFrom('racazaxyrl@gmail.com', 'SkillLink MFA');
                    $mail->addAddress($user['email']);
                    $mail->isHTML(true);
                    $mail->Subject = 'SkillLink Login Verification Code';
                    $mail->Body    = $this->generateGeminiMessage($otp, $user['email']);
                    
                    if ($mail->send()) {
                        return redirect()->to('/auth/verify-otp')->with('success', 'A 6-digit verification code has been sent to your email.');
                    }
                }
                return redirect()->to('/auth/verify-otp')->with('error', 'Failed to send verification email. Please try Resending.');
            } catch (\Throwable $e) {
                log_message('error', 'SMTP ERROR: ' . $e->getMessage());
                return redirect()->to('/auth/verify-otp')->with('error', 'We encountered an error sending your email. Please click Resend.');
            }
            // --- MFA Integration End ---
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
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        try {
            $email = (string) $this->request->getPost('email');

            if ($this->userModel->where('email', $email)->first()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'That email is already being used.');
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
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'We could not create your account. Please try again.');
            }

            // --- MFA Integration for Registration Start ---
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));
            
            $this->userModel->update($userId, [
                'otp' => $otp,
                'otp_expire' => $expire,
                'otp_attempts' => 0
            ]);

            // Set temporary session for OTP verification
            $this->session->set('temp_user_id', $userId);
            $this->session->set('temp_email', $email);

            // Send Email via PHPMailer
            try {
                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('SMTP_USER'); 
                    $mail->Password   = getenv('SMTP_PASS'); 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                    $mail->Port       = 465;
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    $mail->setFrom(getenv('SMTP_USER'), getenv('SMTP_FROM_NAME'));
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome! Verify Your SkillLink Account';
                    $mail->Body    = $this->generateGeminiMessage($otp, $email);
                    
                    if (@$mail->send()) {
                        return redirect()->to('/auth/verify-otp')->with('success', 'Registration successful! Please enter the code sent to your email.');
                    }
                }
                return redirect()->to('/auth/verify-otp')->with('error', 'Account created, but we couldn\'t send the verification email. Please try Resending.');
            } catch (\Throwable $e) {
                return redirect()->to('/auth/verify-otp')->with('error', 'Account created! Error sending email. Please use Resend OTP.');
            }
            // --- MFA Integration End ---
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'We could not create your account right now. Please try again.');
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

        $user = $this->userModel->find($userId);

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
            // Success: Clear OTP and log in
            $this->userModel->update($userId, [
                'otp' => null,
                'otp_expire' => null,
                'otp_attempts' => 0
            ]);

            $token = $this->createApiToken((int) $user['id'], (string) $user['user_type']);

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
                'api_token' => $token,
            ]);

            $this->session->remove('temp_user_id');
            $this->session->remove('temp_email');

            return redirect()->to('/dashboard')->with('success', 'Verification successful. Welcome!');
        } else {
            // Fail: Increment attempts
            $newAttempts = ($user['otp_attempts'] ?? 0) + 1;
            $this->userModel->update($userId, ['otp_attempts' => $newAttempts]);

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

        // Send Email (Reuse logic)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER'); 
            $mail->Password   = getenv('SMTP_PASS'); 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port       = 465;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(getenv('SMTP_USER'), getenv('SMTP_FROM_NAME'));
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = 'New Login OTP';
            $mail->Body    = "Your new code is: <b>$otp</b>";
            $mail->send();

            return redirect()->back()->with('success', 'A new OTP has been sent to your email.');
        } catch (Exception $e) {
            log_message('error', 'Resend SMTP ERROR: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to resend email. Please try again later.');
        }
    }

    private function generateGeminiMessage(string $otp, string $email): string
    {
        $apiKey = getenv('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return "Your verification code is: <b>$otp</b>. It expires in 5 minutes.";
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
        
        $prompt = "Write a very short, professional and welcoming security email for a user with email $email who is logging into SkillLink Services. 
                   The 6-digit verification code is $otp. 
                   Mention it expires in 5 minutes. 
                   Keep it very concise (max 3 sentences). 
                   Use HTML <b> tag for the code.";

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
                return "Your verification code is: <b>$otp</b>. It expires in 5 minutes.";
            }
            curl_close($ch);

            $result = json_decode($response, true);
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Your verification code is: <b>$otp</b>. It expires in 5 minutes.";
            
            // Siguraduhin na ang OTP ay nandoon sa text (Safety Check)
            if (strpos($text, $otp) === false) {
                return "Your verification code is: <b>$otp</b>. It expires in 5 minutes.";
            }
            
            return $text;
        } catch (\Exception $e) {
            return "Your verification code is: <b>$otp</b>. It expires in 5 minutes.";
        }
    }

    private function createApiToken(int $userId, string $userType): ?string
    {
        if (!class_exists('Firebase\\JWT\\JWT')) {
            log_message('warning', 'JWT class is missing. Skipping API token generation for userId={userId}', [
                'userId' => $userId,
            ]);

            return null;
        }

        $key = getenv('JWT_SECRET');
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
        ];

        return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/auth/login')->with('success', 'You have been logged out.');
    }
}
