<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ServiceRecordModel;

class RecordsController extends BaseController
{
    protected $recordModel;

    public function __construct()
    {
        $this->recordModel = new ServiceRecordModel();
    }

    // CREATE
    public function create()
    {
        $data = $this->request->getJSON(true);

        $data['total_amount'] =
            ($data['labor_fee'] ?? 0) +
            ($data['platform_fee'] ?? 0);

        $this->recordModel->insert($data);

        return $this->response->setJSON([
            "message" => "Record created"
        ]);
    }

    // READ ALL + SEARCH
    public function index()
    {
        $status = $this->request->getGet('status');
        $payment = $this->request->getGet('payment_status');
        $q = $this->request->getGet('q');

        $builder = $this->recordModel;

        if ($status) {
            $builder->where('status', $status);
        }

        if ($payment) {
            $builder->where('payment_status', $payment);
        }

        if ($q) {
            $builder->like('payment_ref', $q)
                    ->orLike('address_text', $q);
        }

        $data = $builder->findAll();

        return $this->response->setJSON($data);
    }

    // READ ONE
    public function show($id)
    {
        $record = $this->recordModel->find($id);

        if (!$record) {
            return $this->response->setStatusCode(404)
                ->setJSON(["message" => "Record not found"]);
        }

        return $this->response->setJSON($record);
    }

    // UPDATE
    public function update($id)
    {
        $data = $this->request->getJSON(true);

        if (isset($data['labor_fee']) || isset($data['platform_fee'])) {
            $labor = $data['labor_fee'] ?? 0;
            $platform = $data['platform_fee'] ?? 0;
            $data['total_amount'] = $labor + $platform;
        }

        $this->recordModel->update($id, $data);

        return $this->response->setJSON([
            "message" => "Record updated"
        ]);
    }

    // DELETE
    public function delete($id)
    {
        $this->recordModel->delete($id);

        return $this->response->setJSON([
            "message" => "Record deleted"
        ]);
    }
}