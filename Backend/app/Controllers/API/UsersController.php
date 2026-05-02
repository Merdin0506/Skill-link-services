<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Libraries\ActivityLogger;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class UsersController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected ActivityLogger $activityLogger;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityLogger = new ActivityLogger();
    }

    public function index()
    {
        $userType = $this->request->getVar('user_type');
        $status = $this->request->getVar('status') ?? 'active';
        $limit = $this->getPositiveIntParam('limit', 50);

        if ($userType) {
            $users = $this->userModel
                ->where('user_type', $userType)
                ->where('status', $status)
                ->limit($limit)
                ->findAll();
        } else {
            $users = $this->userModel
                ->where('status', $status)
                ->limit($limit)
                ->findAll();
        }

        // Remove passwords from response
        foreach ($users as &$user) {
            unset($user['password']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function show($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        unset($user['password']);

        // Add additional data based on user type only for admins
        $callerRole = $this->request->authUserRole ?? null;
        if ($user['user_type'] === 'worker' && ($callerRole === 'admin' || $callerRole === 'super_admin')) {
            $user['average_rating'] = $this->userModel->getWorkerRating($id);
            $user['total_earnings'] = $this->userModel->getWorkerEarnings($id);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function workers()
    {
        $status = $this->request->getVar('status') ?? 'active';
        $skill = $this->request->getVar('skill');
        $limit = $this->getPositiveIntParam('limit', 50);

        if ($skill) {
            $workers = $this->userModel->getWorkersBySkill($skill);
        } else {
            $workers = $this->userModel->getWorkers($status);
        }

        // Remove passwords and add ratings only if caller is admin
        $callerRole = $this->request->authUserRole ?? null;
        foreach ($workers as &$worker) {
            unset($worker['password']);
            if ($callerRole === 'admin' || $callerRole === 'super_admin') {
                $worker['average_rating'] = $this->userModel->getWorkerRating($worker['id']);
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => $workers
        ]);
    }

    public function customers()
    {
        $status = $this->request->getVar('status') ?? 'active';
        $limit = $this->getPositiveIntParam('limit', 50);

        $customers = $this->userModel->getCustomers($status);

        // Remove passwords
        foreach ($customers as &$customer) {
            unset($customer['password']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    public function adminStaff()
    {
        $status = $this->request->getVar('status') ?? 'active';
        $limit = $this->getPositiveIntParam('limit', 50);

        $staff = $this->userModel->getAdminStaff($status);

        // Remove passwords
        foreach ($staff as &$member) {
            unset($member['password']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $staff
        ]);
    }

    public function update($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        // Enforce a single super admin account.
        $requestedRole = $this->request->getVar('user_type');
        if (($user['user_type'] ?? '') === 'super_admin' && $requestedRole && $requestedRole !== 'super_admin') {
            return $this->fail('The super admin role cannot be changed');
        }
        if (($user['user_type'] ?? '') !== 'super_admin' && $requestedRole === 'super_admin') {
            return $this->fail('Only one super admin is allowed');
        }

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]|regex_match[/^[a-zA-Z\s]+$/]',
            'last_name'  => 'required|min_length[2]|max_length[100]|regex_match[/^[a-zA-Z\s]+$/]',
            'phone'      => 'max_length[20]',
            'address'    => 'max_length[500]',
            'status'     => 'required|in_list[active,inactive,suspended]'
        ];

        if ($this->request->getVar('email') && $this->request->getVar('email') !== $user['email']) {
            $rules['email'] = 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]|is_unique[users.email]';
        }

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getVar('first_name'),
            'last_name' => $this->request->getVar('last_name'),
            'phone' => $this->request->getVar('phone'),
            'address' => $this->request->getVar('address'),
            'status' => $this->request->getVar('status')
        ];

        if ($this->request->getVar('email')) {
            $data['email'] = $this->request->getVar('email');
        }

        // Worker-specific fields
        if ($user['user_type'] === 'worker') {
            if ($this->request->getVar('skills')) {
                $data['skills'] = $this->request->getVar('skills');
            }
            if ($this->request->getVar('experience_years')) {
                $data['experience_years'] = $this->request->getVar('experience_years');
            }
            if ($this->request->getVar('commission_rate')) {
                $data['commission_rate'] = $this->request->getVar('commission_rate');
            }
        }

        try {
            $this->userModel->update($id, $data);
            $updatedUser = $this->userModel->find($id);
            unset($updatedUser['password']);
            $this->activityLogger->record('account', 'user_updated', 'success', $this->currentActorId(), (int) $id, [
                'changed_fields' => $this->activityLogger->changedFields($user, $data, array_keys($data)),
            ], 'api', $this->request->authSessionKey ?? null);

            return $this->respond([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update user: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        // Protect super admin accounts from deletion.
        $isSuperAdmin = (($user['user_type'] ?? '') === 'super_admin');
        if ($isSuperAdmin) {
            return $this->fail('Super admin account cannot be deleted');
        }

        try {
            $this->userModel->delete($id);
            $this->activityLogger->record('account', 'user_archived', 'success', $this->currentActorId(), (int) $id, [
                'target_email' => $user['email'] ?? null,
                'target_role' => $user['user_type'] ?? null,
            ], 'api', $this->request->authSessionKey ?? null);

            return $this->respond([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete user: ' . $e->getMessage());
        }
    }

    public function dashboard($userId = null)
    {
        if (!$userId) {
            return $this->fail('User ID is required');
        }

        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $dashboardData = $this->userModel->getDashboardData($user['user_type'], $userId);

        return $this->respond([
            'status' => 'success',
            'data' => $dashboardData
        ]);
    }

    public function statistics()
    {
        $stats = [
            'total_users' => $this->userModel->countAll(),
            'total_workers' => $this->userModel->where('user_type', 'worker')->countAllResults(),
            'total_customers' => $this->userModel->where('user_type', 'customer')->countAllResults(),
            'total_admin_staff' => $this->userModel->whereIn('user_type', ['super_admin', 'admin', 'finance'])->countAllResults(),
            'active_users' => $this->userModel->where('status', 'active')->countAllResults(),
            'inactive_users' => $this->userModel->where('status', 'inactive')->countAllResults(),
            'suspended_users' => $this->userModel->where('status', 'suspended')->countAllResults()
        ];

        return $this->respond([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function search()
    {
        $query = $this->request->getVar('q');
        $userType = $this->request->getVar('user_type');
        $limit = $this->getPositiveIntParam('limit', 20);

        if (!$query) {
            return $this->fail('Search query is required');
        }

        $users = $this->userModel
            ->like('first_name', $query)
            ->orLike('last_name', $query)
            ->orLike('email', $query)
            ->when($userType, function($query, $userType) {
                return $query->where('user_type', $userType);
            })
            ->limit($limit)
            ->findAll();

        // Remove passwords
        foreach ($users as &$user) {
            unset($user['password']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function updateProfileImage($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $rules = [
            'profile_image' => 'required|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        try {
            $success = $this->userModel->updateProfileImage($id, $this->request->getVar('profile_image'));

            if ($success) {
                $this->activityLogger->record('account', 'profile_image_updated', 'success', $this->currentActorId(), (int) $id, [
                    'profile_image' => $this->request->getVar('profile_image'),
                ], 'api', $this->request->authSessionKey ?? null);

                return $this->respond([
                    'status' => 'success',
                    'message' => 'Profile image updated successfully'
                ]);
            } else {
                return $this->fail('Failed to update profile image');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to update profile image: ' . $e->getMessage());
        }
    }

    private function getPositiveIntParam(string $name, int $default): int
    {
        $value = (int) $this->request->getVar($name);

        return $value > 0 ? $value : $default;
    }

    private function currentActorId(): ?int
    {
        $userId = $this->request->authUserId ?? null;

        return is_numeric($userId) ? (int) $userId : null;
    }
}
