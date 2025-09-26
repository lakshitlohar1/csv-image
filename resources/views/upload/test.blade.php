<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simple Upload Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-uploading { background-color: #ffc107; color: #000; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-failed { background-color: #dc3545; color: #fff; }
        .upload-progress {
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        .upload-progress-bar {
            height: 100%;
            background-color: #007bff;
            transition: width 0.3s ease;
        }
        .log-output {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Simple Upload Test with Detailed Logging</h4>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="image" class="form-label">Select Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Image</button>
                        </form>
                        <div id="result" class="mt-3"></div>
                        <div id="logs" class="mt-3">
                            <h6>Console Logs:</h6>
                            <div id="logOutput" class="log-output"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Override console.log to display in the page
        const originalLog = console.log;
        const originalError = console.error;
        const logOutput = document.getElementById('logOutput');
        
        function addLog(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.style.color = type === 'error' ? '#dc3545' : '#000';
            logEntry.textContent = `[${timestamp}] ${message}`;
            logOutput.appendChild(logEntry);
            logOutput.scrollTop = logOutput.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addLog(args.join(' '));
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addLog(args.join(' '), 'error');
        };

        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            console.log('=== FORM SUBMISSION START ===');
            
            const formData = new FormData();
            const fileInput = document.getElementById('image');
            
            if (!fileInput.files[0]) {
                console.error('No file selected');
                alert('Please select a file');
                return;
            }
            
            const file = fileInput.files[0];
            console.log('File selected:', {
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: file.lastModified
            });
            
            formData.append('image', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            console.log('FormData created');
            console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').content);
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="alert alert-info">Uploading...</div>';
            
            try {
                console.log('Making fetch request to /api/simple-image-upload');
                console.log('Request details:', {
                    method: 'POST',
                    url: '/api/simple-image-upload',
                    body: formData
                });
                
                const response = await fetch('/api/simple-image-upload', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response received');
                console.log('Response status:', response.status);
                console.log('Response statusText:', response.statusText);
                
                const result = await response.json();
                console.log('Response parsed as JSON:', result);
                
                if (result.success) {
                    console.log('Upload successful!');
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>Upload Successful!</h6>
                            <p><strong>Path:</strong> ${result.path}</p>
                            <p><strong>Dimensions:</strong> ${result.width}x${result.height}</p>
                            <p><strong>File Size:</strong> ${result.file_size} bytes</p>
                            <p><strong>MIME Type:</strong> ${result.mime_type}</p>
                        </div>
                    `;
                } else {
                    console.error('Upload failed:', result.error);
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Upload Failed!</h6>
                            <p><strong>Error:</strong> ${result.error}</p>
                            <p><strong>Errors:</strong> ${JSON.stringify(result.errors || {})}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Upload error:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    name: error.name
                });
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>Upload Error!</h6>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        });
        
        console.log('Page loaded, ready for uploads');
    </script>
</body>
</html>
