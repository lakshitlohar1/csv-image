<?php

namespace App\Http\Controllers;

use App\Services\ChunkedUploadService;
use App\Services\ImageProcessingService;
use App\Services\CsvImportService;
use App\Models\Upload;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    private ChunkedUploadService $chunkedUploadService;
    private ImageProcessingService $imageProcessingService;
    private CsvImportService $csvImportService;

    public function __construct(
        ChunkedUploadService $chunkedUploadService,
        ImageProcessingService $imageProcessingService,
        CsvImportService $csvImportService
    ) {
        $this->chunkedUploadService = $chunkedUploadService;
        $this->imageProcessingService = $imageProcessingService;
        $this->csvImportService = $csvImportService;
    }

    /**
     * Show upload interface
     */
    public function index()
    {
        return view('upload.index');
    }

    /**
     * Initialize chunked upload
     */
    public function initializeUpload(Request $request): JsonResponse
    {
         
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'mime_type' => 'required|string',
            'checksum' => 'required|string',
            'total_chunks' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $upload = $this->chunkedUploadService->initializeUpload(
                $request->all(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'upload_id' => $upload->upload_id,
                'message' => 'Upload initialized successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload chunk
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'chunk_number' => 'required|integer|min:1',
            'chunk' => 'required|file',
            'chunk_checksum' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->chunkedUploadService->uploadChunk(
                $request->upload_id,
                $request->chunk_number,
                $request->file('chunk'),
                $request->chunk_checksum
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import CSV file
     */
    public function importCsv(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $csvFile = $request->file('csv_file');
            $tempPath = $csvFile->store('temp');
            $fullPath = Storage::path($tempPath);

            $results = $this->csvImportService->importProducts($fullPath, auth()->id());

            Storage::delete($tempPath);

            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => 'CSV import completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'CSV import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple image upload with detailed logging
     */
    public function simpleImageUpload(Request $request): JsonResponse
    {
        \Log::info('=== SIMPLE IMAGE UPLOAD START ===');
        \Log::info('Request data:', $request->all());
        \Log::info('User ID:', [auth()->id()]);
        \Log::info('Has file:', [$request->hasFile('image')]);

        try {
            // Step 1: Validation
            \Log::info('Step 1: Starting validation');
            $validator = Validator::make($request->all(), [
                'image' => 'required|file|image|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            \Log::info('Step 1: Validation passed');

            // Step 2: Get file details
            \Log::info('Step 2: Getting file details');
            $file = $request->file('image');
            \Log::info('File details:', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError()
            ]);

            // Step 3: Create storage directories
            \Log::info('Step 3: Creating storage directories');
            $uploadDir = 'uploads/images/' . date('Y/m/d');
            \Log::info('Upload directory:', [$uploadDir]);

            // Step 4: Store file in public directory
            \Log::info('Step 4: Storing file');
            $path = $file->store($uploadDir, 'public');
            \Log::info('File stored at:', [$path]);

            // Step 5: Get image dimensions
            \Log::info('Step 5: Getting image dimensions');
            $imageInfo = getimagesize($file->getPathname());
            $width = $imageInfo[0] ?? 0;
            $height = $imageInfo[1] ?? 0;
            \Log::info('Image dimensions:', ['width' => $width, 'height' => $height]);

            // Step 6: Create database records
            \Log::info('Step 6: Creating database records');
            
            try {
                // Create upload record
                $upload = \App\Models\Upload::create([
                    'upload_id' => 'simple_' . time() . '_' . uniqid(),
                    'original_filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'checksum' => hash_file('md5', $file->getPathname()),
                    'total_chunks' => 1,
                    'uploaded_chunks' => 1,
                    'status' => 'completed',
                    'user_id' => auth()->id(),
                    'completed_at' => now(),
                ]);
                \Log::info('Upload record created:', ['upload_id' => $upload->id]);
                
                // Create image record
                $image = \App\Models\Image::create([
                    'filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'width' => $width,
                    'height' => $height,
                    'checksum' => hash_file('md5', $file->getPathname()),
                    'upload_id' => $upload->id,
                    'user_id' => auth()->id(),
                ]);
                \Log::info('Image record created:', ['image_id' => $image->id]);
                
                // Process image variants
                \Log::info('Step 7: Processing image variants');
                $this->imageProcessingService->processImage($image);
                \Log::info('Image variants processed successfully');
                
            } catch (\Exception $dbError) {
                \Log::warning('Database record creation failed:', [
                    'error' => $dbError->getMessage(),
                    'file' => $dbError->getFile(),
                    'line' => $dbError->getLine()
                ]);
                // Continue with file upload success even if DB fails
            }
            
            \Log::info('=== SIMPLE IMAGE UPLOAD SUCCESS ===');

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'path' => $path,
                'width' => $width,
                'height' => $height,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

        } catch (\Exception $e) {
            \Log::error('Simple image upload failed:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show upload dashboard with statistics
     */
    public function dashboard()
    {
        $uploads = \App\Models\Upload::with(['images.variants', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $totalUploads = \App\Models\Upload::count();
        $completedUploads = \App\Models\Upload::where('status', 'completed')->count();
        $failedUploads = \App\Models\Upload::where('status', 'failed')->count();
        $totalImages = \App\Models\Image::count();

        return view('upload.dashboard', compact(
            'uploads', 
            'totalUploads', 
            'completedUploads', 
            'failedUploads', 
            'totalImages'
        ));
    }

    /**
     * Get upload details for modal
     */
    public function getUploadDetails($id)
    {
        $upload = \App\Models\Upload::with(['images.variants', 'user'])->findOrFail($id);
        
        return response()->json([
            'id' => $upload->id,
            'upload_id' => $upload->upload_id,
            'original_filename' => $upload->original_filename,
            'file_path' => $upload->file_path,
            'file_size' => $upload->file_size,
            'mime_type' => $upload->mime_type,
            'status' => $upload->status,
            'checksum' => $upload->checksum,
            'uploaded_chunks' => $upload->uploaded_chunks,
            'total_chunks' => $upload->total_chunks,
            'error_message' => $upload->error_message,
            'created_at' => $upload->created_at->format('M d, Y H:i:s'),
            'images' => $upload->images->map(function($image) {
                return [
                    'id' => $image->id,
                    'filename' => $image->filename,
                    'file_path' => $image->file_path,
                    'width' => $image->width,
                    'height' => $image->height,
                    'file_size' => $image->file_size,
                    'mime_type' => $image->mime_type,
                    'variants' => $image->variants->map(function($variant) {
                        return [
                            'name' => $variant->variant_name,
                            'width' => $variant->width,
                            'height' => $variant->height,
                            'file_path' => $variant->file_path
                        ];
                    })
                ];
            })
        ]);
    }
}
