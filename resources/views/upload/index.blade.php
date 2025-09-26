<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bulk Upload - CSV Image Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .upload-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .drop-zone {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            background: rgba(102, 126, 234, 0.05);
        }
        .drop-zone.dragover {
            border-color: #764ba2;
            background: rgba(118, 75, 162, 0.1);
            transform: scale(1.02);
        }
        .upload-progress {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
        }
        .upload-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        .upload-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-uploading { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
                <i class="fas fa-images me-2"></i>CSV Image Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link text-white">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8">
                <!-- Upload Interface -->
                <div class="upload-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Bulk Image Upload
                        </h3>
                        <div>
                            <a href="{{ route('upload.dashboard') }}" class="btn btn-outline-info btn-sm me-2">
                                <i class="fas fa-chart-bar me-1"></i>View Dashboard
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </div>
                    </div>
                    
                    <!-- Drop Zone -->
                    <div class="drop-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                        <h5>Drag & Drop Images Here</h5>
                        <p class="text-muted">or click to select files</p>
                        <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
                        <button class="btn btn-primary btn-upload" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-plus me-2"></i>Select Images
                        </button>
                    </div>

                    <!-- Upload Progress -->
                    <div id="uploadProgress" class="mt-4" style="display: none;">
                        <h6 class="mb-3">Upload Progress</h6>
                        <div class="upload-progress">
                            <div class="upload-progress-bar" id="progressBar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small id="progressText">0%</small>
                            <small id="fileCount">0 files</small>
                        </div>
                    </div>

                    <!-- Upload Queue -->
                    <div id="uploadQueue" class="mt-4"></div>
                </div>

                <!-- CSV Import -->
                <div class="upload-card p-4">
                    <h3 class="fw-bold mb-4">
                        <i class="fas fa-file-csv me-2"></i>CSV Import
                    </h3>
                    
                    <form id="csvImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv,.txt" required>
                            <div class="form-text">Maximum file size: 10MB</div>
                        </div>
                        <button type="submit" class="btn btn-success btn-upload">
                            <i class="fas fa-upload me-2"></i>Import CSV
                        </button>
                    </form>

                    <!-- CSV Import Results -->
                    <div id="csvResults" class="mt-4" style="display: none;"></div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Upload Statistics -->
                <div class="upload-card p-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Upload Statistics
                    </h5>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h4 text-primary mb-1" id="totalUploads">0</div>
                            <small class="text-muted">Total Uploads</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 text-success mb-1" id="completedUploads">0</div>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 text-warning mb-1" id="pendingUploads">0</div>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 text-danger mb-1" id="failedUploads">0</div>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Uploads -->
                <div class="upload-card p-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-history me-2"></i>Recent Uploads
                    </h5>
                    <div id="recentUploads">
                        <p class="text-muted text-center">No recent uploads</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        class ChunkedUploader {
            constructor() {
                this.chunkSize = 1024 * 1024; // 1MB chunks
                this.uploadQueue = [];
                this.activeUploads = new Map();
                this.setupEventListeners();
            }

            setupEventListeners() {
                const dropZone = document.getElementById('dropZone');
                const fileInput = document.getElementById('fileInput');

                // Drag and drop events
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('dragover');
                });

                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('dragover');
                });

                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('dragover');
                    this.handleFiles(e.dataTransfer.files);
                });

                // File input change
                fileInput.addEventListener('change', (e) => {
                    this.handleFiles(e.target.files);
                });

                // CSV form submission
                document.getElementById('csvImportForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.importCsv();
                });
            }

            handleFiles(files) {
                Array.from(files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        this.addToQueue(file);
                    }
                });
            }

            addToQueue(file) {
                const uploadId = this.generateUploadId();
                const totalChunks = Math.ceil(file.size / this.chunkSize);
                
                const uploadItem = {
                    id: uploadId,
                    file: file,
                    totalChunks: totalChunks,
                    uploadedChunks: 0,
                    status: 'pending'
                };

                this.uploadQueue.push(uploadItem);
                this.renderUploadItem(uploadItem);
                this.startUpload(uploadItem);
            }

            async startUpload(uploadItem) {
                try {
                    console.log('=== STARTING SIMPLE UPLOAD ===');
                    console.log('Upload ID:', uploadItem.id);
                    console.log('File name:', uploadItem.file.name);
                    console.log('File size:', uploadItem.file.size);
                    console.log('File type:', uploadItem.file.type);
                    
                    uploadItem.status = 'uploading';
                    this.updateUploadStatus(uploadItem.id, 'uploading');

                    // Use simple upload method
                    const formData = new FormData();
                    formData.append('image', uploadItem.file);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    
                    console.log('Step 1: Creating FormData');
                    console.log('Step 2: Making fetch request to /api/simple-image-upload');

                    const response = await fetch('/api/simple-image-upload', {
                        method: 'POST',
                        body: formData
                    });

                    console.log('Step 3: Response received');
                    console.log('Response status:', response.status);

                    const result = await response.json();
                    console.log('Step 4: Response parsed');
                    console.log('Response data:', result);

                    if (result.success) {
                        console.log('Upload successful!');
                        this.updateUploadStatus(uploadItem.id, 'completed');
                        this.updateProgress(uploadItem.id, 100);
                    } else {
                        console.log('Upload failed:', result.error);
                        this.updateUploadStatus(uploadItem.id, 'failed', result.error);
                    }

                } catch (error) {
                    console.error('Upload error:', error);
                    this.updateUploadStatus(uploadItem.id, 'failed', error.message);
                }
            }

            async initializeUpload(uploadItem) {
                const checksum = await this.calculateChecksum(uploadItem.file);
                
                return $.ajax({
                    url: '/api/upload/initialize',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        filename: uploadItem.file.name,
                        file_size: uploadItem.file.size,
                        mime_type: uploadItem.file.type,
                        checksum: checksum,
                        total_chunks: uploadItem.totalChunks
                    }
                });
            }

            async uploadChunk(uploadId, chunkNumber, chunk, checksum) {
                const formData = new FormData();
                formData.append('upload_id', uploadId);
                formData.append('chunk_number', chunkNumber);
                formData.append('chunk', chunk);
                formData.append('chunk_checksum', checksum);

                return $.ajax({
                    url: '/api/upload/chunk',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    processData: false,
                    contentType: false
                });
            }

            async importCsv() {
                const formData = new FormData();
                const csvFile = document.getElementById('csvFile').files[0];
                
                if (!csvFile) {
                    alert('Please select a CSV file');
                    return;
                }

                formData.append('csv_file', csvFile);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                try {
                    const response = await $.ajax({
                        url: '/api/upload/csv',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false
                    });

                    this.showCsvResults(response.results);
                } catch (error) {
                    alert('CSV import failed: ' + error.responseJSON?.error || error.message);
                }
            }

            createChunk(file, chunkNumber) {
                const start = (chunkNumber - 1) * this.chunkSize;
                const end = Math.min(start + this.chunkSize, file.size);
                return file.slice(start, end);
            }

            async calculateChecksum(file) {
                const buffer = await file.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('MD5', buffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            }

            generateUploadId() {
                return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            renderUploadItem(uploadItem) {
                const queue = document.getElementById('uploadQueue');
                const item = document.createElement('div');
                item.className = 'upload-item';
                item.id = `upload-${uploadItem.id}`;
                
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${uploadItem.file.name}</strong>
                            <br>
                            <small class="text-muted">${this.formatFileSize(uploadItem.file.size)}</small>
                        </div>
                        <div>
                            <span class="status-badge status-pending" id="status-${uploadItem.id}">Pending</span>
                        </div>
                    </div>
                    <div class="upload-progress mt-2" id="progress-${uploadItem.id}">
                        <div class="upload-progress-bar" style="width: 0%"></div>
                    </div>
                `;
                
                queue.appendChild(item);
            }

            updateUploadStatus(uploadId, status, error = null) {
                const statusElement = document.getElementById(`status-${uploadId}`);
                statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusElement.className = `status-badge status-${status}`;
                
                if (error) {
                    statusElement.title = error;
                }
            }

            updateProgress(uploadId, progress) {
                const progressBar = document.querySelector(`#progress-${uploadId} .upload-progress-bar`);
                progressBar.style.width = `${progress}%`;
            }

            showCsvResults(results) {
                const resultsDiv = document.getElementById('csvResults');
                resultsDiv.style.display = 'block';
                
                resultsDiv.innerHTML = `
                    <div class="alert alert-info">
                        <h6>Import Results</h6>
                        <div class="row">
                            <div class="col-6">
                                <strong>Total:</strong> ${results.total}
                            </div>
                            <div class="col-6">
                                <strong>Imported:</strong> ${results.imported}
                            </div>
                            <div class="col-6">
                                <strong>Updated:</strong> ${results.updated}
                            </div>
                            <div class="col-6">
                                <strong>Invalid:</strong> ${results.invalid}
                            </div>
                        </div>
                    </div>
                `;
            }

            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        }

        // Initialize uploader
        document.addEventListener('DOMContentLoaded', () => {
            new ChunkedUploader();
        });
    </script>
</body>
</html>
