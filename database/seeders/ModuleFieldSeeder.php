<?php

namespace Database\Seeders;

use App\Models\ModuleField;
use Illuminate\Database\Seeder;

class ModuleFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            'customers' => [
                'name' => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'birthday' => 'Birthday',
            ],
            'orders' => [
                'number' => 'Order Number',
                'customer_id' => 'Customer',
                'status' => 'Status',
                'total_price' => 'Total Price',
                'shipping_price' => 'Shipping Price',
                'shipping_method' => 'Shipping Method',
                'notes' => 'Notes',
            ],
        ];

        foreach ($fields as $moduleKey => $moduleFields) {
            foreach ($moduleFields as $fieldKey => $label) {
                ModuleField::updateOrCreate(
                    ['module_key' => $moduleKey, 'field_key' => $fieldKey],
                    ['label' => $label]
                );
            }
        }
    }
}
