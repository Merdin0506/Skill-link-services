<?php

namespace App\Controllers;

use CodeIgniter\HTTP\Client;

class Auth extends BaseController
{
    protected $session;
    protected $client;
    protected $apiUrl;

    public function __construct()
    {
        $this->session = session();
        $this->client = service('curlrequest');
        $this->apiUrl = base_url('api/auth');
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

        try {
            // Call API login endpoint
            $response = $this->client->request('post', $this->apiUrl . '/login', [
                'json' => [
                    'email' => $this->request->getPost('email'),
                    'password' => $this->request->getPost('password')
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && isset($result['data']['user'])) {
                $user = $result['data']['user'];
                $token = $result['data']['token'];

                // Set session data
                $this->session->set([
                    'user_id' => $user['id'],
                    'user_role' => $user['user_type'],
                    'email' => $user['email'],
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'logged_in' => true,
                    'api_token' => $token
                ]);

                return redirect()->to('/dashboard')->with('success', 'Login successful!');
            } else {
                $errorMsg = isset($result['message']) ? $result['message'] : 'Invalid credentials';
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMsg);
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Login failed. Please try again.');
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
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'user_type' => 'required|in_list[customer,worker]',
            'phone' => 'max_length[20]',
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
            $postData = [
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'email' => $this->request->getPost('email'),
                'password' => $this->request->getPost('password'),
                'user_type' => $this->request->getPost('user_type'),
                'phone' => $this->request->getPost('phone'),
                'address' => $this->request->getPost('address')
            ];

            // Add worker-specific fields
            if ($this->request->getPost('user_type') === 'worker') {
                $postData['skills'] = $this->request->getPost('skills');
                $postData['experience_years'] = $this->request->getPost('experience_years');
            }

            // Call API register endpoint
            $response = $this->client->request('post', $this->apiUrl . '/register', [
                'json' => $postData
            ]);

            $result = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 201 && isset($result['data'])) {
                $user = $result['data'];

                // Auto login after registration
                $this->session->set([
                    'user_id' => $user['id'],
                    'user_role' => $user['user_type'],
                    'email' => $user['email'],
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'logged_in' => true
                ]);

                return redirect()->to('/dashboard')
                    ->with('success', 'Registration successful! Welcome to Skill Link Services.');
            } else {
                $errorMsg = isset($result['message']) ? $result['message'] : 'Registration failed. Please try again.';
                if (isset($result['data']) && is_array($result['data'])) {
                    $errorMsg = implode(', ', array_values($result['data']));
                }
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMsg);
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
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
