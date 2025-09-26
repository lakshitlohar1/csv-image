# Bulk Upload Module Documentation

## Overview
This module implements a comprehensive bulk CSV import and chunked drag-and-drop image upload system with resumable uploads, image variant generation, and robust data validation.

## Features

### 🔄 **Chunked Upload System**
- **Resumable Uploads**: Upload can be resumed from where it left off
- **Checksum Validation**: Each chunk is validated for integrity
- **Progress Tracking**: Real-time upload progress with percentage
- **Error Handling**: Robust error handling with retry mechanisms
- **Concurrency Safe**: Multiple uploads can run simultaneously

### 📊 **CSV Import System**
- **Upsert Logic**: Create new products or update existing ones based on SKU
- **Bulk Processing**: Handle large CSV files (10,000+ rows)
- **Data Validation**: Comprehensive validation with detailed error reporting
- **Result Summary**: Detailed import results with counts and errors
- **Missing Column Handling**: Graceful handling of missing columns

### 🖼️ **Image Processing**
- **Variant Generation**: Automatic creation of 256px, 512px, 1024px variants
- **Aspect Ratio Preservation**: Maintains original aspect ratios
- **Multiple Formats**: Support for JPG, PNG, GIF, WebP
- **Optimization**: Automatic image optimization and compression

### 🛡️ **Security & Validation**
- **CSRF Protection**: All forms protected with CSRF tokens
- **File Type Validation**: Strict file type checking
- **Size Limits**: Configurable file size limits
- **Checksum Verification**: File integrity verification
- **User Isolation**: Users can only access their own uploads

## Database Schema

### Products Table
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    category VARCHAR(255) NULL,
    brand VARCHAR(255) NULL,
    attributes JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    primary_image_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_sku_active (sku, is_active),
    INDEX idx_category_active (category, is_active)
);
```

### Uploads Table
```sql
CREATE TABLE uploads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    upload_id VARCHAR(255) UNIQUE NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    checksum VARCHAR(255) NOT NULL,
    total_chunks INT NOT NULL,
    uploaded_chunks INT DEFAULT 0,
    chunk_checksums JSON NULL,
    status ENUM('pending', 'uploading', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_upload_status (upload_id, status),
    INDEX idx_user_status (user_id, status)
);
```

### Images Table
```sql
CREATE TABLE images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    width INT NOT NULL,
    height INT NOT NULL,
    checksum VARCHAR(255) NOT NULL,
    upload_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_upload (upload_id),
    INDEX idx_user (user_id),
    INDEX idx_checksum (checksum)
);
```

### Image Variants Table
```sql
CREATE TABLE image_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id BIGINT UNSIGNED NOT NULL,
    variant_name VARCHAR(255) NOT NULL,
    width INT NOT NULL,
    height INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    checksum VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    UNIQUE KEY unique_image_variant (image_id, variant_name),
    INDEX idx_image_variant (image_id, variant_name)
);
```

## API Endpoints

### Upload Endpoints
- `POST /api/upload/initialize` - Initialize chunked upload
- `POST /api/upload/chunk` - Upload file chunk
- `POST /api/upload/csv` - Import CSV file
- `GET /api/upload/status/{upload_id}` - Get upload status
- `POST /api/upload/cancel/{upload_id}` - Cancel upload

### Request/Response Examples

#### Initialize Upload
```json
POST /api/upload/initialize
{
    "filename": "image.jpg",
    "file_size": 1048576,
    "mime_type": "image/jpeg",
    "checksum": "abc123...",
    "total_chunks": 10
}

Response:
{
    "success": true,
    "upload_id": "upload_1234567890_abc123",
    "message": "Upload initialized successfully"
}
```

#### Upload Chunk
```json
POST /api/upload/chunk
FormData:
- upload_id: "upload_1234567890_abc123"
- chunk_number: 1
- chunk: [binary file data]
- chunk_checksum: "def456..."

Response:
{
    "success": true,
    "progress": 10,
    "uploaded_chunks": 1,
    "total_chunks": 10
}
```

#### CSV Import
```json
POST /api/upload/csv
FormData:
- csv_file: [CSV file]

