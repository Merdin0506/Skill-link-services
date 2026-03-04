<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        // Insert initial services
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
            ]
        ];

        $this->db->table('services')->insertBatch($services);

        // Insert admin user
        $adminUser = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@skilllink.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'phone' => '+1234567890',
            'address' => 'Office Address',
            'user_type' => 'admin',
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('users')->insert($adminUser);

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
        echo "Admin Login: admin@skilllink.com / admin123\n";
        echo "Worker Login: juan.santos@skilllink.com / worker123\n";
        echo "Customer Login: ana.cruz@email.com / customer123\n";
    }
}
