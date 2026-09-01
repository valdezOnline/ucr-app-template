# User Management System with HR Integration

This Laravel application provides a comprehensive user management interface that integrates with the HR API system using the `getHrPersonInfo` service.

## Features

### User Management
- **Create Users**: Add new users manually with all required information
- **View Users**: Display detailed user information including roles and status
- **Edit Users**: Update user information and roles
- **Delete Users**: Remove users from the system (with safety checks)
- **Search & Filter**: Find users by name, Net ID, email, department, status, or role

### HR API Integration
- **Import from HR**: Import user information directly from HR API using Net ID
- **Sync with HR**: Update existing user information with current HR data
- **HR Comparison**: View differences between stored data and current HR information
- **Automatic Population**: Auto-populate user fields from HR API data

### Role Management
- **Administrator**: Full system access
- **Manager**: Management privileges
- **Supervisor**: Supervisory access
- **Custom Roles**: Extensible role system

### User Status Management
- **Active/Inactive Status**: Control user access to the system
- **Status Toggle**: Easy activation/deactivation of users
- **Bulk Operations**: Filter and manage users by status

## API Integration

The system uses the `ApiClientService` class to communicate with the HR API:

```php
// Get HR person information
$hrInfo = $apiClient->getHrPersonInfo($netId);

// HR data structure returned:
[
    'net_id' => 'user123',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'department' => 'University Library',
    'title' => 'Library Assistant',
    'supervisor_net_id' => 'supervisor123',
    'supervisor_name' => 'Jane Smith',
    'email' => 'john.doe@ucr.edu'
]
```

## Database Structure

The `users` table includes:
- Basic Information: `net_id`, `first_name`, `last_name`, `email`
- HR Information: `department`, `title`, `supervisor_net_id`, `supervisor_name`
- Role Management: `is_admin`, `is_manager`, `is_supervisor`
- Status: `is_active`
- Authentication: `password`, `email_verified_at`, `remember_token`

## User Interface Components

### Dashboard
- User statistics overview
- Quick action buttons
- User profile summary
- Direct links to management functions

### User List
- Paginated user display
- Advanced search and filtering
- Bulk actions
- Status indicators
- Role badges

### User Details
- Complete user information
- HR data comparison
- Action buttons (edit, sync, toggle status)
- Audit information (created/updated dates)

### User Forms
- Create new users
- Edit existing users
- Import from HR modal
- Role and permission management
- Form validation and error handling

## Routes

```php
// User management routes
Route::resource('users', UserController::class);
Route::patch('users/{user}/sync-with-hr', [UserController::class, 'syncWithHr']);
Route::post('users/import-from-hr', [UserController::class, 'importFromHr']);
Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
```

## Usage Examples

### Creating a User
1. Navigate to "Manage Users" → "Add New User"
2. Fill in required information
3. Set roles and permissions
4. Save the user

### Importing from HR
1. Click "Import from HR" button
2. Enter the user's Net ID
3. System fetches HR data automatically
4. User is created with HR information

### Syncing with HR
1. View any user's profile
2. Click "Sync with HR" button
3. System updates user data with current HR information
4. Shows differences between old and new data

### Bulk Management
1. Use filters to find specific user groups
2. Apply status changes or role updates
3. Use search to find users quickly

## Security Features

- **Authentication Required**: All user management requires authentication
- **Self-Protection**: Users cannot delete or deactivate their own accounts
- **Role-Based Access**: Different permission levels for different roles
- **Input Validation**: Comprehensive form validation
- **CSRF Protection**: All forms protected against CSRF attacks

## Error Handling

- **API Failures**: Graceful handling of HR API connection issues
- **Validation Errors**: Clear error messages for form validation
- **User Feedback**: Success and error messages for all actions
- **Logging**: Comprehensive logging of all user management actions

## Testing Data

The system includes a seeder with sample users:
- `admin` - Administrator user
- `manager01` - Manager with supervisor privileges
- `staff01` - Regular staff user
- `supervisor01` - Supervisor user
- `inactive01` - Inactive user for testing

All test users have the password: `password123`

## Customization

The system is designed to be easily customizable:
- **Role System**: Add new roles by extending the User model
- **HR Fields**: Add additional HR fields to the migration and model
- **UI Components**: Customize views and styling
- **API Integration**: Modify the ApiClientService for different HR systems

## Application Management

The system includes a flexible application management interface that supports different URL types for various deployment scenarios.

### URL Types

1. **External Website**
   - Complete external URLs (e.g., `https://scholar.google.com`)
   - Direct links to external applications and services
   - Full URL validation and external link handling

2. **Sub-Application**
   - Applications within the same domain structure
   - Configured with base domain + sub-path
   - Example: `https://myapp.university.edu/admin/library`
   - Ideal for modular applications within the same ecosystem

3. **Folder View**
   - Directory-based views within the same domain
   - Simple path structures for organized content
   - Example: `https://myapp.university.edu/reports`
   - Perfect for categorized content and dashboards

### Application Features

- **Role-Based Access**: Assign specific roles required to access each application
- **Status Management**: Active/inactive status control for applications
- **Icon & Logo Support**: Visual branding with FontAwesome icons and custom logos
- **Usage Analytics**: Track role assignments and user access counts
- **Search & Filter**: Find applications by name, description, URL, or status

### Creating Applications

When creating a new application:

1. **Select URL Type**: Choose between External, Sub-Application, or Folder
2. **Configure URL Structure**:
   - **External**: Provide complete URL
   - **Sub-Application/Folder**: Set base domain and sub-path
3. **Set Role Requirements**: Define which roles can access the application
4. **Add Visual Elements**: Set icons and upload logos for better UX

### URL Structure Examples

```php
// External Application
[
    'url_type' => 'external',
    'url' => 'https://scholar.google.com'
]

// Sub-Application
[
    'url_type' => 'sub-application',
    'base_domain' => 'https://myapp.university.edu',
    'sub_path' => 'admin/library'
    // Results in: https://myapp.university.edu/admin/library
]

// Folder View
[
    'url_type' => 'folder',
    'base_domain' => 'https://myapp.university.edu',
    'sub_path' => 'reports'
    // Results in: https://myapp.university.edu/reports
]
```

## Performance Considerations

- **Pagination**: User lists are paginated to handle large datasets
- **Caching**: API tokens are cached to reduce API calls
- **Indexing**: Database indexes on frequently searched fields
- **Lazy Loading**: Efficient database queries with appropriate relationships
- **URL Generation**: Optimized URL generation for different application types