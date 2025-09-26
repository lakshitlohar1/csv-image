# Task B - User Discounts Module

## Overview

The User Discounts module is a comprehensive Laravel package that provides user-level discount management with deterministic stacking, automated test suite, and full audit capabilities. This module is designed to be reusable and can be easily integrated into any Laravel application.

## Features

### 🎯 Core Functionality
- **User-Level Discounts**: Assign discounts to specific users with usage tracking
- **Deterministic Stacking**: Apply multiple discounts in a predictable order
- **Usage Caps**: Enforce per-user and global usage limits
- **Audit Trail**: Complete audit logging for all discount activities
- **Concurrency Safety**: Thread-safe discount application
- **Event System**: Fire events for discount assignment, revocation, and application

### 🏗️ Package Structure
- **PSR-4 Autoloading**: Properly namespaced package structure
- **Service Provider**: Automatic registration and configuration
- **Migrations**: Database schema for discounts, user_discounts, and discount_audits
- **Models**: Eloquent models with relationships and business logic
- **Services**: Business logic separation with dependency injection
- **Events**: Event-driven architecture for extensibility

### 🛡️ Security & Validation
- **Input Validation**: Comprehensive server-side validation
- **CSRF Protection**: Built-in CSRF token validation
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **XSS Protection**: Proper output escaping in views
- **Session Security**: Secure session handling

### 🎨 User Interface
- **Bootstrap 5**: Modern, responsive design
- **jQuery AJAX**: Smooth, asynchronous form submissions
- **Real-time Validation**: Client-side and server-side validation
- **Error Handling**: Comprehensive error reporting with try-catch blocks
- **Loading States**: Visual feedback during operations

## Installation & Setup

### 1. Package Registration
The package is automatically registered in `bootstrap/providers.php`:

```php
CsvImage\UserDiscounts\UserDiscountsServiceProvider::class,
```

### 2. Autoloading
The package namespace is registered in `composer.json`:

```json
"CsvImage\\UserDiscounts\\": "app/Packages/UserDiscounts/src/"
```

### 3. Database Migrations
Run the package migrations:

```bash
php artisan migrate
```

### 4. Configuration
The package includes a configuration file at `app/Packages/UserDiscounts/config/user-discounts.php` with options for:
- Stacking order configuration
- Maximum percentage and fixed amount caps
- Rounding methods
- Concurrency settings
- Audit retention

## Database Schema

### Discounts Table
```sql
- id (primary key)
- name (discount name)
- code (unique discount code)
- type (percentage, fixed, buy_x_get_y)
- value (discount value)
- min_order_amount (minimum order requirement)
- max_discount_amount (maximum discount cap)
- usage_limit (global usage limit)
- usage_count (current usage count)
- per_user_limit (per-user usage limit)
- starts_at (discount start date)
- expires_at (discount expiry date)
- is_active (active status)
- conditions (JSON conditions for complex discounts)
```

### User Discounts Table
```sql
- id (primary key)
- user_id (foreign key to users)
- discount_id (foreign key to discounts)
- usage_count (user's usage count)
- max_usage (user's usage limit)
- assigned_at (assignment timestamp)
- expires_at (user-specific expiry)
- is_active (active status)
```

### Discount Audits Table
```sql
- id (primary key)
- user_id (foreign key to users)
- discount_id (foreign key to discounts)
- user_discount_id (foreign key to user_discounts)
- action (assigned, revoked, applied, expired)
- original_amount (original order amount)
- discount_amount (discount applied)
- final_amount (final amount after discount)
- metadata (JSON metadata)
- order_reference (order reference)
```

## API Endpoints

### Discount Management
- `GET /discounts` - View all discounts
- `POST /discounts` - Create new discount
- `POST /discounts/assign` - Assign discount to user
- `POST /discounts/revoke` - Revoke discount from user
- `POST /discounts/eligible` - Check user eligibility
- `POST /discounts/apply` - Apply discount to amount
- `GET /discounts/user-discounts` - Get user's discounts

### Request/Response Format
All API endpoints return JSON responses with the following structure:

```json
{
    "success": true|false,
    "message": "Response message",
    "data": {}, // Response data (optional)
    "errors": {} // Validation errors (optional)
}
```

## Usage Examples

### Creating a Discount
```php
$discount = Discount::create([
    'name' => 'Welcome Discount',
    'code' => 'WELCOME10',
    'type' => 'percentage',
    'value' => 10,
    'starts_at' => now(),
    'expires_at' => now()->addDays(30),
    'is_active' => true,
    'per_user_limit' => 1,
]);
```

### Assigning a Discount
```php
$discountService = app(DiscountService::class);
$userDiscount = $discountService->assign($userId, $discountId);
```

### Applying a Discount
```php
$result = $discountService->apply($userId, $discountId, $originalAmount);
// Returns: ['original_amount' => 100, 'discount_amount' => 10, 'final_amount' => 90]
```

