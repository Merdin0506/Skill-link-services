<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissingServices extends Migration
{
    public function up()
    {
        // Add missing service categories
        $services = [
            [
                'name' => 'Painting Services',
                'description' => 'Interior and exterior painting, surface preparation, and finishing.',
                'category' => 'painting',
                'base_price' => 500.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Carpentry Services',
                'description' => 'Professional carpentry, furniture repair, and installation services.',
                'category' => 'carpentry',
                'base_price' => 600.00,
                'estimated_duration' => 150,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Appliance Repair',
                'description' => 'Repair and maintenance for household appliances.',
                'category' => 'appliance',
                'base_price' => 750.00,
                'estimated_duration' => 90,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Gardening & Landscaping',
                'description' => 'Professional gardening, landscaping, and lawn maintenance services.',
                'category' => 'gardening',
                'base_price' => 800.00,
                'estimated_duration' => 180,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Welding Services',
                'description' => 'Professional welding and metal fabrication services.',
                'category' => 'welding',
                'base_price' => 1200.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Roofing Services',
                'description' => 'Roof repair, installation, and maintenance services.',
                'category' => 'roofing',
                'base_price' => 2000.00,
                'estimated_duration' => 240,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Tile Installation',
                'description' => 'Professional tile installation and replacement services.',
                'category' => 'tile',
                'base_price' => 900.00,
                'estimated_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('services')->insertBatch($services);
    }

    public function down()
    {
        // Remove newly added services by category
        $this->db->table('services')
            ->whereIn('category', ['painting', 'carpentry', 'appliance', 'gardening', 'welding', 'roofing', 'tile'])
            ->delete();
    }
}
