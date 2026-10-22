# API Authentication Service Documentation

## Overview

This authentication service allows external applications to authenticate users against this Laravel application. External applications can verify user credentials, check user access to specific applications, and get user information through a secure API.

## Authentication Methods

The system supports both local Laravel authentication and CAS (Single Sign-On) authentication, depending on the configuration.

## Getting Started

### 1. Generate API Credentials

Before an external application can use the authentication API, you need to generate API credentials for it.

#### Using Web Interface (Recommended)

1. **During Application Creation:**
   - Navigate to Applications → Create New Application
   - Fill in the basic application details (name, description, URL, etc.)
   - In the "API Authentication Setup" section:
     - Check "Enable API Authentication"
     - Check "Generate API credentials immediately" (recommended)
     - Set an expiration date (optional but recommended)
     - Add allowed domains for additional security (optional)
   - Complete the form and create the application
   - The API credentials will be displayed once after creation - save them securely

2. **For Existing Applications:**
   - Navigate to Applications → Select Application → Edit
   - In the "API Authentication Setup" section:
     - Check "Enable API Authentication"  
     - Check "Generate API credentials now" if no credentials exist
     - Configure allowed domains and expiration as needed
   - Save the application
   - The API credentials will be displayed once after saving

3. **Managing Existing Credentials:**
   - In the edit view, you can:
     - View current API status and expiration
     - Update allowed domains
     - Enable/disable API access temporarily
     - Revoke credentials completely
     - Regenerate new credentials (invalidates old ones)

#### Using Artisan Command (Alternative)

```bash
# Interactive mode - select from existing applications
php artisan app:generate-api-credentials

# Generate for specific application by name
php artisan app:generate-api-credentials "My External App"

# Generate for specific application by ID
php artisan app:generate-api-credentials 1

# List all applications and their API status
php artisan app:generate-api-credentials --list

# Set allowed domains during generation
php artisan app:generate-api-credentials "My App" --domains=example.com,app.example.com

# Set expiration date
php artisan app:generate-api-credentials "My App" --expires=2025-12-31

# Revoke existing credentials
php artisan app:generate-api-credentials "My App" --revoke
```

#### Programmatically

```php
$application = Application::find(1);
$credentials = $application->generateApiCredentials();

// $credentials contains:
// [
//     'api_key' => 'app_...',
//     'api_secret' => 'plain_secret_here'
// ]
```

### 2. Application Setup Requirements

Your external application must:

1. Be registered in the applications table
2. Have `supports_api_auth` set to `true`
3. Have valid `api_key` and `api_secret` generated
4. Be marked as `is_active = true`
5. Be configured with appropriate user roles for access control

## API Endpoints

Base URL: `https://your-domain.com/api`

## External CAS Broker Flow (Authorization Code)

Use this flow when an external application wants browser-based CAS login through this app without handling user passwords directly.

### Flow Summary

1. External app redirects user to `GET /api/auth/authorize` with `api_key`, `redirect_uri`, and `state`.
2. This app validates client/callback configuration and completes CAS authentication.
3. On success, this app redirects user back to external `redirect_uri` with `code` and `state`.
4. External backend exchanges the code at `POST /api/auth/token` with `api_key` and `api_secret`.
5. This app returns a Sanctum bearer token plus user/application details.

### Requirements

1. Application must have API auth enabled (`supports_api_auth=true`, `api_active=true`).
2. External broker mode must be enabled on the application (`external_auth_enabled=true`).
3. Application must have a registered callback URL (`external_auth_callback_url`).
4. Redirect URI must exactly match the registered callback URL.

### Authorize Endpoint

**GET** `/api/auth/authorize`

**Query Parameters:**

- `api_key` (required)
- `redirect_uri` (required)
- `state` (required)

**Success Behavior:**

Browser is redirected to:

```text
{redirect_uri}?code={authorization_code}&state={state}
```

**Error Behavior:**

Browser is redirected to:

```text
{redirect_uri}?error={error}&error_description={message}&state={state}
```

### Token Exchange Endpoint

**POST** `/api/auth/token`

**Request:**

