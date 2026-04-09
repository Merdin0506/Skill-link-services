<?php

namespace App\Controllers;

use App\Models\UserModel;

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
                ->with('error', 'Login validation failed. Please check your inputs. [DBG-LOGIN-VALIDATION]');
        }

        try {
            $user = $this->userModel->where('email', $email)->first();
            log_message('debug', 'Login user lookup completed. email={email}, found={found}', [
                'email' => $email,
                'found' => $user ? 'yes' : 'no',
            ]);

            if (!$user || !password_verify($password, $user['password'])) {
                log_message('notice', 'Login failed: invalid credentials. email={email}', [
                    'email' => $email,
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid credentials [DBG-LOGIN-CREDS]');
            }

            if (($user['status'] ?? null) !== 'active') {
                log_message('notice', 'Login blocked: inactive account. email={email}, status={status}', [
                    'email' => $email,
                    'status' => (string) ($user['status'] ?? 'null'),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Account is not active [DBG-LOGIN-STATUS]');
            }

            $token = $this->createApiToken((int) $user['id'], (string) $user['user_type']);

            // Set session data for dashboard pages.
            $this->session->set([
                'user_id' => $user['id'],
                'role' => $user['user_type'],
                'user_role' => $user['user_type'], // Keep for backward compatibility
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

            // Confirm session write succeeded before redirect.
            if (!$this->session->has('user_id')) {
                log_message('error', 'Login failed: session write did not persist for email={email}', [
                    'email' => $email,
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Login failed due to session persistence issue. [DBG-LOGIN-SESSION]');
            }

            log_message('info', 'Login successful. userId={userId}, email={email}, role={role}', [
                'userId' => (int) $user['id'],
                'email' => $email,
                'role' => (string) $user['user_type'],
            ]);

            return redirect()->to('/dashboard')->with('success', 'Login successful!');
        } catch (\Throwable $e) {
            log_message('error', 'Login exception for email={email}. message={message} line={line}', [
                'email' => $email,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Login failed. Please try again. [DBG-LOGIN-EXCEPTION]');
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
                    ->with('error', 'Email already exists');
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
                    ->with('error', 'Registration failed. Please try again.');
            }

            $user = $this->userModel->find($userId);

            // Auto login after registration.
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
                'api_token' => $this->createApiToken((int) $user['id'], (string) $user['user_type']),
            ]);

            return redirect()->to('/dashboard')
                ->with('success', 'Registration successful! Welcome to Skill Link Services.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
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
        return redirect()->to('/auth/login')->with('success', 'Logged out successfully');
    }
}
