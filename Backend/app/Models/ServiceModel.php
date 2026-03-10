<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'description',
        'category',
        'base_price',
        'estimated_duration',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'category' => 'required|in_list[mechanic,electrician,plumber,technician,general]',
        'base_price' => 'required|numeric|greater_than[0]',
        'estimated_duration' => 'integer|greater_than[0]',
        'status' => 'required|in_list[active,inactive]'
    ];

    public function getActiveServices()
    {
        return $this->where('status', 'active')->findAll();
    }

    public function getServicesByCategory($category)
    {
        return $this->where('category', $category)
                    ->where('status', 'active')
                    ->findAll();
    }

    public function getServiceCategories()
    {
        return [
            'mechanic' => 'Mechanic Services',
            'electrician' => 'Electrical Services',
            'plumber' => 'Plumbing Services',
            'technician' => 'Technical Services',
            'general' => 'General Services'
        ];
    }

    public function getPopularServices($limit = 10)
    {
        $bookingModel = new BookingModel();
        
        return $this->select('services.*, COUNT(bookings.id) as booking_count')
                    ->join('bookings', 'bookings.service_id = services.id')
                    ->where('services.status', 'active')
                    ->groupBy('services.id')
                    ->orderBy('booking_count', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
