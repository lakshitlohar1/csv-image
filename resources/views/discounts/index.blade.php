@extends('layouts.app')

@section('title', 'Task B - User Discounts Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h4 class="mb-0 text-dark">
                        <i class="fas fa-percentage me-2"></i>
                        Task B - User Discounts Management
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-4" id="discountTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="discounts-tab" data-bs-toggle="tab" data-bs-target="#discounts" type="button" role="tab">
                                <i class="fas fa-tags me-1"></i> All Discounts
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="user-discounts-tab" data-bs-toggle="tab" data-bs-target="#user-discounts" type="button" role="tab">
                                <i class="fas fa-user-tag me-1"></i> My Discounts
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="create-discount-tab" data-bs-toggle="tab" data-bs-target="#create-discount" type="button" role="tab">
                                <i class="fas fa-plus-circle me-1"></i> Create Discount
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assign-discount-tab" data-bs-toggle="tab" data-bs-target="#assign-discount" type="button" role="tab">
                                <i class="fas fa-user-plus me-1"></i> Assign Discount
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="discountTabsContent">
                        <!-- All Discounts Tab -->
                        <div class="tab-pane fade show active" id="discounts" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                    <th>Usage</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($discounts as $discount)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $discount->code }}</span>
                                                    </td>
                                                    <td>{{ $discount->name }}</td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ ucfirst($discount->type) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($discount->type === 'percentage')
                                                            {{ $discount->value }}%
                                                        @else
                                                            ${{ number_format($discount->value, 2) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($discount->isActive())
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $discount->usage_count }}
                                                        @if($discount->usage_limit)
                                                            / {{ $discount->usage_limit }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary assign-discount-btn" 
                                                                data-discount-id="{{ $discount->id }}"
                                                                data-discount-code="{{ $discount->code }}">
                                                            <i class="fas fa-user-plus"></i> Assign
                                                        </button>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                        No discounts found
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    {{ $discounts->links() }}
                                </div>
                            </div>
                        </div>

                        <!-- User Discounts Tab -->
                        <div class="tab-pane fade" id="user-discounts" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Discount Code</th>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Value</th>
                                                    <th>Usage</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($userDiscounts as $userDiscount)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $userDiscount->discount->code }}</span>
                                                    </td>
                                                    <td>{{ $userDiscount->discount->name }}</td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ ucfirst($userDiscount->discount->type) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($userDiscount->discount->type === 'percentage')
                                                            {{ $userDiscount->discount->value }}%
                                                        @else
                                                            ${{ number_format($userDiscount->discount->value, 2) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $userDiscount->usage_count }}
                                                        @if($userDiscount->max_usage)
                                                            / {{ $userDiscount->max_usage }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($userDiscount->canUse())
                                                            <span class="badge bg-success">Available</span>
                                                        @else
                                                            <span class="badge bg-warning">Unavailable</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-success test-discount-btn" 
                                                                data-user-discount-id="{{ $userDiscount->id }}"
                                                                data-discount-code="{{ $userDiscount->discount->code }}">
                                                            <i class="fas fa-check"></i> Test
                                                        </button>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        <i class="fas fa-user-slash fa-2x mb-2"></i><br>
                                                        No discounts assigned to you
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    {{ $userDiscounts->links() }}
                                </div>
                            </div>
                        </div>

                        <!-- Create Discount Tab -->
                        <div class="tab-pane fade" id="create-discount" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Create New Discount</h5>
                                        </div>
                                        <div class="card-body">
                                            <form id="create-discount-form">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="name" class="form-label">Discount Name *</label>
                                                        <input type="text" class="form-control" id="name" name="name" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="code" class="form-label">Discount Code *</label>
                                                        <input type="text" class="form-control" id="code" name="code" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="type" class="form-label">Discount Type *</label>
                                                        <select class="form-select" id="type" name="type" required>
                                                            <option value="">Select Type</option>
                                                            <option value="percentage">Percentage</option>
                                                            <option value="fixed">Fixed Amount</option>
                                                            <option value="buy_x_get_y">Buy X Get Y</option>
                                                        </select>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="value" class="form-label">Value *</label>
                                                        <input type="number" class="form-control" id="value" name="value" step="0.01" min="0" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="min_order_amount" class="form-label">Minimum Order Amount</label>
                                                        <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" step="0.01" min="0">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="max_discount_amount" class="form-label">Maximum Discount Amount</label>
                                                        <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" step="0.01" min="0">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="usage_limit" class="form-label">Usage Limit</label>
                                                        <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="per_user_limit" class="form-label">Per User Limit</label>
                                                        <input type="number" class="form-control" id="per_user_limit" name="per_user_limit" min="1">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="starts_at" class="form-label">Start Date *</label>
                                                        <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="expires_at" class="form-label">Expiry Date</label>
                                                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                                        <label class="form-check-label" for="is_active">
                                                            Active
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-1"></i> Create Discount
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assign Discount Tab -->
                        <div class="tab-pane fade" id="assign-discount" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Assign Discount to User</h5>
                                        </div>
                                        <div class="card-body">
                                            <form id="assign-discount-form">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="assign_user_id" class="form-label">User *</label>
                                                        <select class="form-select" id="assign_user_id" name="user_id" required>
                                                            <option value="">Select User</option>
                                                            @foreach(\App\Models\User::all() as $user)
                                                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="assign_discount_id" class="form-label">Discount *</label>
                                                        <select class="form-select" id="assign_discount_id" name="discount_id" required>
                                                            <option value="">Select Discount</option>
                                                            @foreach(\CsvImage\UserDiscounts\Models\Discount::where('is_active', true)->get() as $discount)
                                                                <option value="{{ $discount->id }}">{{ $discount->code }} - {{ $discount->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="max_usage" class="form-label">Max Usage</label>
                                                        <input type="number" class="form-control" id="max_usage" name="max_usage" min="1">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="assign_expires_at" class="form-label">Expiry Date</label>
                                                        <input type="datetime-local" class="form-control" id="assign_expires_at" name="expires_at">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-user-plus me-1"></i> Assign Discount
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Discount Modal -->
<div class="modal fade" id="testDiscountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Discount Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="test-discount-form">
                    @csrf
                    <div class="mb-3">
                        <label for="test_original_amount" class="form-label">Original Amount *</label>
                        <input type="number" class="form-control" id="test_original_amount" name="original_amount" step="0.01" min="0" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="test_order_reference" class="form-label">Order Reference</label>
                        <input type="text" class="form-control" id="test_order_reference" name="order_reference">
                        <div class="invalid-feedback"></div>
                    </div>
                    <input type="hidden" id="test_user_discount_id" name="user_discount_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="apply-test-discount">Apply Discount</button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alert-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999;"></div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Create Discount Form
    $('#create-discount-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Creating...');
        
        $.ajax({
            url: '{{ route("discounts.store") }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    form[0].reset();
                    // Refresh the page to show new discount
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                    if (response.errors) {
                        displayValidationErrors(form, response.errors);
                    }
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                showAlert('danger', 'An error occurred while creating the discount');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Assign Discount Form
    $('#assign-discount-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Assigning...');
        
        $.ajax({
            url: '{{ route("discounts.assign") }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    form[0].reset();
                    // Refresh the page to show new assignment
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                    if (response.errors) {
                        displayValidationErrors(form, response.errors);
                    }
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                showAlert('danger', 'An error occurred while assigning the discount');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Test Discount Button
    $('.test-discount-btn').on('click', function() {
        const userDiscountId = $(this).data('user-discount-id');
        const discountCode = $(this).data('discount-code');
        
        $('#test_user_discount_id').val(userDiscountId);
        $('#testDiscountModal .modal-title').text(`Test Discount: ${discountCode}`);
        $('#testDiscountModal').modal('show');
    });

    // Apply Test Discount
    $('#apply-test-discount').on('click', function() {
        const form = $('#test-discount-form');
        const submitBtn = $(this);
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...');
        
        const formData = form.serialize();
        const userDiscountId = $('#test_user_discount_id').val();
        
        // Get user discount details first
        $.ajax({
            url: '{{ route("discounts.user-discounts") }}',
            method: 'GET',
            data: { user_id: '{{ auth()->id() }}' },
            success: function(response) {
                if (response.success) {
                    const userDiscount = response.discounts.find(ud => ud.id == userDiscountId);
                    if (userDiscount) {
                        // Apply the discount
                        $.ajax({
                            url: '{{ route("discounts.apply") }}',
                            method: 'POST',
                            data: {
                                user_id: '{{ auth()->id() }}',
                                discount_id: userDiscount.discount_id,
                                original_amount: $('#test_original_amount').val(),
                                order_reference: $('#test_order_reference').val(),
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(applyResponse) {
                                if (applyResponse.success) {
                                    const result = applyResponse.result;
                                    showAlert('success', `Discount applied successfully! Original: $${result.original_amount}, Discount: $${result.discount_amount}, Final: $${result.final_amount}`);
                                    $('#testDiscountModal').modal('hide');
                                    form[0].reset();
                                } else {
                                    showAlert('danger', applyResponse.message);
                                }
                            },
                            error: function(xhr) {
                                console.error('Error:', xhr);
                                showAlert('danger', 'An error occurred while applying the discount');
                            }
                        });
                    } else {
                        showAlert('danger', 'User discount not found');
                    }
                } else {
                    showAlert('danger', 'Failed to get user discounts');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                showAlert('danger', 'An error occurred while getting user discounts');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Assign Discount Button from table
    $('.assign-discount-btn').on('click', function() {
        const discountId = $(this).data('discount-id');
        const discountCode = $(this).data('discount-code');
        
        $('#assign_discount_id').val(discountId);
        $('#assign-discount-tab').tab('show');
        
        showAlert('info', `Selected discount: ${discountCode}`);
    });

    // Helper Functions
    function showAlert(type, message) {
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('#alert-container').append(alertHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $(`#${alertId}`).alert('close');
        }, 5000);
    }

    function displayValidationErrors(form, errors) {
        // Clear previous errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        
        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = form.find(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(errors[field][0]);
        });
    }

    // Set default start date to today
    const today = new Date().toISOString().slice(0, 16);
    $('#starts_at').val(today);
});
</script>
@endsection
