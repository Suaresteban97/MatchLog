<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PcComponent;

class PcComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            // CPUs - Intel
            ['type' => 'cpu', 'name' => 'Intel Core i3-12100', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i5-12400', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i5-13400', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i5-14400', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i7-12700K', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i7-13700K', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i7-14700K', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i9-13900K', 'brand' => 'Intel'],
            ['type' => 'cpu', 'name' => 'Intel Core i9-14900K', 'brand' => 'Intel'],
            
            // CPUs - AMD
            ['type' => 'cpu', 'name' => 'AMD Ryzen 5 5600', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 5 5600X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 5 7600', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 5 7600X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 7 5700X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 7 5800X3D', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 7 7700X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 7 7800X3D', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 9 5900X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 9 7900X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 9 7950X', 'brand' => 'AMD'],
            ['type' => 'cpu', 'name' => 'AMD Ryzen 9 7950X3D', 'brand' => 'AMD'],

            // GPUs - NVIDIA
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce GTX 1650', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce GTX 1660 Super', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3050', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3060', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3060 Ti', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3070', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3070 Ti', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3080', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 3090', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4060', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4060 Ti', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4070', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4070 Super', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4070 Ti', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4080', 'brand' => 'NVIDIA'],
            ['type' => 'gpu', 'name' => 'NVIDIA GeForce RTX 4090', 'brand' => 'NVIDIA'],

            // GPUs - AMD
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6600', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6600 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6700 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6750 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6800', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6800 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 6900 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 7600', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 7700 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 7800 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 7900 XT', 'brand' => 'AMD'],
            ['type' => 'gpu', 'name' => 'AMD Radeon RX 7900 XTX', 'brand' => 'AMD'],

            // RAM
            ['type' => 'ram', 'name' => '8GB DDR4', 'brand' => null],
            ['type' => 'ram', 'name' => '16GB DDR4', 'brand' => null],
            ['type' => 'ram', 'name' => '32GB DDR4', 'brand' => null],
            ['type' => 'ram', 'name' => '64GB DDR4', 'brand' => null],
            ['type' => 'ram', 'name' => '8GB DDR5', 'brand' => null],
            ['type' => 'ram', 'name' => '16GB DDR5', 'brand' => null],
            ['type' => 'ram', 'name' => '32GB DDR5', 'brand' => null],
            ['type' => 'ram', 'name' => '64GB DDR5', 'brand' => null],
            ['type' => 'ram', 'name' => '128GB DDR5', 'brand' => null],

            // Storage
            ['type' => 'storage', 'name' => '256GB SSD', 'brand' => null],
            ['type' => 'storage', 'name' => '512GB SSD', 'brand' => null],
            ['type' => 'storage', 'name' => '1TB SSD', 'brand' => null],
            ['type' => 'storage', 'name' => '2TB SSD', 'brand' => null],
            ['type' => 'storage', 'name' => '4TB SSD', 'brand' => null],
            ['type' => 'storage', 'name' => '256GB NVMe', 'brand' => null],
            ['type' => 'storage', 'name' => '512GB NVMe', 'brand' => null],
            ['type' => 'storage', 'name' => '1TB NVMe', 'brand' => null],
            ['type' => 'storage', 'name' => '2TB NVMe', 'brand' => null],
            ['type' => 'storage', 'name' => '4TB NVMe', 'brand' => null],
            ['type' => 'storage', 'name' => '1TB HDD', 'brand' => null],
            ['type' => 'storage', 'name' => '2TB HDD', 'brand' => null],
            ['type' => 'storage', 'name' => '4TB HDD', 'brand' => null],
        ];

        foreach ($components as $component) {
            PcComponent::create($component);
        }
    }
}
