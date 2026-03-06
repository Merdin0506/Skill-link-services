<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ServiceRecordModel;
use App\Models\UserModel;
use App\Models\ServiceModel;
use CodeIgniter\API\ResponseTrait;

class RecordsController extends BaseController
{
    use ResponseTrait;

    protected $recordModel;

    public function __construct()
    {
        $this->recordModel = new ServiceRecordModel();
    }

    /**
     * Get current authenticated user from session.
     * Returns null if not logged in.
     */
    private function getAuthUser(): ?array
    {
        $session = session();
        if (!$session->has('user_id')) {
            return null;
        }

        return [
            'id'   => (int) $session->get('user_id'),
            'role' => $session->get('user_role'),
        ];
    }

    // ─── READ ALL + SEARCH / FILTER / PAGINATION ─────────────────

    public function index()
    {
        $auth = $this->getAuthUser();
        if (!$auth) {
            return $this->failUnauthorized('Authentication required.');
        }

        $filters = [
            'status'         => $this->request->getGet('status'),
            'payment_status' => $this->request->getGet('payment_status'),
            'customer_id'    => $this->request->getGet('customer_id'),
            'provider_id'    => $this->request->getGet('provider_id'),
            'service_id'     => $this->request->getGet('service_id'),
            'date_from'      => $this->request->getGet('date_from'),
            'date_to'        => $this->request->getGet('date_to'),
            'q'              => $this->request->getGet('q'),
            'sort'           => $this->request->getGet('sort'),
            'order'          => $this->request->getGet('order'),
        ];

        // Non-admin users can only see their own records
        if ($auth['role'] === 'customer') {
            $filters['customer_id'] = $auth['id'];
        } elseif ($auth['role'] === 'worker') {
            $filters['provider_id'] = $auth['id'];
        }

        $limit  = max(1, min((int) ($this->request->getGet('limit') ?? 20), 100));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $limit;

        $records = $this->recordModel->getFilteredRecords($filters, $limit, $offset);
        $total   = $this->recordModel->countFilteredRecords($filters);

        return $this->respond([
            'status' => 'success',
            'data'   => $records,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $limit,
                'current_page' => $page,
                'total_pages'  => (int) ceil($total / $limit),
            ],
        ]);
    }

    // ─── READ ONE ─────────────────────────────────────────────────

    public function show($id = null)
    {
        $auth = $this->getAuthUser();
        if (!$auth) {
            return $this->failUnauthorized('Authentication required.');
        }

        $record = $this->recordModel->getRecordWithDetails((int) $id);

        if (!$record) {
            return $this->failNotFound('Record not found.');
        }

        // Customers see only their own; workers see only assigned
        if ($auth['role'] === 'customer' && (int) $record['customer_id'] !== $auth['id']) {
            return $this->failForbidden('You do not have permission to view this record.');
        }
        if ($auth['role'] === 'worker' && (int) $record['provider_id'] !== $auth['id']) {
            return $this->failForbidden('You do not have permission to view this record.');
        }

        return $this->respond([
            'status' => 'success',
            'data'   => $record,
        ]);
    }

    // ─── CREATE ───────────────────────────────────────────────────

    public function create()
    {
        $auth = $this->getAuthUser();
        if (!$auth) {
            return $this->failUnauthorized('Authentication required.');
        }

        // Only admin, owner, or cashier may create records
        if (!in_array($auth['role'], ['admin', 'owner', 'cashier'])) {
            return $this->failForbidden('You do not have permission to create records.');
        }

        $rules = [
            'customer_id'    => 'required|integer',
            'service_id'     => 'required|integer',
            'status'         => 'permit_empty|in_list[pending,scheduled,in_progress,completed,cancelled]',
            'scheduled_at'   => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'address_text'   => 'permit_empty|max_length[1000]',
            'labor_fee'      => 'permit_empty|numeric|greater_than_equal_to[0]',
            'platform_fee'   => 'permit_empty|numeric|greater_than_equal_to[0]',
            'payment_status' => 'permit_empty|in_list[unpaid,partial,paid,refunded]',
            'payment_ref'    => 'permit_empty|max_length[100]',
            'customer_note'  => 'permit_empty|max_length[2000]',
            'provider_note'  => 'permit_empty|max_length[2000]',
            'admin_note'     => 'permit_empty|max_length[2000]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Verify referenced entities exist
        $userModel    = new UserModel();
        $serviceModel = new ServiceModel();

        $customer = $userModel->find($this->request->getVar('customer_id'));
        if (!$customer) {
            return $this->fail('Invalid customer ID.');
        }

        $service = $serviceModel->find($this->request->getVar('service_id'));
        if (!$service) {
            return $this->fail('Invalid service ID.');
        }

        $laborFee    = (float) ($this->request->getVar('labor_fee') ?? 0);
        $platformFee = (float) ($this->request->getVar('platform_fee') ?? 0);

        $data = [
            'booking_id'     => $this->request->getVar('booking_id'),
            'customer_id'    => $this->request->getVar('customer_id'),
            'provider_id'    => $this->request->getVar('provider_id'),
            'service_id'     => $this->request->getVar('service_id'),
            'status'         => $this->request->getVar('status') ?? 'pending',
            'scheduled_at'   => $this->request->getVar('scheduled_at'),
            'address_text'   => $this->request->getVar('address_text'),
            'labor_fee'      => $laborFee,
            'platform_fee'   => $platformFee,
            'total_amount'   => $laborFee + $platformFee,
            'payment_status' => $this->request->getVar('payment_status') ?? 'unpaid',
            'payment_ref'    => $this->request->getVar('payment_ref'),
            'customer_note'  => $this->request->getVar('customer_note'),
            'provider_note'  => $this->request->getVar('provider_note'),
            'admin_note'     => $this->request->getVar('admin_note'),
        ];

        try {
            $recordId = $this->recordModel->insert($data);

            if ($recordId === false) {
                return $this->fail($this->recordModel->errors());
            }

            $record = $this->recordModel->getRecordWithDetails($recordId);

            return $this->respondCreated([
                'status'  => 'success',
                'message' => 'Record created successfully.',
                'data'    => $record,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Record create failed: ' . $e->getMessage());
            return $this->fail('Failed to create record.');
        }
    }

    // ─── UPDATE ───────────────────────────────────────────────────

    public function update($id = null)
    {
        $auth = $this->getAuthUser();
        if (!$auth) {
            return $this->failUnauthorized('Authentication required.');
        }

        $record = $this->recordModel->find((int) $id);
        if (!$record) {
            return $this->failNotFound('Record not found.');
        }

        // Authorization: admin/owner/cashier can update any; worker can update their own assigned
        if (in_array($auth['role'], ['admin', 'owner', 'cashier'])) {
            // allowed
        } elseif ($auth['role'] === 'worker' && (int) $record['provider_id'] === $auth['id']) {
            // worker can only update notes and status for assigned records
        } else {
            return $this->failForbidden('You do not have permission to update this record.');
        }

        $rules = [
            'status'         => 'permit_empty|in_list[pending,scheduled,in_progress,completed,cancelled]',
            'scheduled_at'   => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'address_text'   => 'permit_empty|max_length[1000]',
            'labor_fee'      => 'permit_empty|numeric|greater_than_equal_to[0]',
            'platform_fee'   => 'permit_empty|numeric|greater_than_equal_to[0]',
            'payment_status' => 'permit_empty|in_list[unpaid,partial,paid,refunded]',
            'payment_ref'    => 'permit_empty|max_length[100]',
            'customer_note'  => 'permit_empty|max_length[2000]',
            'provider_note'  => 'permit_empty|max_length[2000]',
            'admin_note'     => 'permit_empty|max_length[2000]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Workers may only update provider_note and status
        $allowedFields = ['status', 'scheduled_at', 'started_at', 'completed_at',
            'address_text', 'labor_fee', 'platform_fee', 'payment_status',
            'payment_ref', 'customer_note', 'provider_note', 'admin_note',
            'provider_id', 'booking_id'];

        if ($auth['role'] === 'worker') {
            $allowedFields = ['provider_note', 'status', 'started_at', 'completed_at'];
        }

        $data = [];
        foreach ($allowedFields as $field) {
            $value = $this->request->getVar($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        // Recalculate total if fees changed
        if (isset($data['labor_fee']) || isset($data['platform_fee'])) {
            $labor    = (float) ($data['labor_fee'] ?? $record['labor_fee']);
            $platform = (float) ($data['platform_fee'] ?? $record['platform_fee']);
            $data['total_amount'] = $labor + $platform;
        }

        if (empty($data)) {
            return $this->fail('No valid fields provided for update.');
        }

        try {
            $success = $this->recordModel->update((int) $id, $data);

            if ($success === false) {
                return $this->fail($this->recordModel->errors());
            }

            $updated = $this->recordModel->getRecordWithDetails((int) $id);

            return $this->respond([
                'status'  => 'success',
                'message' => 'Record updated successfully.',
                'data'    => $updated,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Record update failed: ' . $e->getMessage());
            return $this->fail('Failed to update record.');
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────

    public function delete($id = null)
    {
        $auth = $this->getAuthUser();
        if (!$auth) {
            return $this->failUnauthorized('Authentication required.');
        }

        // Only admin can delete records
        if ($auth['role'] !== 'admin') {
            return $this->failForbidden('Only administrators can delete records.');
        }

        $record = $this->recordModel->find((int) $id);
        if (!$record) {
            return $this->failNotFound('Record not found.');
        }

        try {
            $this->recordModel->delete((int) $id);

            return $this->respond([
                'status'  => 'success',
                'message' => 'Record deleted successfully.',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Record delete failed: ' . $e->getMessage());
            return $this->fail('Failed to delete record.');
        }
    }
}