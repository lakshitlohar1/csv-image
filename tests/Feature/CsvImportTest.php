<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Services\CsvImportService;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_service_can_be_instantiated()
    {
        $service = new CsvImportService();
        $this->assertInstanceOf(CsvImportService::class, $service);
    }

    public function test_csv_import_with_valid_data()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create a temporary CSV file
        $csvContent = "sku,name,price,stock_quantity,category,brand\n";
        $csvContent .= "TEST-001,Test Product 1,29.99,100,Electronics,TestBrand\n";
        $csvContent .= "TEST-002,Test Product 2,49.99,50,Clothing,AnotherBrand\n";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv');
        file_put_contents($tempFile, $csvContent);

        // Test the service
        $service = new CsvImportService();
        $results = $service->importProducts($tempFile, $user->id);

        // Assertions
        $this->assertEquals(2, $results['total']);
        $this->assertEquals(2, $results['imported']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEquals(0, $results['invalid']);

        // Clean up
        unlink($tempFile);
    }

    public function test_csv_import_validation()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create CSV with missing required fields
        $csvContent = "sku,name,price,stock_quantity,category,brand\n";
        $csvContent .= "TEST-001,Test Product 1,29.99,100,Electronics,TestBrand\n"; // Valid
        $csvContent .= ",Test Product 2,49.99,50,Clothing,AnotherBrand\n"; // Missing SKU
        $csvContent .= "TEST-003,,29.99,100,Electronics,TestBrand\n"; // Missing name
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv');
        file_put_contents($tempFile, $csvContent);

        // Test the service
        $service = new CsvImportService();
        $results = $service->importProducts($tempFile, $user->id);

        // Assertions
        $this->assertEquals(3, $results['total']);
        $this->assertEquals(1, $results['imported']); // Only one valid
        $this->assertEquals(0, $results['updated']);
        $this->assertEquals(2, $results['invalid']); // Two invalid

        // Clean up
        unlink($tempFile);
    }
}