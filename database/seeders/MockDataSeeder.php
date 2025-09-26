<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MockDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create test users if they don't exist
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Generate mock products
        $this->generateMockProducts($user->id);
        
        // Generate mock CSV file
        $this->generateMockCsv();
        
        // Create storage directories
        $this->createStorageDirectories();
    }

    private function generateMockProducts(int $userId): void
    {
        $categories = ['Electronics', 'Clothing', 'Home & Garden', 'Sports', 'Books', 'Toys', 'Automotive', 'Health'];
        $brands = ['Apple', 'Samsung', 'Nike', 'Adidas', 'Sony', 'LG', 'Canon', 'Nikon', 'Generic'];
        
        $products = [];
        
        for ($i = 1; $i <= 1000; $i++) {
            $products[] = [
                'sku' => 'SKU-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => $this->generateProductName(),
                'description' => $this->generateDescription(),
                'price' => round(rand(100, 50000) / 100, 2),
                'stock_quantity' => rand(0, 1000),
                'category' => $categories[array_rand($categories)],
                'brand' => $brands[array_rand($brands)],
                'attributes' => json_encode([
                    'color' => $this->getRandomColor(),
                    'size' => $this->getRandomSize(),
                    'weight' => rand(1, 50) . 'kg',
                    'material' => $this->getRandomMaterial(),
                ]),
                'is_active' => rand(0, 10) > 1, // 90% active
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ];
        }
        
        // Insert in batches
        Product::insert($products);
    }

    private function generateMockCsv(): void
    {
        $csvPath = storage_path('app/mock_products.csv');
        $file = fopen($csvPath, 'w');
        
        // CSV Header
        fputcsv($file, [
            'sku', 'name', 'description', 'price', 'stock_quantity', 
            'category', 'brand', 'color', 'size', 'weight', 'material'
        ]);
        
        // Generate 10,000+ rows
        for ($i = 1; $i <= 10000; $i++) {
            fputcsv($file, [
                'SKU-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                $this->generateProductName(),
                $this->generateDescription(),
                round(rand(100, 50000) / 100, 2),
                rand(0, 1000),
                $this->getRandomCategory(),
                $this->getRandomBrand(),
                $this->getRandomColor(),
                $this->getRandomSize(),
                rand(1, 50) . 'kg',
                $this->getRandomMaterial(),
            ]);
        }
        
        fclose($file);
    }

    private function createStorageDirectories(): void
    {
        $directories = [
            'uploads/chunks',
            'uploads/completed',
            'uploads/variants',
            'temp',
            'public/images',
        ];
        
        foreach ($directories as $dir) {
            Storage::makeDirectory($dir);
        }
    }

    private function generateProductName(): string
    {
        $adjectives = ['Premium', 'Professional', 'Deluxe', 'Standard', 'Basic', 'Advanced', 'Ultra', 'Super'];
        $nouns = ['Widget', 'Gadget', 'Device', 'Tool', 'Item', 'Product', 'Component', 'Accessory'];
        $types = ['Pro', 'Max', 'Plus', 'Lite', 'Mini', 'XL', 'HD', '4K'];
        
        $name = $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
        
        if (rand(0, 3) === 0) {
            $name .= ' ' . $types[array_rand($types)];
        }
        
        return $name;
    }

    private function generateDescription(): string
    {
        $descriptions = [
            'High-quality product with excellent features.',
            'Durable and reliable for everyday use.',
            'Modern design with advanced technology.',
            'Perfect for professional and personal use.',
            'Innovative solution for your needs.',
            'Premium materials and craftsmanship.',
            'User-friendly and easy to operate.',
            'Versatile and multifunctional design.',
        ];
        
        return $descriptions[array_rand($descriptions)];
    }

    private function getRandomCategory(): string
    {
        $categories = ['Electronics', 'Clothing', 'Home & Garden', 'Sports', 'Books', 'Toys', 'Automotive', 'Health'];
        return $categories[array_rand($categories)];
    }

    private function getRandomBrand(): string
    {
        $brands = ['Apple', 'Samsung', 'Nike', 'Adidas', 'Sony', 'LG', 'Canon', 'Nikon', 'Generic'];
        return $brands[array_rand($brands)];
    }

    private function getRandomColor(): string
    {
        $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Yellow', 'Purple', 'Orange', 'Pink', 'Gray'];
        return $colors[array_rand($colors)];
    }

    private function getRandomSize(): string
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Small', 'Medium', 'Large', 'One Size'];
        return $sizes[array_rand($sizes)];
    }

    private function getRandomMaterial(): string
    {
        $materials = ['Cotton', 'Polyester', 'Metal', 'Plastic', 'Wood', 'Leather', 'Rubber', 'Glass', 'Ceramic'];
        return $materials[array_rand($materials)];
    }
}