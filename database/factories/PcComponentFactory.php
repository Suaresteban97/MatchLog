<?php

namespace Database\Factories;

use App\Models\PcComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

class PcComponentFactory extends Factory
{
    protected $model = PcComponent::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['cpu', 'gpu', 'ram', 'storage']);
        
        $names = [
            'cpu' => ['Intel Core i7-13700K', 'AMD Ryzen 7 7800X3D', 'Intel Core i5-12400'],
            'gpu' => ['NVIDIA GeForce RTX 4070', 'AMD Radeon RX 7800 XT', 'NVIDIA GeForce RTX 3060'],
            'ram' => ['16GB DDR5', '32GB DDR4', '64GB DDR5'],
            'storage' => ['1TB NVMe', '2TB SSD', '512GB NVMe']
        ];

        $brands = [
            'cpu' => ['Intel', 'AMD'],
            'gpu' => ['NVIDIA', 'AMD'],
            'ram' => [null],
            'storage' => [null]
        ];

        return [
            'type' => $type,
            'name' => $this->faker->randomElement($names[$type]),
            'brand' => $this->faker->randomElement($brands[$type]),
        ];
    }
}
