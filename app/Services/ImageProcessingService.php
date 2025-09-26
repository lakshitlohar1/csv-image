<?php

namespace App\Services;

use App\Models\Image;
use App\Models\ImageVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageProcessingService
{
    private array $variantSizes = [
        'thumbnail' => 256,
        'medium' => 512,
        'large' => 1024
    ];

    /**
     * Process uploaded image and create variants
     */
    public function processImage(Image $image): bool
    {
        try {
            $sourcePath = Storage::disk('public')->path($image->file_path);
            
            if (!file_exists($sourcePath)) {
                Log::error('Source image file not found', ['image_id' => $image->id, 'path' => $sourcePath]);
                return false;
            }

            // Get image info
            $imageInfo = $this->getImageInfo($sourcePath);
            if (!$imageInfo) {
                return false;
            }

            // Create variants using basic PHP GD functions
            foreach ($this->variantSizes as $variantName => $maxSize) {
                $this->createVariant($image, $sourcePath, $variantName, $maxSize, $imageInfo);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create a specific variant using PHP GD
     */
    private function createVariant(Image $image, string $sourcePath, string $variantName, int $maxSize, array $imageInfo): void
    {
        try {
            // Calculate dimensions while maintaining aspect ratio
            $dimensions = $this->calculateDimensions(
                $imageInfo['width'],
                $imageInfo['height'],
                $maxSize
            );

            // Create source image resource
            $sourceImage = $this->createImageResource($sourcePath, $imageInfo['type']);
            if (!$sourceImage) {
                return;
            }

            // Create variant image
            $variantImage = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
            
            // Preserve transparency for PNG and GIF
            if ($imageInfo['type'] == IMAGETYPE_PNG || $imageInfo['type'] == IMAGETYPE_GIF) {
                imagealphablending($variantImage, false);
                imagesavealpha($variantImage, true);
                $transparent = imagecolorallocatealpha($variantImage, 255, 255, 255, 127);
                imagefill($variantImage, 0, 0, $transparent);
            }

            // Resize image
            imagecopyresampled(
                $variantImage, $sourceImage,
                0, 0, 0, 0,
                $dimensions['width'], $dimensions['height'],
                $imageInfo['width'], $imageInfo['height']
            );

            // Generate file path and filename
            $variantPath = $this->generateVariantPath($image, $variantName);
            $variantFilename = $this->generateVariantFilename($image, $variantName);
            
            // Save variant
            $this->saveImageResource($variantImage, Storage::disk('public')->path($variantPath), $imageInfo['type']);
            
            // Create database record
            ImageVariant::create([
                'image_id' => $image->id,
                'variant_name' => $variantName,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'file_path' => $variantPath,
                'filename' => $variantFilename,
                'file_size' => Storage::disk('public')->size($variantPath),
                'checksum' => hash_file('md5', Storage::disk('public')->path($variantPath))
            ]);

            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($variantImage);

        } catch (\Exception $e) {
            Log::error('Variant creation failed', [
                'image_id' => $image->id,
                'variant_name' => $variantName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate dimensions while maintaining aspect ratio
     */
    private function calculateDimensions(int $originalWidth, int $originalHeight, int $maxSize): array
    {
        $aspectRatio = $originalWidth / $originalHeight;
        
        if ($originalWidth > $originalHeight) {
            // Landscape
            $newWidth = min($maxSize, $originalWidth);
            $newHeight = (int) round($newWidth / $aspectRatio);
        } else {
            // Portrait or square
            $newHeight = min($maxSize, $originalHeight);
            $newWidth = (int) round($newHeight * $aspectRatio);
        }
        
        return [
            'width' => $newWidth,
            'height' => $newHeight
        ];
    }

    /**
     * Generate variant file path
     */
    private function generateVariantPath(Image $image, string $variantName): string
    {
        $pathInfo = pathinfo($image->file_path);
        $directory = dirname($pathInfo['dirname']) . '/variants/' . $image->id;
        $filename = $pathInfo['filename'] . '_' . $variantName . '.' . $pathInfo['extension'];
        
        return $directory . '/' . $filename;
    }

    /**
     * Generate variant filename
     */
    private function generateVariantFilename(Image $image, string $variantName): string
    {
        $pathInfo = pathinfo($image->filename);
        return $pathInfo['filename'] . '_' . $variantName . '.' . $pathInfo['extension'];
    }

    /**
     * Process image from upload
     */
    public function processImageFromUpload(string $uploadId, int $userId): ?Image
    {
        try {
            $upload = \App\Models\Upload::where('upload_id', $uploadId)->first();
            
            if (!$upload || !$upload->isCompleted()) {
                return null;
            }

            // Get image dimensions
            $imageInfo = $this->getImageInfo(Storage::disk('public')->path($upload->file_path));
            
            if (!$imageInfo) {
                return null;
            }

            // Create image record
            $image = Image::create([
                'filename' => $upload->original_filename,
                'file_path' => $upload->file_path,
                'mime_type' => $upload->mime_type,
                'file_size' => $upload->file_size,
                'width' => $imageInfo['width'],
                'height' => $imageInfo['height'],
                'checksum' => $upload->checksum,
                'upload_id' => $upload->id,
                'user_id' => $userId,
            ]);

            // Process variants
            $this->processImage($image);

            return $image;

        } catch (\Exception $e) {
            Log::error('Failed to process image from upload', [
                'upload_id' => $uploadId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get image information using PHP GD
     */
    private function getImageInfo(string $filePath): ?array
    {
        try {
            $imageInfo = getimagesize($filePath);
            
            if (!$imageInfo) {
                return null;
            }
            
            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'type' => $imageInfo[2],
                'mime_type' => $imageInfo['mime']
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get image info', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create image resource from file
     */
    private function createImageResource(string $filePath, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    /**
     * Save image resource to file
     */
    private function saveImageResource($imageResource, string $filePath, int $imageType): bool
    {
        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($imageResource, $filePath, 85);
            case IMAGETYPE_PNG:
                return imagepng($imageResource, $filePath, 8);
            case IMAGETYPE_GIF:
                return imagegif($imageResource, $filePath);
            case IMAGETYPE_WEBP:
                return imagewebp($imageResource, $filePath, 85);
            default:
                return false;
        }
    }

    /**
     * Delete image and all variants
     */
    public function deleteImage(Image $image): bool
    {
        try {
            // Delete all variants
            foreach ($image->variants as $variant) {
                if (Storage::disk('public')->exists($variant->file_path)) {
                    Storage::disk('public')->delete($variant->file_path);
                }
                $variant->delete();
            }

            // Delete original image
            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }

            // Delete image record
            $image->delete();

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete image', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Regenerate variants for an image
     */
    public function regenerateVariants(Image $image): bool
    {
        try {
            // Delete existing variants
            foreach ($image->variants as $variant) {
                if (Storage::disk('public')->exists($variant->file_path)) {
                    Storage::disk('public')->delete($variant->file_path);
                }
                $variant->delete();
            }

            // Regenerate variants
            return $this->processImage($image);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate variants', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get variant URL
     */
    public function getVariantUrl(ImageVariant $variant): string
    {
        return Storage::disk('public')->url($variant->file_path);
    }

    /**
     * Get all variant URLs for an image
     */
    public function getImageVariantUrls(Image $image): array
    {
        $urls = [];
        
        foreach ($image->variants as $variant) {
            $urls[$variant->variant_name] = $this->getVariantUrl($variant);
        }
        
        return $urls;
    }

    /**
     * Validate image file
     */
    public function validateImageFile(string $filePath): bool
    {
        try {
            $imageInfo = getimagesize($filePath);
            return $imageInfo !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get supported image formats
     */
    public function getSupportedFormats(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }

    /**
     * Check if file is a supported image format
     */
    public function isSupportedImageFormat(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $this->getSupportedFormats());
    }
}