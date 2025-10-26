# User Approval System Setup Guide

This guide explains how to set up and use the new user approval system for the OnlineBizPermit application.

## Overview

The system now requires admin approval for new user registrations before they can access the applicant dashboard. Admins can review user information, check application history, and approve or reject users.

## Features Implemented

### 1. User Registration Changes
- New users are registered with "pending" status
- Users cannot login until approved by admin
- Clear messaging about pending approval status

### 2. Admin Dashboard Enhancements
- **Pending Users** section in sidebar navigation
- Dashboard shows count of pending users with notification badge
- Comprehensive user review interface

### 3. User Review Process
- View user details and contact information
- Check application history for existing users
- Approve or reject users with one click
- Automatic notification system

### 4. Database Enhancements
- Added `status` column to users table
- Added approval tracking columns
- Optional approval history table

## Setup Instructions

### Step 1: Database Updates

Run the SQL script to update your database:

```sql
-- Execute the database_update.sql file
source database_update.sql;
```

Or manually run these commands:

```sql
-- Add status column
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('pending', 'active', 'rejected') DEFAULT 'active';

-- Update existing users
UPDATE users SET status = 'active' WHERE status IS NULL OR status = '';

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status);
```

### Step 2: File Updates

The following files have been created or modified:

#### New Files Created:
- `Admin-dashboard/pending_users.php` - Main pending users management page
- `Admin-dashboard/view_user_details.php` - Detailed user review page
- `Admin-dashboard/notify_user_approval.php` - Notification system
- `database_update.sql` - Database update script

#### Modified Files:
- `Applicant-dashboard/register.php` - Sets new users as pending
- `Applicant-dashboard/login.php` - Checks user status before login
- `Admin-dashboard/admin_sidebar.php` - Added Pending Users navigation
- `Admin-dashboard/dashboard.php` - Shows pending users count
- `Admin-dashboard/user_management.php` - Added status column display

### Step 3: Test the System

1. **Register a new user** from the applicant dashboard
2. **Check admin dashboard** - you should see the pending users count
3. **Navigate to Pending Users** from the sidebar
4. **Review user details** and application history
5. **Approve or reject** the user
6. **Verify login** - approved users should be able to login

## How It Works

### User Registration Flow

1. User fills out registration form
2. User is created with `status = 'pending'`
3. User is redirected to login with pending message
4. User cannot login until approved

### Admin Review Process

1. Admin sees pending users count on dashboard
2. Admin clicks "Pending Users" in sidebar
3. Admin reviews user details and application history
4. Admin clicks "Approve" or "Reject"
5. User status is updated and notification is sent
6. Approved users can now login

### User Login Process

1. User enters credentials
2. System checks user status
3. If pending: Shows "pending approval" message
4. If rejected: Shows "account rejected" message
5. If active: Allows login to dashboard

## Admin Interface Features

### Pending Users Page
- Lists all users awaiting approval
- Shows user contact information
- Displays application history count
- Quick approve/reject buttons
- Link to detailed user review

### User Details Page
- Complete user information
- Full application history
- Previous application details
- Approve/reject actions
- Application status tracking

### Dashboard Integration
- Pending users count with notification badge
- Quick access to pending users
- Visual indicators for attention needed

## Notification System

The system includes a notification framework that can be extended:

- Logs approval/rejection actions
- Ready for email integration
- Database logging for audit trail
- Customizable notification messages

## Customization Options

### Email Notifications
To enable email notifications, modify `notify_user_approval.php`:

1. Add your SMTP credentials
2. Uncomment the PHPMailer code
3. Configure email templates
4. Test notification delivery

### Approval Workflow
You can customize the approval process by:

1. Adding custom approval criteria
2. Implementing multi-step approval
3. Adding approval comments/notes
4. Setting up approval notifications

### User Status Options
Extend the status system by:

1. Adding new status types in database
2. Updating the ENUM values
3. Modifying the UI to handle new statuses
4. Adding status-specific workflows

## Security Considerations

- All user data is properly sanitized
- SQL injection protection with prepared statements
- XSS protection with htmlspecialchars
- Session management for admin authentication
- Transaction handling for data consistency

## Troubleshooting

### Common Issues

1. **Database errors**: Ensure the database_update.sql script ran successfully
2. **Permission issues**: Check file permissions on new PHP files
3. **Login problems**: Verify user status column exists and has data
4. **Missing navigation**: Clear browser cache to see sidebar updates

### Debug Mode

Enable debug mode by adding to your PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support

For issues or questions about the user approval system:

1. Check the error logs
2. Verify database structure
3. Test with a new user registration
4. Review the admin dashboard functionality

## Future Enhancements

Potential improvements for the system:

1. **Bulk Approval**: Approve multiple users at once
2. **Email Templates**: Customizable approval/rejection emails
3. **Approval Rules**: Automated approval based on criteria
4. **Audit Trail**: Complete history of all approval actions
5. **User Communication**: In-app messaging system
6. **Advanced Filtering**: Filter users by various criteria

---

**Note**: This system is designed to work with your existing OnlineBizPermit application. Make sure to backup your database before running the update script.
