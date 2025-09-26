<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-failed { background-color: #dc3545; color: #fff; }
        .status-uploading { background-color: #ffc107; color: #000; }
        .status-pending { background-color: #6c757d; color: #fff; }
        .image-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .image-preview:hover {
            transform: scale(1.1);
        }
        .variant-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin: 2px;
        }
        .modal-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .size-button {
            margin: 5px;
            min-width: 80px;
        }
        .size-button.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .image-container {
            text-align: center;
            padding: 20px;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Upload Dashboard</h2>
                    <div>
                        <a href="{{ route('upload.index') }}" class="btn btn-primary">Upload Files</a>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Uploads</h5>
                                <h3 id="totalUploads">{{ $totalUploads ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completed</h5>
                                <h3 id="completedUploads">{{ $completedUploads ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Failed</h5>
                                <h3 id="failedUploads">{{ $failedUploads ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Images</h5>
                                <h3 id="totalImages">{{ $totalImages ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- DataTable -->
                <div class="card">
                    <div class="card-body">
                        <table id="uploadsTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="d-none">Preview</th>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uploads as $upload)
                                    <tr>
                                        <td class="d-none">
                                            @if($upload->images->count() > 0)
                                                <img src="{{ Storage::disk('public')->url($upload->images->first()->file_path) }}" 
                                                     class="image-preview" 
                                                     alt="Preview"
                                                     onclick="openImageModal({{ $upload->id }})">
                                            @else
                                                <div class="image-preview bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $upload->original_filename }}</strong>
                                            <br><small class="text-muted">ID: {{ $upload->upload_id }}</small>
                                        </td>
                                        <td>{{ number_format($upload->file_size / 1024, 2) }} KB</td>
                                        <td>{{ $upload->mime_type }}</td>
                                        <td>
                                            <span class="status-badge status-{{ $upload->status }}">
                                                {{ ucfirst($upload->status) }}
                                            </span>
                                            @if($upload->error_message)
                                                <br><small class="text-danger">{{ Str::limit($upload->error_message, 30) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $upload->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary btn-sm" onclick="viewDetails({{ $upload->id }})" title="View Details">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                                @if($upload->status === 'completed')
                                                    <button class="btn btn-outline-success btn-sm" onclick="downloadFile('{{ $upload->file_path }}')" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                @endif
                                                @if($upload->images->count() > 0)
                                                    <button class="btn btn-outline-info btn-sm" onclick="openImageModal({{ $upload->id }})" title="View Image">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Details -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Image View -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Image Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="image-container">
                                <img id="modalImage" src="" alt="Image" class="modal-image">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>Image Sizes</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary size-button" onclick="changeImageSize('original')">
                                    <i class="fas fa-expand"></i> Original
                                </button>
                                <button class="btn btn-outline-success size-button" onclick="changeImageSize('large')">
                                    <i class="fas fa-image"></i> Large (1024px)
                                </button>
                                <button class="btn btn-outline-warning size-button" onclick="changeImageSize('medium')">
                                    <i class="fas fa-image"></i> Medium (512px)
                                </button>
                                <button class="btn btn-outline-info size-button" onclick="changeImageSize('thumbnail')">
                                    <i class="fas fa-image"></i> Thumbnail (256px)
                                </button>
                            </div>
                            <hr>
                            <div id="imageInfo">
                                <h6>Image Information</h6>
                                <p><strong>Filename:</strong> <span id="imageFilename"></span></p>
                                <p><strong>Dimensions:</strong> <span id="imageDimensions"></span></p>
                                <p><strong>Size:</strong> <span id="imageSize"></span></p>
                                <p><strong>Type:</strong> <span id="imageType"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadCurrentImage()">Download</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Check if all required libraries are loaded
        console.log('=== LIBRARY CHECK ===');
        console.log('jQuery loaded:', typeof $ !== 'undefined');
        console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
        console.log('DataTables loaded:', typeof $.fn.DataTable !== 'undefined');
        
        let currentImageData = {};
        let currentImageSize = 'original';
        
        // Initialize DataTable
        $(document).ready(function() {
            try {
                // Check if jQuery is loaded
                if (typeof $ === 'undefined') {
                    console.error('jQuery is not loaded!');
                    return;
                }
                
                console.log('Initializing DataTable...');
                console.log('jQuery version:', $.fn.jquery);
                console.log('DataTable available:', typeof $.fn.DataTable);
                
                $('#uploadsTable').DataTable({
                    "pageLength": 25,
                    "order": [[ 5, "desc" ]], // Sort by upload date descending
                    "columnDefs": [
                        { "orderable": false, "targets": [0, 6] }, // Disable sorting on preview and actions columns
                        { "searchable": false, "targets": [0, 6] } // Disable search on preview and actions columns
                    ],
                    "language": {
                        "search": "Search uploads:",
                        "lengthMenu": "Show _MENU_ uploads per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ uploads",
                        "infoEmpty": "No uploads found",
                        "infoFiltered": "(filtered from _MAX_ total uploads)"
                    }
                });
                
                console.log('DataTable initialized successfully!');
            } catch (error) {
                console.error('DataTable initialization failed:', error);
                // Fallback: just show the table without DataTables features
                console.log('Falling back to basic table display');
            }
        });
        
        function viewDetails(uploadId) {
            // Load upload details via AJAX
            fetch(`/api/upload/details/${uploadId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Upload Information</h6>
                                <p><strong>ID:</strong> ${data.id}</p>
                                <p><strong>Upload ID:</strong> ${data.upload_id}</p>
                                <p><strong>Filename:</strong> ${data.original_filename}</p>
                                <p><strong>Size:</strong> ${data.file_size} bytes</p>
                                <p><strong>Type:</strong> ${data.mime_type}</p>
                                <p><strong>Status:</strong> ${data.status}</p>
                                <p><strong>Created:</strong> ${data.created_at}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>File Information</h6>
                                <p><strong>Path:</strong> ${data.file_path}</p>
                                <p><strong>Checksum:</strong> ${data.checksum}</p>
                                <p><strong>Chunks:</strong> ${data.uploaded_chunks}/${data.total_chunks}</p>
                                ${data.error_message ? `<p><strong>Error:</strong> ${data.error_message}</p>` : ''}
                            </div>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('detailsModal')).show();
                })
                .catch(error => {
                    console.error('Error loading details:', error);
                    alert('Error loading upload details');
                });
        }
        
        function downloadFile(filePath) {
            window.open(`/storage/${filePath}`, '_blank');
        }
        
        // Image Modal Functions
        function openImageModal(uploadId) {
            // Load image data via AJAX
            fetch(`/api/upload/details/${uploadId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.images && data.images.length > 0) {
                        currentImageData = data.images[0];
                        currentImageSize = 'original';
                        
                        // Set original image
                        const originalPath = data.images[0].file_path;
                        const imageUrl = `/storage/${originalPath}`;
                        console.log('Loading image from:', imageUrl);
                        document.getElementById('modalImage').src = imageUrl;
                        
                        // Add error handling for image loading
                        document.getElementById('modalImage').onerror = function() {
                            console.error('Failed to load image:', imageUrl);
                            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4=';
                        };
                        
                        // Update image info
                        document.getElementById('imageFilename').textContent = data.images[0].filename;
                        document.getElementById('imageDimensions').textContent = `${data.images[0].width}x${data.images[0].height}px`;
                        document.getElementById('imageSize').textContent = `${Math.round(data.images[0].file_size / 1024)} KB`;
                        document.getElementById('imageType').textContent = data.mime_type;
                        
                        // Show modal
                        new bootstrap.Modal(document.getElementById('imageModal')).show();
                        
                        // Set original button as active
                        setTimeout(() => {
                            document.querySelector('.size-button[onclick*="original"]').classList.add('active');
                        }, 100);
                    } else {
                        alert('No image found for this upload');
                    }
                })
                .catch(error => {
                    console.error('Error loading image data:', error);
                    alert('Error loading image data');
                });
        }
        
        function changeImageSize(size) {
            if (!currentImageData || !currentImageData.variants) return;
            
            currentImageSize = size;
            let imagePath = '';
            let dimensions = '';
            
            if (size === 'original') {
                imagePath = currentImageData.file_path;
                dimensions = `${currentImageData.width}x${currentImageData.height}px`;
            } else {
                // Find the variant with matching name
                const variant = currentImageData.variants.find(v => v.name === size);
                if (variant) {
                    imagePath = variant.file_path;
                    dimensions = `${variant.width}x${variant.height}px`;
                } else {
                    // Fallback to original if variant not found
                    imagePath = currentImageData.file_path;
                    dimensions = `${currentImageData.width}x${currentImageData.height}px`;
                }
            }
            
            // Update image
            const newImageUrl = `/storage/${imagePath}`;
            console.log('Current image data:', currentImageData);
            console.log('Image path:', imagePath);
            console.log('Dimensions:', dimensions);
            console.log('Changing image to:', newImageUrl);
            document.getElementById('modalImage').src = newImageUrl;
            document.getElementById('imageDimensions').textContent = dimensions;
            
            // Update button states
            document.querySelectorAll('.size-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
        
        function downloadCurrentImage() {
            if (currentImageData) {
                let imagePath = '';
                
                if (currentImageSize === 'original') {
                    imagePath = currentImageData.file_path;
                } else {
                    const variant = currentImageData.variants.find(v => v.name === currentImageSize);
                    if (variant) {
                        imagePath = variant.file_path;
                    } else {
                        imagePath = currentImageData.file_path;
                    }
                }
                
                window.open(`/storage/${imagePath}`, '_blank');
            }
        }
    </script>
</body>
</html>
