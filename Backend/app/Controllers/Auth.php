<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = session();
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

        $validation->setRules([
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Authenticate using database directly
        $user = $this->userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Check if user is active
            if ($user['status'] !== 'active') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Your account is not active. Please contact administrator.');
            }

            // Set session data
            $this->session->set([
                'user_id' => $user['id'],
                'user_role' => $user['user_type'],
                'email' => $user['email'],
                'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                'logged_in' => true
            ]);

            return redirect()->to('/dashboard')->with('success', 'Login successful!');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Invalid email or password');
    }

    /**
     * Process registration - connects to backend API
     */
    public function doRegister()
    {
        $validation = \Config\Services::validation();

        $validation->setRules([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'confirm_password' => 'required|matches[password]',
            'phone' => 'max_length[20]',
            'user_type' => 'required|in_list[customer,worker]',
            'address' => 'max_length[500]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        $data = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'phone' => $this->request->getPost('phone'),
            'user_type' => $this->request->getPost('user_type'),
            'address' => $this->request->getPost('address'),
            'status' => 'active'
        ];

        // Add worker-specific fields
        if ($this->request->getPost('user_type') === 'worker') {
            $data['skills'] = $this->request->getPost('skills');
            $data['experience_years'] = $this->request->getPost('experience_years') ?? 0;
        }

        try {
            $userId = $this->userModel->insert($data);

            if ($userId) {
                // Auto login after registration
                $user = $this->userModel->find($userId);
                
                $this->session->set([
                    'user_id' => $user['id'],
                    'user_role' => $user['user_type'],
                    'email' => $user['email'],
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'logged_in' => true
                ]);

                return redirect()->to('/dashboard')
                    ->with('success', 'Registration successful! Welcome to Skill Link Services.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Registration failed. Please try again.');
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
