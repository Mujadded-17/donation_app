# Donation App - Setup & Testing Guide

## Fixed Issues

### Backend PHP Files
1. **Table Name Case Sensitivity** - Fixed all SQL queries to use lowercase `user` and `item` table names instead of uppercase `User` and `Item`
   - `auth_register.php` - Fixed INSERT INTO user
   - `auth_login.php` - Fixed SELECT FROM user  
   - `items_list.php` - Fixed SELECT FROM item

2. **Error Handling** - Added comprehensive error handling to all backend files:
   - Database prepare/execute error details
   - Proper error messages returned to frontend

3. **CORS Headers** - Added proper CORS headers to `items_list.php`

### Frontend React Files
1. **Error Display** - Enhanced error handling in all pages to show detailed backend errors:
   - `Login.jsx` - Shows specific DB/API errors
   - `Register.jsx` - Shows specific validation/DB errors
   - `PostDonation.jsx` - Shows detailed submission errors
   - `MyDonations.jsx` - Shows fetch errors with details
   - `Items.jsx` - Shows loading errors with details

2. **Console Logging** - Added response logging for easier debugging

## Testing the Application

### 1. Start Backend (XAMPP)
```powershell
# Make sure XAMPP is running with Apache and MySQL
# Apache should be listening on port 80 or 8080
# MySQL should be listening on port 3306
```

### 2. Import Database
- Open phpMyAdmin (http://localhost/phpmyadmin)
- Create database named `donation_app` if it doesn't exist
- Import `database/donation_app.sql`

### 3. Verify Backend URL
The backend should be accessible at:
```
http://localhost/donation_backend
```

Test with:
```powershell
curl http://localhost/donation_backend/test_db.php
```

### 4. Start Frontend
```powershell
cd frontend
npm install  # if not already done
npm run dev
```

### 5. Test Features

#### Registration
1. Go to Register page
2. Fill in all fields
3. Click "Create Account"
4. Check browser console for response details
5. Should redirect to login on success

#### Login
1. Go to Login page
2. Use credentials:
   - Email: `rahim@gmail.com`
   - Password: `hashed123` (or register new user)
3. Should redirect to home on success

#### Post Donation
1. Login first
2. Go to Post Donation page
3. Fill in item details and upload image
4. Submit
5. Check console for detailed error if it fails

#### View Items
1. Go to Items page
2. Should see list of available items
3. Check console if nothing loads

## Common Issues

### "Failed to submit" or "Network error"
- **Cause**: Backend not running or wrong URL
- **Fix**: 
  1. Check XAMPP Apache is running
  2. Verify `http://localhost/donation_backend` is accessible
  3. Check browser console for exact error

### "DB prepare failed" or "Table doesn't exist"
- **Cause**: Database not imported or wrong table names
- **Fix**: 
  1. Import `database/donation_app.sql` in phpMyAdmin
  2. Verify tables are lowercase: `user`, `item`, `donation`, etc.

### "Duplicate entry" on registration
- **Cause**: Email already exists in database
- **Fix**: Use a different email or clear existing data

### Image upload fails
- **Cause**: Upload directory doesn't exist or no permissions
- **Fix**: 
  1. Check `donation_backend/uploads/items/` directory exists
  2. Ensure directory has write permissions (777 on development)

## Environment Variables

Create `frontend/.env` if you need custom backend URL:
```
VITE_API_BASE_URL=http://localhost/donation_backend
```

## Browser Console

All API calls now log responses. Open browser DevTools (F12) and check Console tab for:
- "Login response:", "Register response:", etc.
- Network errors with full details
- Backend error messages

This will help identify exactly where the problem is.
