<?php

namespace App\Services;

use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChunkedUploadService
{
    private string $chunkDirectory = 'uploads/chunks';
    private string $uploadDirectory = 'uploads/completed';
    private int $maxChunkSize = 1024 * 1024; // 1MB chunks

    /**
     * Initialize a new chunked upload
     */
    public function initializeUpload(array $uploadData, int $userId): Upload
    {
        $uploadId = Str::uuid()->toString();
        
        $upload = Upload::create([
            'upload_id' => $uploadId,
            'original_filename' => $uploadData['filename'],
            'file_path' => '', // Will be set when completed
            'mime_type' => $uploadData['mime_type'],
            'file_size' => $uploadData['file_size'],
            'checksum' => $uploadData['checksum'],
            'total_chunks' => $uploadData['total_chunks'],
            'uploaded_chunks' => 0,
            'chunk_checksums' => [],
            'status' => 'pending',
            'user_id' => $userId,
        ]);

        return $upload;
    }

    /**
     * Upload a chunk
     */
    public function uploadChunk(string $uploadId, int $chunkNumber, UploadedFile $chunk, string $chunkChecksum): array
    {
        try {
            $upload = Upload::where('upload_id', $uploadId)->first();
            
            if (!$upload) {
                return ['success' => false, 'error' => 'Upload not found'];
            }

            if ($upload->status === 'completed') {
                return ['success' => true, 'message' => 'Upload already completed'];
            }

            // Validate chunk checksum
            if (!$this->validateChunkChecksum($chunk, $chunkChecksum)) {
                return ['success' => false, 'error' => 'Chunk checksum mismatch'];
            }

            // Store chunk
            $chunkPath = $this->storeChunk($uploadId, $chunkNumber, $chunk);
            
            if (!$chunkPath) {
                return ['success' => false, 'error' => 'Failed to store chunk'];
            }

            // Update upload progress
            $this->updateUploadProgress($upload, $chunkNumber, $chunkChecksum);

            // Check if all chunks are uploaded
            if ($upload->uploaded_chunks >= $upload->total_chunks) {
                $this->completeUpload($upload);
            }

            return [
                'success' => true,
                'progress' => $upload->getProgressPercentageAttribute(),
                'uploaded_chunks' => $upload->uploaded_chunks,
                'total_chunks' => $upload->total_chunks
            ];

        } catch (\Exception $e) {
            Log::error('Chunk upload failed', [
                'upload_id' => $uploadId,
                'chunk_number' => $chunkNumber,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Resume an upload
     */
    public function resumeUpload(string $uploadId): array
    {
        $upload = Upload::where('upload_id', $uploadId)->first();
        
        if (!$upload) {
            return ['success' => false, 'error' => 'Upload not found'];
        }

        if ($upload->status === 'completed') {
            return [
                'success' => true,
                'status' => 'completed',
                'file_path' => $upload->file_path
            ];
        }

        return [
            'success' => true,
            'status' => $upload->status,
            'uploaded_chunks' => $upload->uploaded_chunks,
            'total_chunks' => $upload->total_chunks,
            'progress' => $upload->getProgressPercentageAttribute()
        ];
    }

    /**
     * Store a chunk
     */
    private function storeChunk(string $uploadId, int $chunkNumber, UploadedFile $chunk): ?string
    {
        try {
            $chunkPath = "{$this->chunkDirectory}/{$uploadId}/chunk_{$chunkNumber}";
            
            Storage::put($chunkPath, file_get_contents($chunk->getPathname()));
            
            return $chunkPath;
            
        } catch (\Exception $e) {
            Log::error('Failed to store chunk', [
                'upload_id' => $uploadId,
                'chunk_number' => $chunkNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate chunk checksum
     */
    private function validateChunkChecksum(UploadedFile $chunk, string $expectedChecksum): bool
    {
        $actualChecksum = hash_file('md5', $chunk->getPathname());
        return $actualChecksum === $expectedChecksum;
    }

    /**
     * Update upload progress
     */
    private function updateUploadProgress(Upload $upload, int $chunkNumber, string $chunkChecksum): void
    {
        $chunkChecksums = $upload->chunk_checksums ?? [];
        $chunkChecksums[$chunkNumber] = $chunkChecksum;
        
        $upload->update([
            'uploaded_chunks' => $upload->uploaded_chunks + 1,
            'chunk_checksums' => $chunkChecksums,
            'status' => 'uploading'
        ]);
    }

    /**
     * Complete the upload by combining chunks
     */
    private function completeUpload(Upload $upload): void
    {
        try {
            $finalPath = $this->combineChunks($upload);
            
            if (!$finalPath) {
                $upload->markAsFailed('Failed to combine chunks');
                return;
            }

            // Verify final file checksum
            if (!$this->verifyFinalChecksum($finalPath, $upload->checksum)) {
                $upload->markAsFailed('Final file checksum mismatch');
                Storage::delete($finalPath);
                return;
            }

            // Update upload record
            $upload->update([
                'file_path' => $finalPath,
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Clean up chunks
            $this->cleanupChunks($upload->upload_id);

        } catch (\Exception $e) {
            $upload->markAsFailed('Upload completion failed: ' . $e->getMessage());
            Log::error('Upload completion failed', [
                'upload_id' => $upload->upload_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Combine all chunks into final file
     */
    private function combineChunks(Upload $upload): ?string
    {
        try {
            $finalPath = "{$this->uploadDirectory}/{$upload->upload_id}/{$upload->original_filename}";
            
            // Create directory if it doesn't exist
            Storage::makeDirectory(dirname($finalPath));
            
            $finalFile = fopen(Storage::path($finalPath), 'wb');
            
            if (!$finalFile) {
                return null;
            }

            // Combine chunks in order
            for ($i = 1; $i <= $upload->total_chunks; $i++) {
                $chunkPath = "{$this->chunkDirectory}/{$upload->upload_id}/chunk_{$i}";
                
                if (!Storage::exists($chunkPath)) {
                    fclose($finalFile);
                    Storage::delete($finalPath);
                    return null;
                }
                
                $chunkContent = Storage::get($chunkPath);
                fwrite($finalFile, $chunkContent);
            }
            
            fclose($finalFile);
            
            return $finalPath;
            
        } catch (\Exception $e) {
            Log::error('Failed to combine chunks', [
                'upload_id' => $upload->upload_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verify final file checksum
     */
    private function verifyFinalChecksum(string $filePath, string $expectedChecksum): bool
    {
        try {
            $actualChecksum = hash_file('md5', Storage::path($filePath));
            return $actualChecksum === $expectedChecksum;
        } catch (\Exception $e) {
            Log::error('Failed to verify final checksum', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up chunk files
     */
    private function cleanupChunks(string $uploadId): void
    {
        try {
            $chunkDir = "{$this->chunkDirectory}/{$uploadId}";
            Storage::deleteDirectory($chunkDir);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup chunks', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel an upload
     */
    public function cancelUpload(string $uploadId): bool
    {
        try {
            $upload = Upload::where('upload_id', $uploadId)->first();
            
            if (!$upload) {
                return false;
            }

            $upload->update(['status' => 'cancelled']);
            
            // Clean up any existing chunks
            $this->cleanupChunks($uploadId);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to cancel upload', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get upload status
     */
    public function getUploadStatus(string $uploadId): ?array
    {
        $upload = Upload::where('upload_id', $uploadId)->first();
        
        if (!$upload) {
            return null;
        }

        return [
            'upload_id' => $upload->upload_id,
            'status' => $upload->status,
            'progress' => $upload->getProgressPercentageAttribute(),
            'uploaded_chunks' => $upload->uploaded_chunks,
            'total_chunks' => $upload->total_chunks,
            'file_path' => $upload->file_path,
            'error_message' => $upload->error_message
        ];
    }
}
