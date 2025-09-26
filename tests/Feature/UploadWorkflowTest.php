<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Upload;
use App\Models\Image;
use App\Models\ImageVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
    }

    /**
     * Test complete image upload workflow
     */
    public function test_complete_image_upload_workflow()
    {
        // Create a test image file
        $testImage = UploadedFile::fake()->image('test.jpg', 1200, 800)->size(500);

        // Login user
        $this->actingAs($this->user);

        // Upload image via API
        $response = $this->postJson('/api/simple-image-upload', [
            'image' => $testImage
        ]);

        // Assertions
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('path', $responseData);
        $this->assertArrayHasKey('width', $responseData);
        $this->assertArrayHasKey('height', $responseData);

        // Verify upload record was created
        $upload = Upload::where('user_id', $this->user->id)->first();
        $this->assertNotNull($upload);
        $this->assertEquals('completed', $upload->status);
        $this->assertEquals('test.jpg', $upload->original_filename);
        $this->assertTrue($upload->file_size > 0);

        // Verify image record was created
        $image = Image::where('upload_id', $upload->id)->first();
        $this->assertNotNull($image);
        $this->assertEquals(1200, $image->width);
        $this->assertEquals(800, $image->height);
        $this->assertTrue($image->file_size > 0);

        // Verify image variants were created
        $variants = $image->variants;
        $this->assertCount(3, $variants);
        
        $variantNames = $variants->pluck('variant_name')->toArray();
        $this->assertContains('thumbnail', $variantNames);
        $this->assertContains('medium', $variantNames);
        $this->assertContains('large', $variantNames);

        // Verify file exists in storage
        $this->assertTrue(Storage::disk('public')->exists($image->file_path));
        
        foreach ($variants as $variant) {
            $this->assertTrue(Storage::disk('public')->exists($variant->file_path));
        }
    }

    /**
     * Test image upload with validation errors
     */
    public function test_image_upload_validation_errors()
    {
        // Login user
        $this->actingAs($this->user);

        // Test with no file
        $response = $this->postJson('/api/simple-image-upload', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);

        // Test with non-image file
        $textFile = UploadedFile::fake()->create('test.txt', 100);
        $response = $this->postJson('/api/simple-image-upload', [
            'image' => $textFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);

        // Test with oversized file
        $largeImage = UploadedFile::fake()->image('large.jpg', 1000, 1000)->size(15000); // 15MB
        $response = $this->postJson('/api/simple-image-upload', [
            'image' => $largeImage
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    /**
     * Test upload dashboard access
     */
    public function test_upload_dashboard_access()
    {
        // Create some test uploads
        $upload1 = Upload::create([
            'upload_id' => 'test_upload_1',
            'original_filename' => 'test1.jpg',
            'file_path' => 'uploads/images/test1.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'checksum' => 'test_checksum_1',
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        $upload2 = Upload::create([
            'upload_id' => 'test_upload_2',
            'original_filename' => 'test2.png',
            'file_path' => 'uploads/images/test2.png',
            'mime_type' => 'image/png',
            'file_size' => 2048,
            'checksum' => 'test_checksum_2',
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'failed',
            'user_id' => $this->user->id,
            'error_message' => 'Processing failed'
        ]);

        // Create image for upload1
        $image = Image::create([
            'filename' => 'test1.jpg',
            'file_path' => 'uploads/images/test1.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'width' => 800,
            'height' => 600,
            'checksum' => 'test_checksum_1',
            'upload_id' => $upload1->id,
            'user_id' => $this->user->id
        ]);

        // Login user
        $this->actingAs($this->user);

        // Access dashboard
        $response = $this->get('/upload/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Upload Dashboard');
        $response->assertSee('test1.jpg');
        $response->assertSee('test2.png');
        $response->assertSee('completed');
        $response->assertSee('failed');
    }

    /**
     * Test upload details API
     */
    public function test_upload_details_api()
    {
        // Create test upload with image
        $upload = Upload::create([
            'upload_id' => 'test_upload_details',
            'original_filename' => 'details_test.jpg',
            'file_path' => 'uploads/images/details_test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'checksum' => 'test_checksum_details',
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        $image = Image::create([
            'filename' => 'details_test.jpg',
            'file_path' => 'uploads/images/details_test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'width' => 800,
            'height' => 600,
            'checksum' => 'test_checksum_details',
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Create variants
        ImageVariant::create([
            'image_id' => $image->id,
            'variant_name' => 'thumbnail',
            'width' => 256,
            'height' => 192,
            'file_path' => 'uploads/images/variants/1/thumbnail.jpg',
            'filename' => 'thumbnail.jpg',
            'file_size' => 512,
            'checksum' => 'variant_checksum_1'
        ]);

        ImageVariant::create([
            'image_id' => $image->id,
            'variant_name' => 'medium',
            'width' => 512,
            'height' => 384,
            'file_path' => 'uploads/images/variants/1/medium.jpg',
            'filename' => 'medium.jpg',
            'file_size' => 1024,
            'checksum' => 'variant_checksum_2'
        ]);

        // Login user
        $this->actingAs($this->user);

        // Get upload details
        $response = $this->getJson("/api/upload/details/{$upload->id}");
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertEquals($upload->id, $responseData['id']);
        $this->assertEquals('test_upload_details', $responseData['upload_id']);
        $this->assertEquals('details_test.jpg', $responseData['original_filename']);
        $this->assertEquals('completed', $responseData['status']);

        // Verify images data
        $this->assertArrayHasKey('images', $responseData);
        $this->assertCount(1, $responseData['images']);
        
        $imageData = $responseData['images'][0];
        $this->assertEquals($image->id, $imageData['id']);
        $this->assertEquals(800, $imageData['width']);
        $this->assertEquals(600, $imageData['height']);

        // Verify variants data
        $this->assertArrayHasKey('variants', $imageData);
        $this->assertCount(2, $imageData['variants']);
        
        $variantNames = collect($imageData['variants'])->pluck('name')->toArray();
        $this->assertContains('thumbnail', $variantNames);
        $this->assertContains('medium', $variantNames);
    }

    /**
     * Test unauthorized access to upload endpoints
     */
    public function test_unauthorized_access_to_upload_endpoints()
    {
        // Test upload without authentication
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $response = $this->postJson('/api/simple-image-upload', [
            'image' => $testImage
        ]);
        $response->assertStatus(401);

        // Test dashboard access without authentication
        $response = $this->get('/upload/dashboard');
        $response->assertRedirect('/login');

        // Test upload details without authentication
        $response = $this->getJson('/api/upload/details/1');
        $response->assertStatus(401);
    }

    /**
     * Test CSV import workflow
     */
    public function test_csv_import_workflow()
    {
        // Create CSV content
        $csvContent = "sku,name,description,price,stock_quantity,category,brand,is_active\n";
        $csvContent .= "CSV-001,CSV Product 1,Description 1,19.99,100,Electronics,TestBrand,1\n";
        $csvContent .= "CSV-002,CSV Product 2,Description 2,29.99,50,Clothing,TestBrand,1\n";

        // Create CSV file
        $csvFile = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        // Login user
        $this->actingAs($this->user);

        // Import CSV
        $response = $this->postJson('/api/upload/csv', [
            'csv_file' => $csvFile
        ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals(2, $responseData['results']['total']);
        $this->assertEquals(2, $responseData['results']['imported']);
        $this->assertEquals(0, $responseData['results']['updated']);
        $this->assertEquals(0, $responseData['results']['invalid']);
        $this->assertEquals(0, $responseData['results']['duplicates']);
    }

    /**
     * Test concurrent upload handling
     */
    public function test_concurrent_upload_handling()
    {
        // Login user
        $this->actingAs($this->user);

        // Create multiple test images
        $images = [
            UploadedFile::fake()->image('test1.jpg', 800, 600),
            UploadedFile::fake()->image('test2.jpg', 1000, 800),
            UploadedFile::fake()->image('test3.jpg', 1200, 900)
        ];

        // Upload all images concurrently
        $responses = [];
        foreach ($images as $image) {
            $responses[] = $this->postJson('/api/simple-image-upload', [
                'image' => $image
            ]);
        }

        // Verify all uploads succeeded
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }

        // Verify all uploads were recorded
        $this->assertEquals(3, Upload::where('user_id', $this->user->id)->count());
        $this->assertEquals(3, Image::where('user_id', $this->user->id)->count());
    }
}