### Checking Eligibility
```php
$eligible = $discountService->eligibleFor($userId, $discountId);
```

## Business Rules

### Discount Types
1. **Percentage**: Discount as percentage of order amount
2. **Fixed**: Fixed amount discount
3. **Buy X Get Y**: Complex discount with conditions

### Stacking Rules
- Discounts are applied in configured order
- Percentage discounts applied first
- Fixed amount discounts applied second
- Complex discounts applied last

### Usage Limits
- Global usage limit per discount
- Per-user usage limit
- Minimum order amount requirements
- Maximum discount amount caps

### Expiry Handling
- Discount expiry dates are respected
- User-specific expiry dates override global expiry
- Expired discounts are automatically excluded

## Events

### DiscountAssigned
Fired when a discount is assigned to a user:
```php
event(new DiscountAssigned($discount, $userDiscount, $userId));
```

### DiscountRevoked
Fired when a discount is revoked from a user:
```php
event(new DiscountRevoked($discount, $userDiscount, $userId));
```

### DiscountApplied
Fired when a discount is applied to an order:
```php
event(new DiscountApplied($discount, $userDiscount, $userId, $originalAmount, $discountAmount, $finalAmount));
```

## Testing

### Unit Tests
- `DiscountServiceTest`: Tests core discount service functionality
- `DiscountCalculationServiceTest`: Tests discount calculation logic
- `DiscountModelTest`: Tests model relationships and business logic

### Feature Tests
- `DiscountWorkflowTest`: Tests complete discount workflow
- `DiscountApiTest`: Tests API endpoints
- `DiscountConcurrencyTest`: Tests concurrent access scenarios

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test class
php artisan test tests/Unit/DiscountServiceTest.php

# Run with coverage
php artisan test --coverage
```

## Configuration Options

### Stacking Configuration
```php
'stacking' => [
    'enabled' => true,
    'order' => [
        'percentage' => 1,
        'fixed' => 2,
        'buy_x_get_y' => 3,
    ],
],
```

### Caps Configuration
```php
'caps' => [
    'max_percentage' => 100,
    'max_fixed_amount' => 1000,
],
```

### Rounding Configuration
```php
'rounding' => [
    'method' => 'round', // round, floor, ceil
    'precision' => 2,
],
```

## Error Handling

### Validation Errors
- Server-side validation with detailed error messages
- Client-side validation with real-time feedback
- Proper HTTP status codes (422 for validation errors)

### Exception Handling
- Try-catch blocks in all service methods
- Comprehensive logging for debugging
- User-friendly error messages
- Graceful degradation

### Logging
- All discount operations are logged
- Error conditions are logged with context
- Audit trail for compliance
- Debug information for troubleshooting

## Security Considerations

### Data Protection
- Sensitive data is properly hashed
- SQL injection prevention through Eloquent ORM
- XSS protection through proper output escaping
- CSRF protection on all forms

### Access Control
- Authentication required for all operations
- User-specific discount access
- Admin-only discount creation
- Audit trail for compliance

### Concurrency
- Database locks prevent race conditions
- Atomic operations for consistency
- Retry mechanisms for transient failures
- Deadlock prevention

## Performance Considerations

### Database Optimization
- Proper indexing on frequently queried columns
- Efficient relationship loading
- Query optimization for large datasets
- Connection pooling for high concurrency

### Caching
- Configuration caching
- Route caching
- View caching
- Database query caching

### Monitoring
- Performance metrics collection
- Error rate monitoring
- Usage pattern analysis
- Resource utilization tracking

## Troubleshooting

### Common Issues

1. **Discount Not Applying**
   - Check if discount is active and not expired
   - Verify user has the discount assigned
   - Check usage limits
   - Verify minimum order amount

2. **Concurrency Issues**
   - Ensure proper database locking
   - Check for deadlocks
   - Verify transaction isolation

3. **Performance Issues**
   - Check database indexes
   - Monitor query performance
   - Review caching configuration
   - Analyze usage patterns

### Debug Tools
- Laravel Debugbar for development
- Query logging for database issues
- Event logging for business logic
- Performance profiling tools

## Future Enhancements

### Planned Features
- Bulk discount operations
- Advanced reporting and analytics
- Integration with external systems
- Machine learning for discount optimization
- Mobile API endpoints
- Real-time notifications

### Scalability Improvements
- Horizontal scaling support
- Microservices architecture
- Event sourcing implementation
- CQRS pattern adoption
- Message queue integration

## Support & Maintenance

### Documentation
- Comprehensive API documentation
- Code comments and docblocks
- User guides and tutorials
- Video demonstrations

### Maintenance
- Regular security updates
- Performance optimizations
- Bug fixes and patches
- Feature enhancements

### Community
- GitHub repository for issues
- Discussion forums
- Developer community
- Contribution guidelines

---

**Built with Laravel 12, Bootstrap 5, and jQuery**

This module provides a complete, production-ready solution for user discount management with enterprise-grade features, security, and performance considerations.