```json
{
    "grant_type": "authorization_code",
    "code": "one_time_authorization_code",
    "api_key": "app_your_api_key_here",
    "api_secret": "your_api_secret_here",
    "redirect_uri": "https://external.app-template.com/callback"
}
```

**Response (Success):**

```json
{
    "success": true,
    "token_type": "Bearer",
    "access_token": "sanctum_token_here",
    "expires_at": null,
    "user": {
        "id": 1,
        "net_id": "jdoe",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "roles": ["user", "editor"]
    },
    "application": {
        "id": 1,
        "name": "My External App"
    }
}
```

**Response (Failed):**

```json
{
    "success": false,
    "error": "invalid_grant",
    "message": "Authorization code is invalid, consumed, or expired"
}
```

### Security Notes

1. Access tokens are never sent in front-channel redirects.
2. Authorization codes are one-time use and expire quickly.
3. Redirect URI is bound to application registration and to the issued code.
4. Failed code replay returns `invalid_grant`.

### Authentication Endpoints

#### 1. Authenticate User

**POST** `/api/auth/authenticate`

Authenticates a user with their credentials and returns user information plus access token.

**Request:**

```json
{
    "api_key": "app_your_api_key_here",
    "api_secret": "your_api_secret_here", 
    "username": "user@example.com",
    "password": "user_password",
    "domain": "optional_origin_domain"
}
```

**Response (Success):**

```json
{
    "success": true,
    "message": "Authentication successful",
    "user": {
        "id": 1,
        "net_id": "jdoe",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "is_admin": false,
        "roles": ["user", "editor"]
    },
    "token": "sanctum_token_here",
    "application": {
        "id": 1,
        "name": "My External App"
    }
}
```

**Response (Failed):**

```json
{
    "success": false,
    "message": "Invalid user credentials"
}
```

#### 2. Verify User Access

**POST** `/api/auth/verify-access`

Checks if a user has access to your application without authenticating them.

**Request:**

```json
{
    "api_key": "app_your_api_key_here",
    "api_secret": "your_api_secret_here",
    "user_id": 1,
    // OR
    "net_id": "jdoe"
}
```

**Response:**

```json
{
    "success": true,
    "has_access": true,
    "user": {
        "id": 1,
        "net_id": "jdoe", 
        "name": "John Doe",
        "email": "john.doe@example.com",
        "roles": ["user", "editor"]
    },
    "application": {
        "id": 1,
        "name": "My External App"
    }
}
```

#### 3. Get User Information

**POST** `/api/auth/user-info`

Retrieves detailed user information including roles and accessible applications.

**Request:**

```json
{
    "api_key": "app_your_api_key_here",
    "api_secret": "your_api_secret_here",
    "user_id": 1
    // OR
    "net_id": "jdoe"
}
```

**Response:**

```json
{
    "success": true,
    "user": {
        "id": 1,
        "net_id": "jdoe",
        "name": "John Doe", 
        "email": "john.doe@example.com",
        "is_admin": false,
        "roles": [
            {
                "id": 1,
                "name": "user",
                "description": "Basic User"
            }
        ],
        "applications": [
            {
                "id": 1,
                "name": "Main App",
                "description": "Main Application"
            }
        ]
    }
}
```

#### 4. Check User Role

**POST** `/api/auth/check-role`

Checks if a user has a specific role.

**Request:**

```json
{
    "api_key": "app_your_api_key_here",
    "api_secret": "your_api_secret_here",
    "user_id": 1,
    "role_name": "admin"
}
```

**Response:**

```json
{
    "success": true,
    "has_role": false,
    "user": {
        "id": 1,
        "net_id": "jdoe",
        "name": "John Doe"
    },
    "role_name": "admin"
}
```

#### 5. Health Check

**GET** `/api/auth/health`

Check if the API is operational.

**Response:**

```json
{
    "success": true,
    "message": "API is healthy",
    "timestamp": "2024-04-24T15:30:00.000Z",
    "auth_type": "laravel",
    "version": "1.0.0"
}
```

## Security Features

### API Credentials

