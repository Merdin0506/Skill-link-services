<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceRecordModel extends Model
{
    protected $table = 'service_records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'booking_id',
        'customer_id',
        'provider_id',
        'service_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'address_text',
        'labor_fee',
        'platform_fee',
        'total_amount',
        'payment_status',
        'payment_ref',
        'customer_note',
        'provider_note',
        'admin_note',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'customer_id'    => 'required|integer',
        'service_id'     => 'required|integer',
        'status'         => 'required|in_list[pending,scheduled,in_progress,completed,cancelled]',
        'labor_fee'      => 'permit_empty|numeric|greater_than_equal_to[0]',
        'platform_fee'   => 'permit_empty|numeric|greater_than_equal_to[0]',
        'total_amount'   => 'permit_empty|numeric|greater_than_equal_to[0]',
        'payment_status' => 'permit_empty|in_list[unpaid,partial,paid,refunded]',
        'payment_ref'    => 'permit_empty|max_length[100]',
        'address_text'   => 'permit_empty|max_length[1000]',
    ];

    protected $validationMessages = [
        'customer_id' => [
            'required' => 'Customer ID is required.',
            'integer'  => 'Customer ID must be a valid integer.',
        ],
        'service_id' => [
            'required' => 'Service ID is required.',
            'integer'  => 'Service ID must be a valid integer.',
        ],
        'status' => [
            'in_list' => 'Status must be one of: pending, scheduled, in_progress, completed, cancelled.',
        ],
    ];

    /**
     * Get a record with related user and service names
     */
    public function getRecordWithDetails(int $recordId)
    {
        return $this->select('service_records.*,
                    customers.first_name AS customer_first_name,
                    customers.last_name  AS customer_last_name,
                    providers.first_name AS provider_first_name,
                    providers.last_name  AS provider_last_name,
                    services.name        AS service_name,
                    services.category    AS service_category')
            ->join('users AS customers', 'customers.id = service_records.customer_id')
            ->join('users AS providers', 'providers.id = service_records.provider_id', 'left')
            ->join('services', 'services.id = service_records.service_id')
            ->where('service_records.id', $recordId)
            ->first();
    }

    /**
     * List records with joins and optional filters, with pagination
     */
    public function getFilteredRecords(array $filters = [], int $limit = 20, int $offset = 0)
    {
        $builder = $this->select('service_records.*,
                    customers.first_name AS customer_first_name,
                    customers.last_name  AS customer_last_name,
                    providers.first_name AS provider_first_name,
                    providers.last_name  AS provider_last_name,
                    services.name        AS service_name')
            ->join('users AS customers', 'customers.id = service_records.customer_id')
            ->join('users AS providers', 'providers.id = service_records.provider_id', 'left')
            ->join('services', 'services.id = service_records.service_id');

        if (!empty($filters['status'])) {
            $builder->where('service_records.status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $builder->where('service_records.payment_status', $filters['payment_status']);
        }

        if (!empty($filters['customer_id'])) {
            $builder->where('service_records.customer_id', $filters['customer_id']);
        }

        if (!empty($filters['provider_id'])) {
            $builder->where('service_records.provider_id', $filters['provider_id']);
        }

        if (!empty($filters['service_id'])) {
            $builder->where('service_records.service_id', $filters['service_id']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('service_records.scheduled_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('service_records.scheduled_at <=', $filters['date_to']);
        }

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $builder->groupStart()
                ->like('service_records.payment_ref', $q)
                ->orLike('service_records.address_text', $q)
                ->orLike('customers.first_name', $q)
                ->orLike('customers.last_name', $q)
                ->orLike('services.name', $q)
            ->groupEnd();
        }

        $sort  = $filters['sort'] ?? 'service_records.created_at';
        $order = $filters['order'] ?? 'DESC';
        $allowed_sort = ['service_records.created_at', 'service_records.scheduled_at', 'service_records.total_amount', 'service_records.status'];
        if (!in_array($sort, $allowed_sort, true)) {
            $sort = 'service_records.created_at';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return $builder->orderBy($sort, $order)
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Count filtered records (for pagination)
     */
    public function countFilteredRecords(array $filters = []): int
    {
        $builder = $this->join('users AS customers', 'customers.id = service_records.customer_id')
            ->join('users AS providers', 'providers.id = service_records.provider_id', 'left')
            ->join('services', 'services.id = service_records.service_id');

        if (!empty($filters['status'])) {
            $builder->where('service_records.status', $filters['status']);
        }
        if (!empty($filters['payment_status'])) {
            $builder->where('service_records.payment_status', $filters['payment_status']);
        }
        if (!empty($filters['customer_id'])) {
            $builder->where('service_records.customer_id', $filters['customer_id']);
        }
        if (!empty($filters['provider_id'])) {
            $builder->where('service_records.provider_id', $filters['provider_id']);
        }
        if (!empty($filters['service_id'])) {
            $builder->where('service_records.service_id', $filters['service_id']);
        }
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $builder->groupStart()
                ->like('service_records.payment_ref', $q)
                ->orLike('service_records.address_text', $q)
                ->orLike('customers.first_name', $q)
                ->orLike('customers.last_name', $q)
                ->orLike('services.name', $q)
            ->groupEnd();
        }

        return $builder->countAllResults();
    }

    /**
     * Get records for a specific customer
     */
    public function getCustomerRecords(int $customerId, ?string $status = null)
    {
        $builder = $this->select('service_records.*, services.name AS service_name')
            ->join('services', 'services.id = service_records.service_id')
            ->where('service_records.customer_id', $customerId);

        if ($status) {
            $builder->where('service_records.status', $status);
        }

        return $builder->orderBy('service_records.created_at', 'DESC')->findAll();
    }

    /**
     * Get records for a specific provider/worker
     */
    public function getProviderRecords(int $providerId, ?string $status = null)
    {
        $builder = $this->select('service_records.*, services.name AS service_name,
                    customers.first_name AS customer_first_name,
                    customers.last_name  AS customer_last_name')
            ->join('services', 'services.id = service_records.service_id')
            ->join('users AS customers', 'customers.id = service_records.customer_id')
            ->where('service_records.provider_id', $providerId);

        if ($status) {
            $builder->where('service_records.status', $status);
        }

        return $builder->orderBy('service_records.created_at', 'DESC')->findAll();
    }
}