# TM Customer Data Management System

A secure web-based system for managing Telekom Malaysia (TM) customer data, featuring role-based access control, two-factor authentication, and comprehensive audit logging.

## Features

- **Secure Authentication**
  - Two-factor authentication using OTP
  - Strong password requirements
  - Session management
  - Role-based access control (Admin/Staff)

- **Customer Data Management**
  - Record customer details (name, email, phone, IC number, etc.)
  - Data validation for Malaysian phone numbers and IC
  - Staff can only view and edit their own records
  - Admins have full access to all records

- **Audit Logging**
  - Comprehensive tracking of all data changes
  - Records who made changes and when
  - Detailed logging of what was changed
  - Viewable through admin dashboard

- **User Management (Admin)**
  - Create and manage staff accounts
  - Delete user accounts
  - View all system users
  - Monitor user activities

## Requirements

- XAMPP (PHP 7.4 or higher)
- MySQL
- Web Browser (Chrome/Firefox recommended)
- Composer (for email functionality)

## Installation

1. Clone this repository to your XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\customerdata\
   ```

2. Start XAMPP Apache and MySQL services

3. The database will be created automatically on first access, or you can manually import:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the SQL file from `database/init.sql`

4. Access the system:
   ```
   http://localhost/customerdata/
   ```

5. Default admin credentials:
   - Username: `admin`
   - Password: `admin123`
   - **Important**: Change these credentials after first login

## Email Configuration (Optional)

To enable email-based OTP:

1. Install Composer from https://getcomposer.org/
2. Run in project directory:
   ```
   composer require phpmailer/phpmailer
   ```
3. Configure email settings in `includes/mail_config.php`

## Security Features

- Password Requirements:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
  - At least one special character

- Data Validation:
  - Phone numbers must start with "+60" (Malaysian format)
  - IC numbers must be exactly 12 digits
  - Input sanitization for all fields

- Access Control:
  - Staff can only view/edit their own records
  - Admins have full system access
  - Session timeout protection
  - OTP verification for login

## Directory Structure

```
customerdata/
├── admin/
│   ├── audit_logs.php
│   └── manage_users.php
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── verify_otp.php
├── config/
│   └── database.php
├── includes/
│   ├── functions.php
│   └── mail.php
├── customers/
│   ├── add.php
│   └── edit.php
└── database/
    └── init.sql
```

## Contributing

For any improvements or issues, please contact the system administrator.

## Security Notes

1. Always change the default admin password after installation
2. Keep the system and its dependencies updated
3. Regularly backup the database
4. Monitor audit logs for suspicious activities
5. Ensure proper server security configurations

## Support

For technical support or questions, please contact:
- Email: teckangoh@gmail.com