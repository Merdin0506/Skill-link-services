# SkillLink Services Backend API

A comprehensive backend API for SkillLink Services - a Service Workforce Management System that connects skilled workers with customers.

## 🚀 Project Overview

SkillLink Services Inc. is a service-based company that connects skilled workers such as mechanics, electricians, plumbers, and technicians with customers who require on-demand or scheduled services.

### Features

- **User Management**: Multi-role system (Admin, Finance, Worker, Customer)
- **Service Booking**: Complete booking lifecycle from request to completion
- **Payment Processing**: Multiple payment methods with commission-based worker payouts
- **Review System**: Customer ratings and feedback for workers
- **Real-time Tracking**: Job status updates and notifications
- **Reporting**: Comprehensive analytics and reporting dashboard

## 🛠️ Technology Stack

- **Framework**: CodeIgniter 4
- **Language**: PHP 8.2+
- **Database**: MySQL/MariaDB
- **Authentication**: JWT (JSON Web Tokens)
- **API Documentation**: RESTful API

## 📋 Prerequisites

- PHP 8.2 or higher
- MySQL/MariaDB
- Composer
- Web Server (Apache/Nginx)

## 🚀 Installation

> Repository layout note: `Backend/` is the canonical CodeIgniter project root. Run backend commands from `Backend/`.

### 1. Clone the Repository

```bash
git clone https://github.com/Merdin0506/Skill-link-services.git
cd Skill-link-services/Backend
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

```bash
# Copy environment template
cp env.example .env

# Edit the .env file with your database credentials
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE skilllink_services;

# Run migrations
php spark migrate
```

### 5. Generate Encryption Key

```bash
php spark key:generate
```

### 6. Set JWT Secret

Add this to your `.env` file:
```
JWT_SECRET = your-super-secret-jwt-key-change-this-in-production
```

## 📚 API Documentation

### Base URL
```
http://localhost:8080/api
```

### Authentication

All API endpoints (except login/register) require JWT authentication.

#### Register
```http
POST /api/auth/register
Content-Type: application/json

{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "user_type": "customer",
    "phone": "+1234567890",
    "address": "123 Main St"
}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

#### Get Profile
```http
GET /api/auth/profile
Authorization: Bearer {jwt_token}
```

### Services

#### Get All Services
```http
GET /api/services
```

#### Get Service by ID
```http
GET /api/services/{id}
```

#### Get Services by Category
```http
GET /api/services/category/{category}
```

### Bookings

#### Create Booking
```http
POST /api/bookings
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
    "customer_id": 1,
    "service_id": 1,
    "title": "Fix broken faucet",
    "description": "Kitchen faucet is leaking",
    "location_address": "123 Main St",
    "scheduled_date": "2026-03-05",
    "scheduled_time": "14:00",
    "labor_fee": 500.00,
    "priority": "medium"
}
```

#### Get Customer Bookings
```http
GET /api/bookings?user_type=customer&user_id={customer_id}
```

#### Assign Worker to Booking
```http
POST /api/bookings/assign-worker
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
    "booking_id": 1,
    "worker_id": 2,
    "assigned_by": 1
}
```

### Payments

#### Create Customer Payment
```http
POST /api/payments/customer
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
    "booking_id": 1,
    "payment_method": "gcash",
    "processed_by": 1
}
```

#### Get Payment Statistics
```http
GET /api/payments/statistics
Authorization: Bearer {jwt_token}
```

### Reviews

#### Create Review
```http
POST /api/reviews
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
    "booking_id": 1,
    "customer_id": 1,
    "worker_id": 2,
    "rating": 5,
    "comment": "Excellent service!",
    "service_quality": 5,
    "timeliness": 5,
    "professionalism": 5,
    "would_recommend": 1
}
```

#### Get Worker Ratings
```http
GET /api/reviews/worker/{worker_id}
```

## 🏗️ Project Structure

```
Backend/
├── app/
│   ├── Controllers/API/
│   │   ├── AuthController.php
│   │   ├── BookingsController.php
│   │   ├── PaymentsController.php
│   │   ├── ReviewsController.php
│   │   ├── ServicesController.php
│   │   └── UsersController.php
│   ├── Database/
│   │   └── Migrations/
│   ├── Models/
│   │   ├── BookingModel.php
│   │   ├── PaymentModel.php
│   │   ├── ReviewModel.php
│   │   ├── ServiceModel.php
│   │   └── UserModel.php
│   └── Config/
├── public/
├── writable/
│   ├── uploads/
│   ├── logs/
│   └── session/
└── vendor/
```

## 🗄️ Database Schema

### Users Table
- Stores all user types (Owner, Admin, Cashier, Worker, Customer)
- Includes worker-specific fields (skills, experience, commission rate)

### Services Table
- Available services with categories and pricing
- Categories: mechanic, electrician, plumber, technician, general

### Bookings Table
- Service requests with full lifecycle tracking
- Status: pending, assigned, in_progress, completed, cancelled, rejected

### Payments Table
- Customer payments and worker payouts
- Multiple payment methods supported

### Reviews Table
- Customer ratings and feedback
- Detailed rating categories (service quality, timeliness, professionalism)

## 🔧 Development

### Running Migrations
```bash
php spark migrate
```

### Creating New Migration
```bash
php spark make:migration create_new_table
```

### Running Tests
```bash
php spark test
```

### Code Generation
```bash
php spark make:controller API/NewController
php spark make:model NewModel
```

## 🔐 Security Features

- JWT Authentication
- Password Hashing
- Input Validation
- SQL Injection Protection
- XSS Protection
- CORS Configuration

## 📊 Business Logic

### Commission System
- Company receives full payment from customer
- Commission is deducted from labor fees
- Workers receive remaining amount via scheduled payouts

### Payment Flow
1. Customer creates booking
2. Worker assigned and completes service
3. Customer pays company (cash/e-wallet)
4. Company creates worker payout
5. Worker receives earnings

### User Roles
- **Admin**: Full system access, manage users, bookings, and payments
- **Finance**: Process payments, manage payouts, and view financial reports
- **Worker**: Accept bookings and provide services
- **Customer**: Request services and make payments

## 🚀 Deployment

### Production Setup

1. Set environment variables:
```env
CI_ENVIRONMENT = production
displayErrors = 0
JWT_SECRET = your-production-secret-key
```

2. Configure production database
3. Set up SSL certificates
4. Configure web server (Apache/Nginx)
5. Set up file permissions

### Apache Configuration
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/Backend/public
    ServerName yourdomain.com
    
    <Directory /path/to/Backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Email: support@skilllink.com
- GitHub Issues: https://github.com/Merdin0506/Skill-link-services/issues

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added review system and ratings
- **v1.2.0** - Enhanced payment processing
- **v1.3.0** - Added reporting and analytics

---

**SkillLink Services Backend API** - Connecting skilled workers with customers, one service at a time.
