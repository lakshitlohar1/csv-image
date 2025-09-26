<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageProcessingService;
use App\Models\Image;
use App\Models\ImageVariant;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $imageProcessingService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageProcessingService = new ImageProcessingService();
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
    }

    /**
     * Test image processing with JPEG file
     */
    public function test_process_jpeg_image_creates_variants()
    {
        // Create a test JPEG image
        $testImagePath = $this->createTestImage('jpeg', 1200, 800);
        
        // Create upload record
        $upload = Upload::create([
            'upload_id' => 'test_upload_' . time(),
            'original_filename' => 'test.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'checksum' => hash_file('md5', $testImagePath),
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        // Create image record
        $image = Image::create([
            'filename' => 'test.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'width' => 1200,
            'height' => 800,
            'checksum' => hash_file('md5', $testImagePath),
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Process image
        $result = $this->imageProcessingService->processImage($image);

        // Assertions
        $this->assertTrue($result);
        
        // Verify variants were created
        $variants = $image->variants;
        $this->assertCount(3, $variants);
        
        // Check thumbnail variant
        $thumbnail = $variants->where('variant_name', 'thumbnail')->first();
        $this->assertNotNull($thumbnail);
        $this->assertEquals(256, $thumbnail->width);
        $this->assertTrue($thumbnail->height <= 256);
        $this->assertTrue($thumbnail->height > 0);
        
        // Check medium variant
        $medium = $variants->where('variant_name', 'medium')->first();
        $this->assertNotNull($medium);
        $this->assertEquals(512, $medium->width);
        $this->assertTrue($medium->height <= 512);
        $this->assertTrue($medium->height > 0);
        
        // Check large variant
        $large = $variants->where('variant_name', 'large')->first();
        $this->assertNotNull($large);
        $this->assertEquals(1024, $large->width);
        $this->assertTrue($large->height <= 1024);
        $this->assertTrue($large->height > 0);

        // Verify aspect ratios are maintained
        $originalRatio = 1200 / 800;
        $this->assertEqualsWithDelta($originalRatio, $thumbnail->width / $thumbnail->height, 0.1);
        $this->assertEqualsWithDelta($originalRatio, $medium->width / $medium->height, 0.1);
        $this->assertEqualsWithDelta($originalRatio, $large->width / $large->height, 0.1);

        // Clean up
        $this->cleanupTestFiles([$testImagePath]);
    }

    /**
     * Test image processing with PNG file
     */
    public function test_process_png_image_creates_variants()
    {
        // Create a test PNG image
        $testImagePath = $this->createTestImage('png', 800, 600);
        
        // Create upload record
        $upload = Upload::create([
            'upload_id' => 'test_upload_' . time(),
            'original_filename' => 'test.png',
            'file_path' => $testImagePath,
            'mime_type' => 'image/png',
            'file_size' => filesize($testImagePath),
            'checksum' => hash_file('md5', $testImagePath),
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        // Create image record
        $image = Image::create([
            'filename' => 'test.png',
            'file_path' => $testImagePath,
            'mime_type' => 'image/png',
            'file_size' => filesize($testImagePath),
            'width' => 800,
            'height' => 600,
            'checksum' => hash_file('md5', $testImagePath),
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Process image
        $result = $this->imageProcessingService->processImage($image);

        // Assertions
        $this->assertTrue($result);
        
        // Verify variants were created
        $variants = $image->variants;
        $this->assertCount(3, $variants);
        
        // Check that all variants exist and have correct dimensions
        foreach (['thumbnail', 'medium', 'large'] as $variantName) {
            $variant = $variants->where('variant_name', $variantName)->first();
            $this->assertNotNull($variant);
            $this->assertTrue($variant->width > 0);
            $this->assertTrue($variant->height > 0);
            $this->assertTrue($variant->file_size > 0);
            $this->assertNotEmpty($variant->checksum);
        }

        // Clean up
        $this->cleanupTestFiles([$testImagePath]);
    }

    /**
     * Test image processing with small image (smaller than largest variant)
     */
    public function test_process_small_image_handles_upscaling()
    {
        // Create a small test image (200x150)
        $testImagePath = $this->createTestImage('jpeg', 200, 150);
        
        // Create upload record
        $upload = Upload::create([
            'upload_id' => 'test_upload_' . time(),
            'original_filename' => 'small.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'checksum' => hash_file('md5', $testImagePath),
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        // Create image record
        $image = Image::create([
            'filename' => 'small.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'width' => 200,
            'height' => 150,
            'checksum' => hash_file('md5', $testImagePath),
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Process image
        $result = $this->imageProcessingService->processImage($image);

        // Assertions
        $this->assertTrue($result);
        
        // Verify variants were created (should not exceed original dimensions)
        $variants = $image->variants;
        $this->assertCount(3, $variants);
        
        // Check that variants don't exceed original dimensions
        foreach ($variants as $variant) {
            $this->assertLessThanOrEqual(200, $variant->width);
            $this->assertLessThanOrEqual(150, $variant->height);
        }

        // Clean up
        $this->cleanupTestFiles([$testImagePath]);
    }

    /**
     * Test image processing with non-existent file
     */
    public function test_process_image_with_missing_file()
    {
        // Create image record with non-existent file
        $image = Image::create([
            'filename' => 'missing.jpg',
            'file_path' => 'uploads/images/nonexistent/missing.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
            'width' => 800,
            'height' => 600,
            'checksum' => 'dummy',
            'upload_id' => 999,
            'user_id' => $this->user->id
        ]);

        // Process image
        $result = $this->imageProcessingService->processImage($image);

        // Assertions
        $this->assertFalse($result);
        
        // Verify no variants were created
        $this->assertEquals(0, $image->variants->count());
    }

    /**
     * Test image processing with invalid image file
     */
    public function test_process_image_with_invalid_file()
    {
        // Create a text file instead of image
        $testFilePath = storage_path('app/public/test.txt');
        file_put_contents($testFilePath, 'This is not an image');
        
        // Create upload record
        $upload = Upload::create([
            'upload_id' => 'test_upload_' . time(),
            'original_filename' => 'test.txt',
            'file_path' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => filesize($testFilePath),
            'checksum' => hash_file('md5', $testFilePath),
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        // Create image record
        $image = Image::create([
            'filename' => 'test.txt',
            'file_path' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => filesize($testFilePath),
            'width' => 0,
            'height' => 0,
            'checksum' => hash_file('md5', $testFilePath),
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Process image
        $result = $this->imageProcessingService->processImage($image);

        // Assertions
        $this->assertFalse($result);
        
        // Verify no variants were created
        $this->assertEquals(0, $image->variants->count());

        // Clean up
        unlink($testFilePath);
    }

    /**
     * Test variant regeneration
     */
    public function test_regenerate_variants()
    {
        // Create a test image
        $testImagePath = $this->createTestImage('jpeg', 1000, 800);
        
        // Create upload record
        $upload = Upload::create([
            'upload_id' => 'test_upload_' . time(),
            'original_filename' => 'test.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'checksum' => hash_file('md5', $testImagePath),
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        // Create image record
        $image = Image::create([
            'filename' => 'test.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'width' => 1000,
            'height' => 800,
            'checksum' => hash_file('md5', $testImagePath),
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Process image first time
        $result1 = $this->imageProcessingService->processImage($image);
        $this->assertTrue($result1);
        $this->assertEquals(3, $image->variants->count());

        // Regenerate variants
        $result2 = $this->imageProcessingService->regenerateVariants($image);
        $this->assertTrue($result2);
        
        // Verify variants still exist
        $this->assertEquals(3, $image->variants->count());

        // Clean up
        $this->cleanupTestFiles([$testImagePath]);
    }

    /**
     * Test image deletion
     */
    public function test_delete_image()
    {
        // Create a test image
        $testImagePath = $this->createTestImage('jpeg', 800, 600);
        
        // Create upload record
        $upload = Upload::create([
            'upload_id' => 'test_upload_' . time(),
            'original_filename' => 'test.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'checksum' => hash_file('md5', $testImagePath),
            'total_chunks' => 1,
            'uploaded_chunks' => 1,
            'status' => 'completed',
            'user_id' => $this->user->id,
            'completed_at' => now()
        ]);

        // Create image record
        $image = Image::create([
            'filename' => 'test.jpg',
            'file_path' => $testImagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($testImagePath),
            'width' => 800,
            'height' => 600,
            'checksum' => hash_file('md5', $testImagePath),
            'upload_id' => $upload->id,
            'user_id' => $this->user->id
        ]);

        // Process image to create variants
        $this->imageProcessingService->processImage($image);
        $this->assertEquals(3, $image->variants->count());

        // Delete image
        $result = $this->imageProcessingService->deleteImage($image);
        $this->assertTrue($result);

        // Verify image and variants are deleted from database
        $this->assertNull(Image::find($image->id));
        $this->assertEquals(0, ImageVariant::where('image_id', $image->id)->count());

        // Clean up
        $this->cleanupTestFiles([$testImagePath]);
    }

    /**
     * Create a test image file
     */
    private function createTestImage($format, $width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        
        // Fill with gradient
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorallocate($image, 
                    (int)($x / $width * 255), 
                    (int)($y / $height * 255), 
                    128
                );
                imagesetpixel($image, $x, $y, $color);
            }
        }
        
        // Save image
        $filename = 'test_' . uniqid() . '.' . $format;
        $filepath = storage_path('app/public/' . $filename);
        
        if ($format === 'jpeg') {
            imagejpeg($image, $filepath, 90);
        } elseif ($format === 'png') {
            imagepng($image, $filepath);
        }
        
        imagedestroy($image);
        
        return $filename;
    }

    /**
     * Clean up test files
     */
    private function cleanupTestFiles($files)
    {
        foreach ($files as $file) {
            $fullPath = storage_path('app/public/' . $file);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
