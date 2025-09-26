# CSV Image Manager

A Laravel 12 application for managing CSV files and images with a complete authentication system.

## Features

### 🔐 Complete Authentication System
- **Secure Login/Logout** with proper session management
- **Remember Me** functionality with secure token generation
- **Session Security** with CSRF protection and session regeneration
- **Form Validation** with client-side and server-side validation
- **Beautiful UI** with Bootstrap 5 and modern design

### 📤 Bulk Upload System
- **Chunked File Upload** with resume capability and checksum validation
- **CSV Import System** with upsert logic for 10,000+ records
- **Image Processing** with automatic variant generation (256px, 512px, 1024px)
- **Drag & Drop Interface** with real-time progress tracking
- **Data Validation** with comprehensive error reporting and result summaries

### 🎨 Modern User Interface
- Responsive design with Bootstrap 5
- jQuery-powered interactive elements
- Gradient backgrounds and glassmorphism effects
- Font Awesome icons for enhanced UX
- Real-time form validation

### 🏷️ Task B - User Discounts Management
- **User-Level Discounts** with deterministic stacking
- **Comprehensive Audit Trail** for all discount activities
- **Usage Caps** with per-user and global limits
- **Concurrency Safety** for thread-safe operations
- **Event-Driven Architecture** with Laravel events
- **Complete Test Suite** with unit and feature tests
- **Bootstrap 5 Interface** with jQuery AJAX functionality

### 🛡️ Security Features
- CSRF token protection
- Session invalidation on logout
- Remember token management
- Password hashing with Laravel's built-in hashing
- Secure session handling

## Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Laravel 12
- MySQL/SQLite database

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd csv-image
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start the development server**
   ```bash
   php artisan serve
   ```

## Authentication System

### Login Credentials
The system comes with pre-seeded test users:

**Test User:**
- Email: `test@example.com`
- Password: `password123`

**Admin User:**
- Email: `admin@example.com`
- Password: `admin123`

### Authentication Features

#### Login Process
- Email and password validation
- "Remember Me" functionality
- Session regeneration for security
- Redirect to intended page after login
- Error handling with user-friendly messages

#### Logout Process
- Complete session destruction
- Remember token clearing
- CSRF token regeneration
- Secure redirect to login page

#### Session Management
- Automatic session regeneration on login
- Remember token generation and validation
- Session timeout handling
- Secure session storage

## Project Structure

```
app/
├── Http/Controllers/
│   └── AuthController.php          # Authentication logic
├── Models/
│   └── User.php                    # User model with remember token
database/
├── migrations/
│   └── *_add_remember_token_to_users_table.php
├── seeders/
│   └── UserSeeder.php              # Test user creation
resources/views/auth/
├── login.blade.php                 # Login form with validation
└── dashboard.blade.php             # User dashboard
routes/
└── web.php                         # Authentication routes
```

## Routes

### Public Routes
- `GET /` - Redirects to login
- `GET /login` - Login form
- `POST /login` - Process login

### Protected Routes (Require Authentication)
- `GET /dashboard` - User dashboard
- `POST /logout` - Process logout

### Task B - Discount Routes
- `GET /discounts` - View discount management interface
- `POST /discounts` - Create new discount
- `POST /discounts/assign` - Assign discount to user
- `POST /discounts/revoke` - Revoke discount from user
- `POST /discounts/eligible` - Check user eligibility
- `POST /discounts/apply` - Apply discount to amount
- `GET /discounts/user-discounts` - Get user's discounts

## Security Implementation

### Session Security
```php
// Session regeneration on login
$request->session()->regenerate();

// Complete session invalidation on logout
$request->session()->invalidate();
$request->session()->regenerateToken();
```

### Remember Token Management
```php
// Generate remember token
$user->setRememberToken(Str::random(60));

// Clear remember token on logout
$user->setRememberToken(null);
```

### CSRF Protection
All forms include CSRF tokens:
```php
@csrf
```

## Frontend Features

### Bootstrap 5 Integration
- Modern responsive design
- Glassmorphism effects
- Gradient backgrounds
- Custom styling for enhanced UX

### jQuery Functionality
- Real-time form validation
- Password visibility toggle
- Auto-hiding alerts
- Interactive elements

### Form Validation
- Client-side validation with jQuery
- Server-side validation with Laravel
- Real-time error display
- User-friendly error messages

## Development

### Adding New Features
1. Create controllers in `app/Http/Controllers/`
2. Add routes in `routes/web.php`
3. Create views in `resources/views/`
4. Update this README with new features

### Database Changes
1. Create migrations: `php artisan make:migration`
2. Update models if needed
3. Run migrations: `php artisan migrate`

## Testing

### Manual Testing
1. Visit `/login` to test login form
2. Use test credentials to login
3. Test "Remember Me" functionality
4. Test logout process
5. Verify session security
6. Test Task B discount management at `/discounts`

### Test Users
- Regular user: `test@example.com` / `password123`
- Admin user: `admin@example.com` / `admin123`

### Automated Testing
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test tests/Unit/DiscountServiceTest.php
php artisan test tests/Feature/DiscountWorkflowTest.php
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please create an issue in the repository or contact the development team.

---

**Built with Laravel 12, Bootstrap 5, and jQuery**