- **API Key**: Unique identifier for your application (`app_` prefix + 48 random characters)
- **API Secret**: 64-character random secret, hashed and stored securely
- **Expiration**: API keys can have expiration dates for enhanced security
- **Domain Restrictions**: Optional whitelist of allowed domains/origins

### Request Authentication

API credentials can be provided via:

1. **Headers** (Recommended):

   ```json
   X-API-Key: app_your_api_key_here
   X-API-Secret: your_api_secret_here
   ```

2. **Request Body**:

   ```json
   {
       "api_key": "app_your_api_key_here",
       "api_secret": "your_api_secret_here"
   }
   ```

### Domain Validation

- The API validates the `Origin` header against configured allowed domains
- Set allowed domains when generating credentials to restrict access
- Leave domains empty for no restrictions (not recommended for production)

## Authentication Types

### Laravel Authentication

- Requires username/email and password
- Users are authenticated against the local database
- Passwords are verified using Laravel's Hash::check()

### CAS Authentication  

- For CAS environments, external applications should handle CAS authentication
- Pass the authenticated username to this API (password not required)
- The API will lookup the user by `net_id`

## Error Handling

All API responses follow this structure:

**Success Response:**

```json
{
    "success": true,
    "message": "Success message",
    "data": { /* response data */ }
}
```

**Error Response:**

```json
{
    "success": false,
    "message": "Error description",
    "errors": { /* validation errors if applicable */ }
}
```

**Common HTTP Status Codes:**

- `200`: Success
- `401`: Unauthorized (invalid API credentials or user credentials)
- `403`: Forbidden (valid credentials but no access)
- `404`: Not Found (user not found)
- `422`: Validation Error (invalid request data)
- `500`: Server Error

## Example Implementation

### PHP/Laravel Example

```php
class ExternalAuthService 
{
    private $apiKey;
    private $apiSecret;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('auth_service.api_key');
        $this->apiSecret = config('auth_service.api_secret');
        $this->baseUrl = config('auth_service.base_url');
    }

    public function authenticateUser($username, $password)
    {
        $response = Http::post($this->baseUrl . '/api/auth/authenticate', [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                return [
                    'user' => $data['user'],
                    'token' => $data['token']
                ];
            }
        }

        return null;
    }

    public function checkUserAccess($userId)
    {
        $response = Http::post($this->baseUrl . '/api/auth/verify-access', [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'user_id' => $userId,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['success'] && $data['has_access'];
        }

        return false;
    }
}
```

### JavaScript/Node.js Example

