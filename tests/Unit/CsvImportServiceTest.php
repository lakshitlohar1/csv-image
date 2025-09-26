<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CsvImportService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CsvImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $csvImportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csvImportService = new CsvImportService();
    }

    /**
     * Test CSV import with upsert logic - create new products
     */
    public function test_csv_import_creates_new_products()
    {
        // Create test CSV content
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "TEST-001,Test Product 1,Description 1,19.99,100,Electronics,TestBrand,1\n";
        $csvContent .= "TEST-002,Test Product 2,Description 2,29.99,50,Clothing,TestBrand,1\n";

        // Create temporary CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Assertions
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['invalid']);
        $this->assertEquals(0, $result['duplicates']);
        $this->assertEmpty($result['errors']);

        // Verify products were created
        $this->assertEquals(2, Product::count());
        
        $product1 = Product::where('sku', 'TEST-001')->first();
        $this->assertNotNull($product1);
        $this->assertEquals('Test Product 1', $product1->name);
        $this->assertEquals(19.99, $product1->price);
        $this->assertEquals(100, $product1->stock_quantity);

        $product2 = Product::where('sku', 'TEST-002')->first();
        $this->assertNotNull($product2);
        $this->assertEquals('Test Product 2', $product2->name);
        $this->assertEquals(29.99, $product2->price);
        $this->assertEquals(50, $product2->stock_quantity);

        fclose($tempFile);
    }

    /**
     * Test CSV import with upsert logic - update existing products
     */
    public function test_csv_import_updates_existing_products()
    {
        // Create existing product
        $existingProduct = Product::create([
            'sku' => 'TEST-001',
            'name' => 'Original Name',
            'description' => 'Original Description',
            'price' => 10.00,
            'stock_quantity' => 25,
            'category' => 'Original Category',
            'brand' => 'Original Brand',
            'is_active' => 1
        ]);

        // Create test CSV content with updated data
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "TEST-001,Updated Name,Updated Description,25.99,75,Updated Category,Updated Brand,1\n";

        // Create temporary CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Assertions
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(0, $result['invalid']);
        $this->assertEquals(0, $result['duplicates']);

        // Verify product was updated
        $this->assertEquals(1, Product::count());
        
        $updatedProduct = Product::where('sku', 'TEST-001')->first();
        $this->assertNotNull($updatedProduct);
        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals('Updated Description', $updatedProduct->description);
        $this->assertEquals(25.99, $updatedProduct->price);
        $this->assertEquals(75, $updatedProduct->stock_quantity);
        $this->assertEquals('Updated Category', $updatedProduct->category);
        $this->assertEquals('Updated Brand', $updatedProduct->brand);

        fclose($tempFile);
    }

    /**
     * Test CSV import with invalid rows
     */
    public function test_csv_import_handles_invalid_rows()
    {
        // Create test CSV content with missing required fields
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "TEST-001,Valid Product,Description,19.99,100,Electronics,Brand,1\n";
        $csvContent .= "INVALID-SKU,,Missing Name,29.99,50,Clothing,Brand,1\n"; // Missing name
        $csvContent .= "TEST-003,Another Valid,Description,39.99,25,Books,Brand,1\n";

        // Create temporary CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Assertions
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['imported']); // Only 2 valid rows
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(1, $result['invalid']); // 1 invalid row
        $this->assertEquals(0, $result['duplicates']);

        // Verify only valid products were created
        $this->assertEquals(2, Product::count());
        
        $validProduct1 = Product::where('sku', 'TEST-001')->first();
        $this->assertNotNull($validProduct1);
        $this->assertEquals('Valid Product', $validProduct1->name);

        $validProduct2 = Product::where('sku', 'TEST-003')->first();
        $this->assertNotNull($validProduct2);
        $this->assertEquals('Another Valid', $validProduct2->name);

        // Verify invalid product was not created
        $invalidProduct = Product::where('sku', 'INVALID-SKU')->first();
        $this->assertNull($invalidProduct);

        fclose($tempFile);
    }

    /**
     * Test CSV import with duplicate SKUs in the same import
     */
    public function test_csv_import_handles_duplicate_skus()
    {
        // Create test CSV content with duplicate SKUs
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "TEST-001,First Product,Description 1,19.99,100,Electronics,Brand,1\n";
        $csvContent .= "TEST-002,Second Product,Description 2,29.99,50,Clothing,Brand,1\n";
        $csvContent .= "TEST-001,Duplicate Product,Description 3,39.99,75,Books,Brand,1\n"; // Duplicate SKU

        // Create temporary CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Assertions
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['imported']); // Only first 2 rows
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['invalid']);
        $this->assertEquals(1, $result['duplicates']); // 1 duplicate

        // Verify only first occurrence was imported
        $this->assertEquals(2, Product::count());
        
        $product1 = Product::where('sku', 'TEST-001')->first();
        $this->assertNotNull($product1);
        $this->assertEquals('First Product', $product1->name); // First occurrence

        $product2 = Product::where('sku', 'TEST-002')->first();
        $this->assertNotNull($product2);
        $this->assertEquals('Second Product', $product2->name);

        fclose($tempFile);
    }

    /**
     * Test CSV import with mixed scenarios (create, update, invalid, duplicate)
     */
    public function test_csv_import_mixed_scenarios()
    {
        // Create existing product
        Product::create([
            'sku' => 'EXISTING-001',
            'name' => 'Original Name',
            'description' => 'Original Description',
            'price' => 10.00,
            'stock_quantity' => 25,
            'category' => 'Original Category',
            'brand' => 'Original Brand',
            'is_active' => 1
        ]);

        // Create test CSV content with mixed scenarios
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "NEW-001,New Product 1,Description 1,19.99,100,Electronics,Brand,1\n"; // New
        $csvContent .= "EXISTING-001,Updated Name,Updated Description,25.99,75,Updated Category,Updated Brand,1\n"; // Update
        $csvContent .= "INVALID-SKU,,Missing Name,29.99,50,Clothing,Brand,1\n"; // Invalid
        $csvContent .= "NEW-002,New Product 2,Description 2,39.99,25,Books,Brand,1\n"; // New
        $csvContent .= "NEW-001,Duplicate Product,Description 3,49.99,30,Toys,Brand,1\n"; // Duplicate

        // Create temporary CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Assertions
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(2, $result['imported']); // 2 new products
        $this->assertEquals(1, $result['updated']); // 1 updated product
        $this->assertEquals(1, $result['invalid']); // 1 invalid row
        $this->assertEquals(1, $result['duplicates']); // 1 duplicate

        // Verify final state
        $this->assertEquals(3, Product::count()); // 1 existing + 2 new
        
        // Verify new products
        $newProduct1 = Product::where('sku', 'NEW-001')->first();
        $this->assertNotNull($newProduct1);
        $this->assertEquals('New Product 1', $newProduct1->name);

        $newProduct2 = Product::where('sku', 'NEW-002')->first();
        $this->assertNotNull($newProduct2);
        $this->assertEquals('New Product 2', $newProduct2->name);

        // Verify updated product
        $updatedProduct = Product::where('sku', 'EXISTING-001')->first();
        $this->assertNotNull($updatedProduct);
        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals(25.99, $updatedProduct->price);

        // Verify invalid product was not created
        $invalidProduct = Product::where('sku', 'INVALID-SKU')->first();
        $this->assertNull($invalidProduct);

        fclose($tempFile);
    }

    /**
     * Test CSV import with empty file
     */
    public function test_csv_import_empty_file()
    {
        // Create empty CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, "");
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Assertions
        $this->assertNotEmpty($result['errors']);

        fclose($tempFile);
    }

    /**
     * Test CSV import with malformed CSV
     */
    public function test_csv_import_malformed_csv()
    {
        // Create malformed CSV content
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "TEST-001,Product with \"quotes\" and,commas,19.99,100,Electronics,Brand,1\n"; // Malformed

        // Create temporary CSV file
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Test import
        $result = $this->csvImportService->importProducts($tempPath, 1);

        // Should handle gracefully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);

        fclose($tempFile);
    }
}