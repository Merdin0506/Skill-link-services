<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        // Insert initial services - connected to WORKER_SKILL_OPTIONS via SERVICE_CATEGORY_SKILL_MAP
        $services = [
            [
                'name' => 'Electrical Repair',
                'description' => 'Professional electrical repair and installation services',
                'category' => 'electrician',
                'base_price' => 800.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Plumbing Services',
                'description' => 'Complete plumbing repair and installation',
                'category' => 'plumber',
                'base_price' => 600.00,
                'estimated_duration' => 90,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Car Repair',
                'description' => 'Automotive repair and maintenance services',
                'category' => 'mechanic',
                'base_price' => 1000.00,
                'estimated_duration' => 180,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Air Conditioning Repair',
                'description' => 'AC repair, installation, and maintenance',
                'category' => 'technician',
                'base_price' => 700.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'General Home Maintenance',
                'description' => 'General home repair and maintenance services',
                'category' => 'general',
                'base_price' => 500.00,
                'estimated_duration' => 60,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Painting Services',
                'description' => 'Interior and exterior painting, surface preparation, and finishing',
                'category' => 'painting',
                'base_price' => 500.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Carpentry Services',
                'description' => 'Professional carpentry, furniture repair, and installation services',
                'category' => 'carpentry',
                'base_price' => 600.00,
                'estimated_duration' => 150,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Appliance Repair',
                'description' => 'Repair and maintenance for household appliances',
                'category' => 'appliance',
                'base_price' => 750.00,
                'estimated_duration' => 90,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Gardening & Landscaping',
                'description' => 'Professional gardening, landscaping, and lawn maintenance services',
                'category' => 'gardening',
                'base_price' => 800.00,
                'estimated_duration' => 180,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Welding Services',
                'description' => 'Professional welding and metal fabrication services',
                'category' => 'welding',
                'base_price' => 1200.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Roofing Services',
                'description' => 'Roof repair, installation, and maintenance services',
                'category' => 'roofing',
                'base_price' => 2000.00,
                'estimated_duration' => 240,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Tile Installation',
                'description' => 'Professional tile installation and replacement services',
                'category' => 'tile',
                'base_price' => 900.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('services')->insertBatch($services);

        // Insert super admin user
        $adminUser = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'skilllinkservices06@gmail.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'phone' => '+1234567890',
            'address' => 'Office Address',
            'user_type' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('users')->insert($adminUser);

        // Insert finance user
        $financeUser = [
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'email' => 'finance@skilllink.com',
            'password' => password_hash('finance123', PASSWORD_DEFAULT),
            'phone' => '+1234567891',
            'address' => 'Finance Department',
            'user_type' => 'finance',
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('users')->insert($financeUser);

        // Insert sample workers
        $workers = [
            [
                'first_name' => 'Juan',
                'last_name' => 'Santos',
                'email' => 'juan.santos@skilllink.com',
                'password' => password_hash('worker123', PASSWORD_DEFAULT),
                'phone' => '+09123456789',
                'address' => 'Quezon City, Philippines',
                'user_type' => 'worker',
                'status' => 'active',
                'skills' => json_encode(['electrical', 'wiring', 'installation']),
                'experience_years' => 5,
                'commission_rate' => 20.00,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Reyes',
                'email' => 'maria.reyes@skilllink.com',
                'password' => password_hash('worker123', PASSWORD_DEFAULT),
                'phone' => '+09876543210',
                'address' => 'Manila, Philippines',
                'user_type' => 'worker',
                'status' => 'active',
                'skills' => json_encode(['plumbing', 'pipe repair', 'installation']),
                'experience_years' => 8,
                'commission_rate' => 20.00,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Garcia',
                'email' => 'carlos.garcia@skilllink.com',
                'password' => password_hash('worker123', PASSWORD_DEFAULT),
                'phone' => '+09234567890',
                'address' => 'Makati, Philippines',
                'user_type' => 'worker',
                'status' => 'active',
                'skills' => json_encode(['automotive', 'engine repair', 'diagnostics']),
                'experience_years' => 10,
                'commission_rate' => 20.00,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Aisha',
                'last_name' => 'Hardi',
                'email' => 'aisha.hardi@skilllink.com',
                'password' => password_hash('worker123', PASSWORD_DEFAULT),
                'phone' => '+09345678901',
                'address' => 'Cebu, Philippines',
                'user_type' => 'worker',
                'status' => 'active',
                'skills' => json_encode(['gardening', 'landscaping', 'lawn maintenance']),
                'experience_years' => 6,
                'commission_rate' => 18.00,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('users')->insertBatch($workers);

        // Insert sample customers
        $customers = [
            [
                'first_name' => 'Ana',
                'last_name' => 'Cruz',
                'email' => 'ana.cruz@email.com',
                'password' => password_hash('customer123', PASSWORD_DEFAULT),
                'phone' => '+09112223333',
                'address' => 'Pasay City, Philippines',
                'user_type' => 'customer',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Lopez',
                'email' => 'roberto.lopez@email.com',
                'password' => password_hash('customer123', PASSWORD_DEFAULT),
                'phone' => '+09445556666',
                'address' => 'Mandaluyong City, Philippines',
                'user_type' => 'customer',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('users')->insertBatch($customers);

        echo "Initial data seeded successfully!\n";
        echo "Super Admin Login: skilllinkservices06@gmail.com / admin123\n";
        echo "Finance Login: finance@skilllink.com / finance123\n";
        echo "Worker Login: juan.santos@skilllink.com / worker123\n";
        echo "Customer Login: ana.cruz@email.com / customer123\n";
        echo "\nBaseline Data Created:\n";
        echo "- 5 Services\n";
        echo "- 7 Users (1 Super Admin, 1 Finance, 3 Workers, 2 Customers)\n";
        echo "- No seeded bookings/payments (real data only)\n";
    }
}
