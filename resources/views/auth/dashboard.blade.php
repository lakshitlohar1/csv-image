<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - CSV Image Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .user-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        .btn-logout {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        .navbar-custom {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-images me-2"></i>CSV Image Manager
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i>{{ $user->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Welcome Section -->
        <div class="user-info">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-hand-wave me-2"></i>Welcome back, {{ $user->name }}!
                    </h2>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-envelope me-2"></i>{{ $user->email }}
                    </p>
                    <small class="opacity-75">
                        <i class="fas fa-clock me-1"></i>Last login: {{ $user->updated_at->format('M d, Y \a\t g:i A') }}
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <i class="fas fa-user-circle fa-5x opacity-75"></i>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-images fa-2x text-primary mb-3"></i>
                    <h4 class="fw-bold">0</h4>
                    <p class="text-muted mb-0">Total Images</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-file-csv fa-2x text-success mb-3"></i>
                    <h4 class="fw-bold">0</h4>
                    <p class="text-muted mb-0">CSV Files</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-upload fa-2x text-warning mb-3"></i>
                    <h4 class="fw-bold">0</h4>
                    <p class="text-muted mb-0">Uploads Today</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-database fa-2x text-info mb-3"></i>
                    <h4 class="fw-bold">0 MB</h4>
                    <p class="text-muted mb-0">Storage Used</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-lg-8">
                <div class="dashboard-card p-4">
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
                    </h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Upload Files</h5>
                                    <p class="card-text text-muted">Upload images and CSV files</p>
                                    <a href="{{ route('upload.index') }}" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Start Upload
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                                    <h5 class="card-title">Upload Dashboard</h5>
                                    <p class="card-text text-muted">View upload statistics and manage files</p>
                                    <a href="{{ route('upload.dashboard') }}" class="btn btn-info">
                                        <i class="fas fa-chart-line me-2"></i>View Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-csv fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">CSV Import</h5>
                                    <p class="card-text text-muted">Import products from CSV files</p>
                                    <a href="{{ route('upload.index') }}" class="btn btn-success">
                                        <i class="fas fa-file-import me-2"></i>Import CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-percentage fa-3x text-warning mb-3"></i>
                                    <h5 class="card-title">Task B - User Discounts</h5>
                                    <p class="card-text text-muted">Manage user-level discounts with deterministic stacking</p>
                                    <a href="{{ route('discounts.index') }}" class="btn btn-warning">
                                        <i class="fas fa-tags me-2"></i>Manage Discounts
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="dashboard-card p-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-info-circle me-2"></i>Quick Actions
                    </h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" disabled>
                            <i class="fas fa-upload me-2"></i>Upload New CSV
                        </button>
                        <button class="btn btn-outline-success" disabled>
                            <i class="fas fa-download me-2"></i>Download Report
                        </button>
                        <button class="btn btn-outline-info" disabled>
                            <i class="fas fa-cog me-2"></i>Settings
                        </button>
                        <hr>
                        <form id="logoutFormBottom" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-logout w-100">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>

                <div class="dashboard-card p-4 mt-3">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-shield-alt me-2"></i>Security Info
                    </h5>
                    <div class="small text-muted">
                        <p><i class="fas fa-check-circle text-success me-2"></i>Session Active</p>
                        <p><i class="fas fa-lock text-primary me-2"></i>Secure Connection</p>
                        <p><i class="fas fa-clock text-info me-2"></i>Last Activity: {{ now()->format('g:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Logout confirmation
            $('#logoutForm, #logoutFormBottom').submit(function(e) {
                e.preventDefault();
                
                if (confirm('Are you sure you want to logout? This will end your current session.')) {
                    this.submit();
                }
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);

            // Update last activity time every minute
            setInterval(function() {
                $('.text-info').last().html('<i class="fas fa-clock text-info me-2"></i>Last Activity: ' + new Date().toLocaleTimeString());
            }, 60000);
        });
    </script>
</body>
</html>
