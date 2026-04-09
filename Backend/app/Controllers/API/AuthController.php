<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController
{
    use ResponseTrait;

    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function register()
    {
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'phone' => 'max_length[20]',
            'user_type' => 'required|in_list[customer,worker]',
            'address' => 'max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getVar('first_name'),
            'last_name' => $this->request->getVar('last_name'),
            'email' => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'phone' => $this->request->getVar('phone'),
            'user_type' => $this->request->getVar('user_type'),
            'address' => $this->request->getVar('address'),
            'status' => 'active'
        ];

        if ($this->request->getVar('user_type') === 'worker') {
            $data['skills'] = $this->request->getVar('skills');
            $data['experience_years'] = $this->request->getVar('experience_years');
            $data['commission_rate'] = 20.00; // Default commission rate
        }

        try {
            $userId = $this->userModel->insert($data);
            
            $user = $this->userModel->find($userId);
            unset($user['password']);

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return $this->fail('Registration failed: ' . $e->getMessage());
        }
    }

    public function login()
    {
        $rules = [
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            return $this->fail('Invalid credentials');
        }

        if (!password_verify($password, $user['password'])) {
            return $this->fail('Invalid credentials');
        }

        if ($user['status'] !== 'active') {
            return $this->fail('Account is not active');
        }

        $key = getenv('JWT_SECRET');
        $payload = [
            'iss' => 'skilllink_backend',
            'sub' => $user['id'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours
            'user_type' => $user['user_type']
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        unset($user['password']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    public function profile()
    {
        $userId = $this->request->authUserId;
        $user   = $this->request->authUser;

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        unset($user['password']);

        // Add additional data based on user type
        if ($user['user_type'] === 'worker') {
            $user['average_rating'] = $this->userModel->getWorkerRating($userId);
            $user['total_earnings'] = $this->userModel->getWorkerEarnings($userId);
        }

        return $this->respond([
            'status' => 'success',
            'data'   => $user,
        ]);
    }

    public function updateProfile()
    {
        $userId = $this->request->authUserId;

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'phone'      => 'max_length[20]',
            'address'    => 'max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getVar('first_name'),
            'last_name'  => $this->request->getVar('last_name'),
            'phone'      => $this->request->getVar('phone'),
            'address'    => $this->request->getVar('address'),
        ];

        if (($this->request->authUser['user_type'] ?? '') === 'worker') {
            $data['skills']           = $this->request->getVar('skills');
            $data['experience_years'] = $this->request->getVar('experience_years');
        }

        $this->userModel->update($userId, $data);
        $user = $this->userModel->find($userId);
        unset($user['password']);

        return $this->respond([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'data'    => $user,
        ]);
    }

    public function changePassword()
    {
        $userId = $this->request->authUserId;

        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Re-fetch from DB to get the hashed password for verification
        $user = $this->userModel->find($userId);
        if (!password_verify($this->request->getVar('current_password'), $user['password'])) {
            return $this->fail('Current password is incorrect');
        }

        $this->userModel->changePassword($userId, $this->request->getVar('new_password'));

        return $this->respond([
            'status'  => 'success',
            'message' => 'Password changed successfully',
        ]);
    }

    public function logout()
    {
        // In a real implementation, you might want to blacklist the token
        // For now, we'll just return a success response
        return $this->respond([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }
}