Response:
{
    "success": true,
    "results": {
        "total": 1000,
        "imported": 950,
        "updated": 30,
        "invalid": 20,
        "duplicates": 0,
        "errors": []
    },
    "message": "CSV import completed"
}
```

## Services

### CsvImportService
Handles CSV file processing and product upsert operations.

**Key Methods:**
- `importProducts(string $filePath, int $userId): array`
- `linkImageToProduct(string $sku, int $imageId): bool`
- `validateCsvStructure(string $filePath): array`

**Features:**
- Upsert logic (create/update based on SKU)
- Data validation with detailed error reporting
- Missing column handling
- Attribute extraction from additional columns
- Transaction safety

### ChunkedUploadService
Manages chunked file uploads with resume capability.

**Key Methods:**
- `initializeUpload(array $uploadData, int $userId): Upload`
- `uploadChunk(string $uploadId, int $chunkNumber, UploadedFile $chunk, string $chunkChecksum): array`
- `resumeUpload(string $uploadId): array`
- `cancelUpload(string $uploadId): bool`

**Features:**
- Chunk-based upload with configurable chunk size
- Checksum validation for each chunk
- Resume capability for interrupted uploads
- Progress tracking
- Automatic cleanup of failed uploads

### ImageProcessingService
Handles image processing and variant generation.

**Key Methods:**
- `processImage(Image $image): bool`
- `processImageFromUpload(string $uploadId, int $userId): ?Image`
- `deleteImage(Image $image): bool`
- `regenerateVariants(Image $image): bool`

**Features:**
- Automatic variant generation (256px, 512px, 1024px)
- Aspect ratio preservation
- Multiple format support
- Image optimization
- Batch processing

## Frontend Implementation

### Drag & Drop Interface
- **HTML5 Drag & Drop**: Native browser drag and drop support
- **File Validation**: Client-side file type and size validation
- **Progress Visualization**: Real-time progress bars and status indicators
- **Queue Management**: Multiple file upload queue with individual progress
- **Error Handling**: User-friendly error messages and retry options

### JavaScript Features
- **Chunked Uploader Class**: ES6 class for managing uploads
- **Checksum Calculation**: Client-side MD5 checksum generation
- **Progress Tracking**: Real-time upload progress updates
- **Resume Capability**: Automatic resume of interrupted uploads
- **Error Recovery**: Automatic retry with exponential backoff

### UI Components
- **Drop Zone**: Visual drag and drop area with hover effects
- **Upload Queue**: List of files being uploaded with progress
- **Status Indicators**: Color-coded status badges
- **Progress Bars**: Animated progress bars for each upload
- **Statistics Dashboard**: Upload statistics and recent activity

## Testing

### Unit Tests
- **CsvImportServiceTest**: Tests upsert logic and data validation
- **ChunkedUploadServiceTest**: Tests upload functionality
- **ImageProcessingServiceTest**: Tests image processing and variants

### Test Coverage
- ✅ Product creation and updates
- ✅ Data validation and error handling
- ✅ CSV structure validation
- ✅ Image processing and variant generation
- ✅ Upload resume functionality
- ✅ Error recovery mechanisms

### Mock Data
- **10,000+ Product Records**: Generated with realistic data
- **Multiple Categories**: Electronics, Clothing, Home & Garden, etc.
- **Various Brands**: Apple, Samsung, Nike, Adidas, etc.
- **Random Attributes**: Colors, sizes, materials, weights
- **CSV Export**: Ready-to-use CSV file for testing

## Performance Considerations

### Database Optimization
- **Indexes**: Strategic indexes on frequently queried columns
- **Batch Processing**: Bulk inserts for large datasets
- **Transaction Management**: Proper transaction handling
- **Connection Pooling**: Efficient database connection usage

### File Storage
- **Chunked Storage**: Temporary chunk storage during upload
- **Cleanup Processes**: Automatic cleanup of failed uploads
- **Storage Optimization**: Efficient file organization
- **CDN Integration**: Ready for CDN integration

### Memory Management
- **Streaming Processing**: Large file processing without memory issues
- **Chunk Processing**: Memory-efficient chunk handling
- **Garbage Collection**: Proper cleanup of temporary resources

## Security Features

### File Security
- **Type Validation**: Strict file type checking
- **Size Limits**: Configurable file size restrictions
- **Path Traversal Protection**: Secure file path handling
- **Virus Scanning**: Ready for antivirus integration

### Data Security
- **CSRF Protection**: All forms protected with CSRF tokens
- **User Isolation**: Users can only access their own data
- **Input Sanitization**: All inputs properly sanitized
- **SQL Injection Prevention**: Parameterized queries

### Upload Security
- **Checksum Verification**: File integrity validation
- **Rate Limiting**: Upload rate limiting (ready for implementation)
- **Access Control**: Proper authentication and authorization
- **Audit Logging**: Comprehensive logging of all operations

## Configuration

### Environment Variables
```env
# Upload Configuration
UPLOAD_MAX_SIZE=10485760  # 10MB
UPLOAD_CHUNK_SIZE=1048576  # 1MB
UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,webp

# Storage Configuration
FILESYSTEM_DISK=local
STORAGE_PATH=uploads

# Image Processing
IMAGE_VARIANTS=256,512,1024
IMAGE_QUALITY=85
```

### Service Configuration
```php
// ChunkedUploadService
private int $maxChunkSize = 1024 * 1024; // 1MB

// ImageProcessingService
private array $variantSizes = [
    'thumbnail' => 256,
    'medium' => 512,
    'large' => 1024
];
```

## Deployment

### Requirements
- **PHP 8.2+**: Required for Laravel 12
- **MySQL 8.0+**: Database with JSON support
- **Storage**: Sufficient disk space for uploads
- **Memory**: Adequate memory for image processing

### Installation Steps
1. Run migrations: `php artisan migrate`
2. Seed mock data: `php artisan db:seed --class=MockDataSeeder`
3. Create storage directories: `php artisan storage:link`
4. Set proper permissions: `chmod -R 755 storage/`

### Production Considerations
- **Queue Workers**: For background image processing
- **Redis**: For session and cache storage
- **CDN**: For image delivery
- **Monitoring**: Upload progress and error monitoring

## Troubleshooting

### Common Issues
1. **Upload Failures**: Check file size limits and permissions
2. **Image Processing Errors**: Verify GD/Imagick extension
3. **CSV Import Issues**: Check file format and encoding
4. **Memory Issues**: Increase PHP memory limit

### Debug Tools
- **Log Files**: Comprehensive logging in `storage/logs/`
- **Database Queries**: Query logging for debugging
- **Upload Status**: Real-time upload status tracking
- **Error Reporting**: Detailed error messages and stack traces

---

**Last Updated**: September 24, 2025  
**Version**: 1.0.0  
**Author**: Development Team
