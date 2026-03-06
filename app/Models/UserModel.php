<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'user_type',
        'status',
        'skills',
        'experience_years',
        'commission_rate',
        'profile_image',
        'email_verified_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'first_name' => 'required|min_length[2]|max_length[100]',
        'last_name' => 'required|min_length[2]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'user_type' => 'required|in_list[owner,admin,cashier,worker,customer]',
        'status' => 'required|in_list[active,inactive,suspended]',
        'phone' => 'permit_empty|max_length[20]|regex_match[/^\+?[0-9]+$/]',
        'commission_rate' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        'experience_years' => 'permit_empty|integer|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Email already exists.',
        ],
    ];

    public function getWorkers($status = 'active')
    {
        return $this->where('user_type', 'worker')
                    ->where('status', $status)
                    ->findAll();
    }

    public function getCustomers($status = 'active')
    {
        return $this->where('user_type', 'customer')
                    ->where('status', $status)
                    ->findAll();
    }

    public function getAdminStaff($status = 'active')
    {
        return $this->whereIn('user_type', ['owner', 'admin', 'cashier'])
                    ->where('status', $status)
                    ->findAll();
    }

    public function getWorkersBySkill($skill)
    {
        return $this->where('user_type', 'worker')
                    ->where('status', 'active')
                    ->like('skills', $skill)
                    ->findAll();
    }

    public function getWorkerRating($workerId)
    {
        $reviewModel = new ReviewModel();
        return $reviewModel->getWorkerAverageRating($workerId);
    }

    public function getWorkerEarnings($workerId, $startDate = null, $endDate = null)
    {
        $paymentModel = new PaymentModel();
        return $paymentModel->getWorkerEarnings($workerId, $startDate, $endDate);
    }

    public function updateProfileImage($userId, $imagePath)
    {
        return $this->update($userId, ['profile_image' => $imagePath]);
    }

    public function verifyEmail($userId)
    {
        return $this->update($userId, ['email_verified_at' => date('Y-m-d H:i:s')]);
    }

    public function changePassword($userId, $newPassword)
    {
        return $this->update($userId, ['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);
    }

    public function getDashboardData($userType, $userId)
    {
        switch ($userType) {
            case 'owner':
                return $this->getOwnerDashboard();
            case 'admin':
                return $this->getAdminDashboard();
            case 'cashier':
                return $this->getCashierDashboard();
            case 'worker':
                return $this->getWorkerDashboard($userId);
            case 'customer':
                return $this->getCustomerDashboard($userId);
            default:
                return [];
        }
    }

    private function getOwnerDashboard()
    {
        $bookingModel = new BookingModel();
        $paymentModel = new PaymentModel();
        $userModel = new UserModel();

        return [
            'total_users' => $userModel->countAll(),
            'total_workers' => $userModel->where('user_type', 'worker')->countAllResults(),
            'total_customers' => $userModel->where('user_type', 'customer')->countAllResults(),
            'total_bookings' => $bookingModel->countAll(),
            'pending_bookings' => $bookingModel->where('status', 'pending')->countAllResults(),
            'completed_bookings' => $bookingModel->where('status', 'completed')->countAllResults(),
            'total_revenue' => $paymentModel->getTotalRevenue(),
            'pending_payments' => $paymentModel->where('status', 'pending')->countAllResults(),
        ];
    }

    private function getWorkerDashboard($workerId)
    {
        $bookingModel = new BookingModel();
        $paymentModel = new PaymentModel();

        return [
            'total_bookings' => $bookingModel->where('worker_id', $workerId)->countAllResults(),
            'pending_bookings' => $bookingModel->where(['worker_id' => $workerId, 'status' => 'assigned'])->countAllResults(),
            'completed_bookings' => $bookingModel->where(['worker_id' => $workerId, 'status' => 'completed'])->countAllResults(),
            'total_earnings' => $paymentModel->getWorkerEarnings($workerId),
            'average_rating' => $this->getWorkerRating($workerId),
        ];
    }

    private function getCustomerDashboard($customerId)
    {
        $bookingModel = new BookingModel();

        return [
            'total_bookings' => $bookingModel->where('customer_id', $customerId)->countAllResults(),
            'pending_bookings' => $bookingModel->where(['customer_id' => $customerId, 'status' => 'pending'])->countAllResults(),
            'completed_bookings' => $bookingModel->where(['customer_id' => $customerId, 'status' => 'completed'])->countAllResults(),
        ];
    }

    private function getAdminDashboard()
    {
        return $this->getOwnerDashboard();
    }

    private function getCashierDashboard()
    {
        $paymentModel = new PaymentModel();

        return [
            'pending_payments' => $paymentModel->where('status', 'pending')->countAllResults(),
            'today_payments' => $paymentModel->getTodayPayments(),
            'total_revenue' => $paymentModel->getTotalRevenue(),
        ];
    }
}
