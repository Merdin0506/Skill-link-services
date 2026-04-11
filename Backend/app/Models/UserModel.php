<?php

namespace App\Models;

use App\Libraries\SensitiveDataCipher;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
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
        'email_verified_at',
        'phone_last4',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'password_changed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $beforeInsert = ['protectSensitiveFields'];
    protected $beforeUpdate = ['protectSensitiveFields'];
    protected $afterFind = ['restoreSensitiveFields'];

    protected $validationRules = [
        'first_name' => 'required|min_length[2]|max_length[100]|regex_match[/^[a-zA-Z\s]+$/]',
        'last_name'  => 'required|min_length[2]|max_length[100]|regex_match[/^[a-zA-Z\s]+$/]',
        'email'      => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]|is_unique[users.email,id,{id}]',
        'password'   => 'required|min_length[8]',
        'user_type'  => 'required|in_list[super_admin,admin,finance,worker,customer]',
        'status'     => 'required|in_list[active,inactive,suspended]',
        'phone'      => 'permit_empty|max_length[20]|regex_match[/^\+?[0-9]+$/]',
        'commission_rate'  => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        'experience_years' => 'permit_empty|integer|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'first_name' => [
            'regex_match' => 'First name can only contain letters and spaces.',
        ],
        'last_name' => [
            'regex_match' => 'Last name can only contain letters and spaces.',
        ],
        'email' => [
            'valid_email' => 'Please provide a valid email address.',
            'regex_match' => 'Email may only use letters, numbers, dots, and underscores before @.',
            'is_unique'   => 'Email already exists.',
        ],
    ];

    private SensitiveDataCipher $cipher;

    public function __construct(?\CodeIgniter\Database\ConnectionInterface $db = null, ?\CodeIgniter\Validation\ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
        $this->cipher = new SensitiveDataCipher();
    }

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
        return $this->whereIn('user_type', ['super_admin', 'admin', 'finance'])
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

    /**
     * Get workers matching a service category
     */
    public function getWorkersByServiceCategory($serviceCategory)
    {
        // Map service categories to skill keywords
        $skillMap = [
            'electrician' => ['electrical', 'electrician', 'wiring'],
            'plumber' => ['plumbing', 'plumber', 'pipe'],
            'mechanic' => ['automotive', 'mechanic', 'engine'],
            'technician' => ['technician', 'technical', 'repair'],
            'general' => [] // general workers can have any skill
        ];

        $workers = $this->where('user_type', 'worker')
                        ->where('status', 'active')
                        ->findAll();

        // If general category, return all active workers
        if ($serviceCategory === 'general' || !isset($skillMap[$serviceCategory])) {
            return $workers;
        }

        // Filter workers whose skills match the category
        $matchingWorkers = [];
        $keywords = $skillMap[$serviceCategory];

        foreach ($workers as $worker) {
            $workerSkills = json_decode($worker['skills'] ?? '[]', true);
            if (!is_array($workerSkills)) {
                continue;
            }

            // Check if any worker skill contains any of the keywords
            foreach ($workerSkills as $skill) {
                $skillLower = strtolower($skill);
                foreach ($keywords as $keyword) {
                    if (stripos($skillLower, $keyword) !== false) {
                        $matchingWorkers[] = $worker;
                        break 2; // Break both loops once matched
                    }
                }
            }
        }

        return $matchingWorkers;
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
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recordFailedLogin(int $userId, int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $user = $this->withDeleted()->find($userId);
        if (!$user) {
            return;
        }

        $attempts = (int) ($user['failed_login_attempts'] ?? 0) + 1;
        $data = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
        }

        $this->builder()->where('id', $userId)->update($data);
    }

    public function clearFailedLogins(int $userId): void
    {
        $this->builder()->where('id', $userId)->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function isLocked(array $user): bool
    {
        $lockedUntil = $user['locked_until'] ?? null;

        return $lockedUntil !== null && strtotime((string) $lockedUntil) > time();
    }

    public function getDashboardData($userType, $userId)
    {
        switch ($userType) {
            case 'owner':
                return $this->getOwnerDashboard();
            case 'admin':
            case 'finance':
                return $this->getAdminDashboard();
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function protectSensitiveFields(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        foreach (['phone', 'address'] as $field) {
            if (!array_key_exists($field, $data['data'])) {
                continue;
            }

            $plainValue = $data['data'][$field];
            if ($plainValue === null || trim((string) $plainValue) === '') {
                $data['data'][$field] = null;
                if ($field === 'phone') {
                    $data['data']['phone_last4'] = null;
                }
                continue;
            }

            $data['data'][$field] = $this->cipher->encrypt((string) $plainValue);
            if ($field === 'phone') {
                $data['data']['phone_last4'] = $this->cipher->phoneLastFour((string) $plainValue);
            }
        }

        if (array_key_exists('password', $data['data']) && !array_key_exists('password_changed_at', $data['data'])) {
            $data['data']['password_changed_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function restoreSensitiveFields(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                $row = $this->decryptSensitiveRow($row);
            }

            return $data;
        }

        if (is_array($data['data'])) {
            $data['data'] = $this->decryptSensitiveRow($data['data']);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decryptSensitiveRow(array $row): array
    {
        foreach (['phone', 'address'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = $this->cipher->decrypt($row[$field]);
            }
        }

        return $row;
    }
}