```javascript
class AuthService {
    constructor(apiKey, apiSecret, baseUrl) {
        this.apiKey = apiKey;
        this.apiSecret = apiSecret;
        this.baseUrl = baseUrl;
    }

    async authenticateUser(username, password) {
        try {
            const response = await fetch(`${this.baseUrl}/api/auth/authenticate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey,
                    'X-API-Secret': this.apiSecret,
                },
                body: JSON.stringify({
                    username,
                    password,
                })
            });

            const data = await response.json();
            
            if (data.success) {
                return {
                    user: data.user,
                    token: data.token
                };
            }
            
            throw new Error(data.message);
        } catch (error) {
            console.error('Authentication failed:', error);
            return null;
        }
    }

    async verifyAccess(userId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/auth/verify-access`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey,
                    'X-API-Secret': this.apiSecret,
                },
                body: JSON.stringify({
                    user_id: userId,
                })
            });

            const data = await response.json();
            return data.success && data.has_access;
        } catch (error) {
            console.error('Access verification failed:', error);
            return false;
        }
    }
}
```

## Testing with Postman

Postman is an excellent tool for testing and documenting API endpoints. Here's how to set up and test the Authentication API effectively.

### Setting Up the Postman Collection

#### 1. Create a New Collection

1. Open Postman and create a new collection named "Authentication API"
2. Add a description: "API endpoints for user authentication and access verification"

#### 2. Configure Environment Variables

Create a new environment with these variables:

| Variable        | Type    | Initial Value           | Current Value               |
| --------------- | ------- | ----------------------- | --------------------------- |
| `base_url`      | default | `http://localhost:8000` | `http://localhost:8000`     |
| `api_key`       | secret  | `app_your_api_key_here` | `app_your_api_key_here`     |
| `api_secret`    | secret  | `your_api_secret_here`  | `your_api_secret_here`      |
| `auth_token`    | secret  |                         | (will be set automatically) |
| `user_id`       | default | `1`                     | `1`                         |
| `test_username` | default | `admin@ucr.edu`         | `admin@ucr.edu`             |
| `test_password` | secret  | `password`              | `password`                  |

### API Endpoint Tests

#### 1. Health Check

**GET** `{{base_url}}/api/auth/health`

**Headers:**

``` json
Content-Type: application/json
```

**Tests Script:**

```javascript
pm.test("Health check returns success", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response.message).to.include("healthy");
});
```

#### 2. Authenticate User

**POST** `{{base_url}}/api/auth/authenticate`

**Headers:**

```json
Content-Type: application/json
X-API-Key: {{api_key}}
X-API-Secret: {{api_secret}}
```

**Body (JSON):**

```json
{
    "username": "{{test_username}}",
    "password": "{{test_password}}"
}
```

**Tests Script:**

```javascript
pm.test("Authentication successful", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response.user).to.have.property('id');
    pm.expect(response.token).to.be.a('string');
    
    // Save token for subsequent requests
    pm.environment.set("auth_token", response.token);
    pm.environment.set("user_id", response.user.id.toString());
});

pm.test("User object contains required fields", function () {
    const response = pm.response.json();
    pm.expect(response.user).to.have.property('net_id');
    pm.expect(response.user).to.have.property('name');
    pm.expect(response.user).to.have.property('email');
    pm.expect(response.user).to.have.property('roles');
});
```

#### 3. Verify User Access

**POST** `{{base_url}}/api/auth/verify-access`

**Headers:**

```
Content-Type: application/json
X-API-Key: {{api_key}}
X-API-Secret: {{api_secret}}
```

**Body (JSON):**

```json
{
    "user_id": {{user_id}}
}
```

**Tests Script:**

```javascript
pm.test("Access verification successful", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response).to.have.property('has_access');
    pm.expect(response.user).to.have.property('id');
});
```

#### 4. Get User Information

**POST** `{{base_url}}/api/auth/user-info`

**Headers:**

```json
Content-Type: application/json
X-API-Key: {{api_key}}
X-API-Secret: {{api_secret}}
```

**Body (JSON):**

```json
{
    "user_id": {{user_id}}
}
```

**Tests Script:**

```javascript
pm.test("User info retrieved successfully", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response.user).to.have.property('roles');
    pm.expect(response.user.roles).to.be.an('array');
    pm.expect(response.user).to.have.property('applications');
});
```

#### 5. Check User Role

**POST** `{{base_url}}/api/auth/check-role`

**Headers:**

```
Content-Type: application/json
X-API-Key: {{api_key}}
X-API-Secret: {{api_secret}}
```

**Body (JSON):**

```json
{
    "user_id": {{user_id}},
    "role_name": "admin"
}
```

**Tests Script:**

```javascript
pm.test("Role check completed", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response).to.have.property('has_role');
    pm.expect(response.role_name).to.equal("admin");
});
```

### Collection-Level Tests

Add these as **Collection Tests** to run after every request:

```javascript
pm.test("Response time is less than 1000ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(1000);
});

pm.test("Response has proper content type", function () {
    pm.expect(pm.response.headers.get("Content-Type")).to.include("application/json");
});
```

### Testing Scenarios

#### Scenario 1: Complete Authentication Flow

1. **Health Check** - Verify API is running
2. **Authenticate User** - Login with valid credentials
3. **Verify Access** - Check user has access to application
4. **Get User Info** - Retrieve detailed user information
5. **Check Role** - Verify user has specific role

#### Scenario 2: Error Handling Tests

Create additional requests to test error conditions:

**Invalid API Credentials:**

```json
{
    "username": "{{test_username}}",
    "password": "{{test_password}}"
}

```

*Headers: Use invalid `X-API-Key`*

**Invalid User Credentials:**

```json
{
    "username": "invalid@example.com",
    "password": "wrongpassword"
}
```

**Non-existent User:**

```json
{
    "user_id": 99999
}
```

### Pre-request Scripts

#### Global Authentication Setup

Add this to your collection's **Pre-request Script**:

```javascript
// Automatically set API credentials if not already set
if (!pm.environment.get("api_key") || pm.environment.get("api_key") === "app_your_api_key_here") {
    console.log("⚠️ Please set your actual API credentials in the environment variables");
}

// Add request timestamp for debugging
pm.request.headers.add({
    key: "X-Request-Time",
    value: new Date().toISOString()
});
```

### Environment-Specific Configurations

#### Development Environment

```json
{
    "base_url": "http://localhost:8000",
    "api_key": "app_your_dev_api_key",
    "api_secret": "your_dev_api_secret"
}
```

#### Staging Environment

```json
{
    "base_url": "https://staging.your-domain.com",
    "api_key": "app_your_staging_api_key",
    "api_secret": "your_staging_api_secret"
}
```

#### Production Environment

```json
{
    "base_url": "https://your-domain.com",
    "api_key": "app_your_prod_api_key",
    "api_secret": "your_prod_api_secret"
}
```

### Debugging Tips

1. **Enable Console Logging:**

   ```javascript
   console.log("Request URL:", pm.request.url.toString());
   console.log("Request Headers:", pm.request.headers.toObject());

   console.log("Response:", pm.response.json());
   ```

2. **Check Environment Variables:**

   ```javascript
   console.log("Current Environment:", pm.environment.name);
   console.log("Base URL:", pm.environment.get("base_url"));
   console.log("API Key:", pm.environment.get("api_key"));
   ```

3. **Validate Response Structure:**

   ```javascript
   pm.test("Response has expected structure", function () {
       const schema = {
           type: "object",
           properties: {
               success: { type: "boolean" },
               message: { type: "string" }
           },
           required: ["success", "message"]
       };
       pm.response.to.have.jsonSchema(schema);
   });
   ```

### Exporting and Sharing Collections

1. **Export Collection:** Click the three dots next to your collection → Export → Collection v2.1
2. **Export Environment:** Click the gear icon → Export → Select environment
3. **Share with Team:** Use Postman workspaces or version control for collection files

### Newman CLI Testing

For automated testing, use Newman to run your collection:

```bash
# Install Newman
npm install -g newman

# Run collection
newman run "Authentication API.postman_collection.json" \
  -e "Development.postman_environment.json" \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/auth-api-test-report.html
```

## Best Practices

1. **Secure Credential Storage**: Store API credentials securely (environment variables, encrypted config)
2. **HTTPS Only**: Always use HTTPS in production
3. **Domain Restrictions**: Configure allowed domains for additional security
4. **Credential Rotation**: Regularly rotate API credentials
5. **Error Handling**: Implement proper error handling and logging
6. **Rate Limiting**: Consider implementing rate limiting on your external application
7. **Token Management**: If using Sanctum tokens, implement proper token lifecycle management

## Troubleshooting

### Common Issues

1. **Invalid API Credentials**
   - Verify API key and secret are correct
   - Check if credentials have expired
   - Ensure application is active and supports API auth

2. **Unauthorized Domain**
   - Check if your domain is in the allowed domains list
   - Verify Origin header is being sent correctly
   - Consider temporarily removing domain restrictions for testing

3. **User Not Found**
   - Verify the user exists in the system
   - Check if using correct identifier (net_id vs email)
   - Ensure user account is active

4. **Access Denied**
   - User exists but doesn't have required roles for your application
   - Check application-role assignments
   - Verify application is active

### Debug Commands

```bash
# List all applications and their API status
php artisan app:generate-api-credentials --list

# Check user roles and applications
php artisan tinker
User::where('net_id', 'username')->first()->roles;
Application::find(1)->roles;

# Test API endpoint manually
curl -X GET https://your-domain.com/api/auth/health
```

## Support

For questions or issues with the API authentication service, please:

1. Check the application logs for detailed error messages
2. Verify your API credentials and configuration
3. Test with the health endpoint to ensure basic connectivity
4. Contact the system administrator with specific error messages and request details
