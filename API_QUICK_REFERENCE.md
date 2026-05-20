# Quick API Reference & Testing Guide

## Base URL
```
http://localhost/NewJob/api
```

## Authentication Pattern
Most endpoints require authentication. Include:
- Session cookie set after login
- Or pass login credentials to auth endpoints

## Core Endpoints to Test First

### 1. Authentication
```bash
# Sign up (Step 1)
POST /auth.php?action=send-otp
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "seeker"
}

# Verify OTP (Step 2)
POST /auth.php?action=verify-otp
{
  "email": "john@example.com",
  "otp": "123456"
}

# Complete Signup (Step 3)
POST /auth.php?action=complete-signup
{}

# Login
POST /auth.php?action=login
{
  "email": "john@example.com",
  "password": "password123"
}

# Check Session
GET /auth.php?action=check-session
```

### 2. Jobs
```bash
# Search jobs
GET /jobs.php?action=search&keyword=developer&location=NYC

# Autocomplete job titles
GET /jobs.php?action=autocomplete&q=soft

# List all jobs
GET /jobs.php
```

### 3. Applications
```bash
# Apply for job
POST /applications.php?action=apply
{
  "job_id": 123,
  "resume_note": "My note"
}
```

### 4. Saved Jobs
```bash
# Get saved jobs
GET /saved.php

# Save a job
POST /saved.php
{
  "job_id": 123
}

# Unsave a job
DELETE /saved.php?job_id=123
```

### 5. Profiles
```bash
# Get own profile
GET /profiles.php

# Update profile
POST /profiles.php
{
  "name": "John",
  "phone": "1234567890",
  "skills": "PHP, Laravel"
}
```

### 6. Messages
```bash
# Get conversations
GET /messages.php

# Get messages with user
GET /messages.php?with=123

# Send message
POST /messages.php
{
  "to_user": 123,
  "message": "Hello"
}
```

### 7. Notifications
```bash
# Get notifications
GET /notifications.php

# Mark as read
PUT /notifications.php

# Delete all
DELETE /notifications.php
```

## Common Response Format

### Success
```json
{
  "success": true,
  "data": {...},
  "message": "Operation successful"
}
```

### Error
```json
{
  "success": false,
  "error": "Error message"
}
```

## HTTP Status Codes
- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (no permission)
- `404` - Not Found
- `409` - Conflict (already exists)
- `405` - Method Not Allowed
- `500` - Server Error

## Authentication Headers (if needed)
```
Content-Type: application/json
Accept: application/json
```

## Testing Tools
- Postman (recommended for API testing)
- curl (command line)
- Thunder Client (VS Code extension)
- Insomnia

## Files Fixed in This Session
1. ✅ saved.php - Fixed `checkAuth()` → `authorizeRole()`
2. ✅ rbac.php - Removed duplicate code
3. ✅ jobs_crud.php - Updated session structure
4. ✅ reviews_crud.php - Updated session structure

## Known Security Issues to Address
1. Password reset endpoint lacks email verification
2. Consider rate limiting on OTP endpoint
3. Implement HTTPS in production

## Next Steps for Testing
1. Test signup flow with OTP verification
2. Test login/logout
3. Test job search and applications
4. Test file uploads (resume, profile image)
5. Test role-based access (admin vs employer vs seeker)
6. Test messaging system
7. Load test with multiple concurrent users
