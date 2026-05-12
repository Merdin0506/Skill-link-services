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
    public const WORKER_SKILL_OPTIONS = [
        'plumbing' => 'Plumbing',
        'car_repair' => 'Car Repair',
        'electrical_repair' => 'Electrical Repair',
        'automotive_maintenance' => 'Automotive Maintenance',
        'air_conditioning_repair' => 'Air Conditioning Repair',
        'general_home_maintenance' => 'General Home Maintenance',
        'painting' => 'Painting',
        'carpentry' => 'Carpentry',
        'welding' => 'Welding',
        'appliance_repair' => 'Appliance Repair',
        'computer_repair' => 'Computer Repair',
        'motorcycle_repair' => 'Motorcycle Repair',
        'roofing_repair' => 'Roofing Repair',
        'tile_installation' => 'Tile Installation',
        'gardening_landscaping' => 'Gardening / Landscaping',
    ];

    public const SERVICE_CATEGORY_SKILL_MAP = [
        'electrician' => ['electrical_repair', 'appliance_repair'],
        'plumber' => ['plumbing', 'tile_installation', 'general_home_maintenance'],
        'mechanic' => ['car_repair', 'automotive_maintenance', 'motorcycle_repair'],
        'technician' => ['air_conditioning_repair', 'computer_repair', 'appliance_repair'],
        'painting' => ['painting'],
        'carpentry' => ['carpentry', 'general_home_maintenance'],
        'welding' => ['welding'],
        'appliance' => ['appliance_repair'],
        'gardening' => ['gardening_landscaping', 'general_home_maintenance'],
        'roofing' => ['roofing_repair'],
        'tile' => ['tile_installation'],
        'general' => ['general_home_maintenance'],
    ];

    private const LEGACY_SKILL_ALIASES = [
        'electrical' => 'electrical_repair',
        'electric' => 'electrical_repair',
        'electrician' => 'electrical_repair',
        'wiring' => 'electrical_repair',
        'installation' => 'electrical_repair',
        'plumbing' => 'plumbing',
        'pipe repair' => 'plumbing',
        'pipe_repair' => 'plumbing',
        'car repair' => 'car_repair',
        'automotive' => 'automotive_maintenance',
        'auto repair' => 'automotive_maintenance',
        'automotive repair' => 'automotive_maintenance',
        'engine repair' => 'automotive_maintenance',
        'diagnostics' => 'automotive_maintenance',
        'ac repair' => 'air_conditioning_repair',
        'air conditioning' => 'air_conditioning_repair',
        'air-conditioning repair' => 'air_conditioning_repair',
        'general home maintenance' => 'general_home_maintenance',
        'general maintenance' => 'general_home_maintenance',
        'home maintenance' => 'general_home_maintenance',
        'painting' => 'painting',
        'painter' => 'painting',
        'carpentry' => 'carpentry',
        'carpenter' => 'carpentry',
        'woodwork' => 'carpentry',
        'welding' => 'welding',
        'welder' => 'welding',
        'appliance repair' => 'appliance_repair',
        'appliance' => 'appliance_repair',
        'computer repair' => 'computer_repair',
        'computer' => 'computer_repair',
        'motorcycle repair' => 'motorcycle_repair',
        'motorcycle' => 'motorcycle_repair',
        'roofing repair' => 'roofing_repair',
        'roof repair' => 'roofing_repair',
        'roofing' => 'roofing_repair',
        'tile installation' => 'tile_installation',
        'tile' => 'tile_installation',
        'gardening' => 'gardening_landscaping',
        'landscaping' => 'gardening_landscaping',
        'garden' => 'gardening_landscaping',
        'gardening / landscaping' => 'gardening_landscaping',
    ];

    public static function normalizeWorkerSkills($skills): array
    {
        if (is_string($skills)) {
            $decodedSkills = json_decode($skills, true);
            $skills = is_array($decodedSkills) ? $decodedSkills : [$skills];
        }

        if (!is_array($skills)) {
            return [];
        }

        $normalizedSkills = [];
        foreach ($skills as $skill) {
            $skillCode = self::normalizeSkillValue((string) $skill);
            if ($skillCode === '') {
                continue;
            }

            if (array_key_exists($skillCode, self::WORKER_SKILL_OPTIONS)) {
                $normalizedSkills[] = $skillCode;
            }
        }

        return array_values(array_unique($normalizedSkills));
    }

    private static function normalizeSkillValue(string $skill): string
    {
        $skill = strtolower(trim($skill));
        if ($skill === '') {
            return '';
        }

        $skill = str_replace(['-', '/', ','], ' ', $skill);
        $skill = preg_replace('/\s+/', ' ', $skill) ?? $skill;

        if (array_key_exists($skill, self::LEGACY_SKILL_ALIASES)) {
            return self::LEGACY_SKILL_ALIASES[$skill];
        }

        $compact = str_replace(' ', '_', $skill);
        if (array_key_exists($compact, self::WORKER_SKILL_OPTIONS)) {
            return $compact;
        }

        return array_key_exists($skill, self::WORKER_SKILL_OPTIONS) ? $skill : '';
    }

    public static function getServiceCategorySkillCodes(string $serviceCategory): array
    {
        return self::SERVICE_CATEGORY_SKILL_MAP[$serviceCategory] ?? [];
    }

    protected $allowedFields = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'resume_path',
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
        'otp',
        'otp_expire',
        'otp_attempts',
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
        'email'      => 'required|valid_email|regex_match[/^[A-Za-z0-9][A-Za-z0-9._-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/]|is_unique[users.email,id,{id}]',
        'password'   => 'required|min_length[8]',
        'user_type'  => 'required|in_list[super_admin,admin,finance,worker,customer]',
        'status'     => 'required|in_list[pending,active,inactive,suspended,rejected]',
        'phone'      => 'permit_empty|regex_match[/^(09\d{9}|\+639\d{8})$/]',
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
            'regex_match' => 'Email local part may use letters, numbers, dots (not as first character), hyphens (-) and underscores (_).',
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
        $workers = $this->where('user_type', 'worker')
                        ->where('status', 'active')
                        ->findAll();

        $requiredSkillCodes = self::getServiceCategorySkillCodes((string) $serviceCategory);

        // Unknown categories should not match anyone.
        if (empty($requiredSkillCodes)) {
            return [];
        }

        $matchingWorkers = [];
        foreach ($workers as $worker) {
            $workerSkills = self::normalizeWorkerSkills($worker['skills'] ?? []);
            if (array_intersect($requiredSkillCodes, $workerSkills) !== []) {
                $matchingWorkers[] = $worker;
            }
        }

        return $matchingWorkers;
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
