<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ServiceModel;
use CodeIgniter\API\ResponseTrait;

class ServicesController extends BaseController
{
    use ResponseTrait;

    protected $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
    }

    public function index()
    {
        $status = $this->request->getVar('status') ?? 'active';
        $category = $this->request->getVar('category');
        $limit = $this->request->getVar('limit') ?? 50;

        $services = $this->serviceModel
            ->where('status', $status)
            ->when($category, function($query, $category) {
                return $query->where('category', $category);
            })
            ->limit($limit)
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function show($id = null)
    {
        $service = $this->serviceModel->find($id);

        if (!$service) {
            return $this->failNotFound('Service not found');
        }

        return $this->respond([
            'status' => 'success',
            'data' => $service
        ]);
    }

    public function categories()
    {
        $categories = $this->serviceModel->getServiceCategories();

        return $this->respond([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function popular()
    {
        $limit = $this->request->getVar('limit') ?? 10;
        $services = $this->serviceModel->getPopularServices($limit);

        return $this->respond([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function byCategory($category)
    {
        $services = $this->serviceModel->getServicesByCategory($category);

        if (empty($services)) {
            return $this->failNotFound('No services found in this category');
        }

        return $this->respond([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function store()
    {
        // This would typically require admin authentication
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[1000]',
            'category' => 'required|in_list[mechanic,electrician,plumber,technician,general]',
            'base_price' => 'required|numeric|greater_than[0]',
            'estimated_duration' => 'integer|greater_than[0]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getVar('name'),
            'description' => $this->request->getVar('description'),
            'category' => $this->request->getVar('category'),
            'base_price' => $this->request->getVar('base_price'),
            'estimated_duration' => $this->request->getVar('estimated_duration'),
            'status' => 'active'
        ];

        try {
            $serviceId = $this->serviceModel->insert($data);
            $service = $this->serviceModel->find($serviceId);

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Service created successfully',
                'data' => $service
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to create service: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        // This would typically require admin authentication
        $service = $this->serviceModel->find($id);

        if (!$service) {
            return $this->failNotFound('Service not found');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[1000]',
            'category' => 'required|in_list[mechanic,electrician,plumber,technician,general]',
            'base_price' => 'required|numeric|greater_than[0]',
            'estimated_duration' => 'integer|greater_than[0]',
            'status' => 'required|in_list[active,inactive]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getVar('name'),
            'description' => $this->request->getVar('description'),
            'category' => $this->request->getVar('category'),
            'base_price' => $this->request->getVar('base_price'),
            'estimated_duration' => $this->request->getVar('estimated_duration'),
            'status' => $this->request->getVar('status')
        ];

        try {
            $this->serviceModel->update($id, $data);
            $updatedService = $this->serviceModel->find($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Service updated successfully',
                'data' => $updatedService
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update service: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        // This would typically require admin authentication
        $service = $this->serviceModel->find($id);

        if (!$service) {
            return $this->failNotFound('Service not found');
        }

        try {
            $this->serviceModel->delete($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Service deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete service: ' . $e->getMessage());
        }
    }
}
