<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\ServiceModel;
use App\Models\UserModel;

class Bookings extends BaseController
{
    protected $bookingModel;
    protected $serviceModel;
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->serviceModel = new ServiceModel();
        $this->userModel = new UserModel();
        $this->session = session();
    }

    /**
     * Store a new booking
     */
    public function store()
    {
        // Check if user is logged in
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login')->with('error', 'Please login to book a service');
        }

        // Validate the form data
        $rules = [
            'service_id' => 'required|integer',
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[1000]',
            'location_address' => 'required|min_length[5]',
            'scheduled_date' => 'required|valid_date[Y-m-d]',
            'scheduled_time' => 'required|regex_match[/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            'priority' => 'required|in_list[low,medium,high,urgent]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Get the service
        $serviceId = $this->request->getPost('service_id');
        $service = $this->serviceModel->find($serviceId);

        if (!$service) {
            return redirect()->back()->with('error', 'Service not found');
        }

        if ($service['status'] !== 'active') {
            return redirect()->back()->with('error', 'This service is not available');
        }

        $customerId = (int) $this->session->get('user_id');

        // Prepare booking data
        $data = [
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'location_address' => $this->request->getPost('location_address'),
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'scheduled_date' => $this->request->getPost('scheduled_date'),
            'scheduled_time' => $this->request->getPost('scheduled_time'),
            'labor_fee' => (float) $service['base_price'],
            'materials_fee' => 0,
            'priority' => $this->request->getPost('priority'),
            'status' => 'pending',
            'notes' => $this->request->getPost('notes')
        ];

        try {
            $bookingId = $this->bookingModel->createBooking($data);
            
            if ($bookingId) {
                $booking = $this->bookingModel->find($bookingId);
                return redirect()->to('/customer/bookings')->with('success', 'Booking created successfully! Reference: ' . $booking['booking_reference']);
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to create booking');
            }
        } catch (\Exception $e) {
            log_message('error', 'Booking creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An error occurred while creating the booking');
        }
    }

    /**
     * Cancel a booking
     */
    public function cancel($id = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $customerId = (int) $this->session->get('user_id');
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        // Verify this booking belongs to the customer
        if ($booking['customer_id'] != $customerId) {
            return redirect()->back()->with('error', 'Unauthorized action');
        }

        // Only allow cancelling pending or assigned bookings
        if (!in_array($booking['status'], ['pending', 'assigned'])) {
            return redirect()->back()->with('error', 'This booking cannot be cancelled');
        }

        try {
            if ($this->bookingModel->cancelBooking($id)) {
                return redirect()->back()->with('success', 'Booking cancelled successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to cancel booking');
            }
        } catch (\Exception $e) {
            log_message('error', 'Booking cancellation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to cancel booking');
        }
    }

    /**
     * View booking details
     */
    public function view($id = null)
    {
        if (!$this->session->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $customerId = (int) $this->session->get('user_id');
        $booking = $this->bookingModel->getBookingWithDetails($id);

        if (!$booking) {
            return redirect()->to('/customer/bookings')->with('error', 'Booking not found');
        }

        // Verify this booking belongs to the customer
        if ($booking['customer_id'] != $customerId) {
            return redirect()->to('/customer/bookings')->with('error', 'Unauthorized access');
        }

        $data = [
            'role' => $this->session->get('user_role'),
            'booking' => $booking,
            'user' => $this->getCurrentUser(),
        ];

        return view('dashboard/booking_details', $data);
    }

    protected function getCurrentUser()
    {
        if ($this->session->has('user_id')) {
            return $this->userModel->find($this->session->get('user_id'));
        }
        return null;
    }
}
