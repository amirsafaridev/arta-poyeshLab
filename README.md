# Arta PoyeshLab - WordPress SMS Authentication Plugin

A comprehensive WordPress plugin for mobile-based authentication and user registration using SMS verification. This plugin provides a secure, user-friendly login and registration system integrated with MeliPayamak SMS gateway.

## 🚀 Features

### 🔐 Authentication System
- **Mobile-based Login & Registration**: Seamless authentication using mobile phone numbers
- **SMS OTP Verification**: Secure two-factor authentication via SMS codes
- **Test Mode Support**: Built-in test code "939393" for development and testing
- **WordPress Session Management**: Native WordPress session handling for secure user sessions

### 📱 SMS Gateway Integration
- **MeliPayamak API Integration**: Direct integration with MeliPayamak SMS service
- **OTP Code Storage**: Secure storage of OTP codes in WordPress options table
- **Auto-expiration**: Automatic code expiration after 2 minutes
- **Attempt Limiting**: Maximum 3 verification attempts per OTP code

### 📊 Logging System
- **Activity Logging**: Tracks the last 50 system activities
- **Multiple Log Types**: SMS sending, login attempts, registrations, code verifications
- **Admin Dashboard**: View statistics and logs in WordPress admin panel
- **Log Management**: Clear logs functionality for maintenance

### ⚙️ Admin Settings Panel
- **SMS Gateway Configuration**: Configure MeliPayamak credentials (username, password, sender number)
- **Login Page Customization**: Upload logo and customize login page texts
- **Log Viewer**: Monitor and manage system logs
- **Connection Testing**: Test SMS gateway connectivity directly from admin panel

### 🎨 User Interface
- **Responsive Design**: Built with Tailwind CSS for modern, responsive layouts
- **RTL Support**: Full support for Persian/Farsi and right-to-left languages
- **Smooth Animations**: Enhanced user experience with fluid transitions
- **Mobile Optimized**: Fully responsive design for all device sizes

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active MeliPayamak account with API access
- WordPress admin access for configuration

## 🔧 Installation

### Method 1: Manual Installation

1. **Download the Plugin**
   ```bash
   # Clone or download the repository
   git clone https://github.com/yourusername/arta-poyeshLab.git
   ```

2. **Upload to WordPress**
   - Copy the `arta-poyeshLab` folder to your WordPress plugins directory:
     ```
     wp-content/plugins/arta-poyeshLab/
     ```

3. **Activate the Plugin**
   - Navigate to **Plugins** in your WordPress admin panel
   - Find **"Arta PoyeshLab"** and click **Activate**

### Method 2: WordPress Admin Upload

1. Go to **Plugins → Add New → Upload Plugin**
2. Select the plugin zip file
3. Click **Install Now** and then **Activate**

## ⚙️ Configuration

### Initial Setup

1. **Access Plugin Settings**
   - Navigate to **"Arta PoyeshLab"** menu in WordPress admin sidebar

2. **Configure SMS Gateway**
   - Enter your MeliPayamak credentials:
     - **Username**: Your MeliPayamak panel username
     - **Password**: Your MeliPayamak panel password
     - **Sender Number**: Your approved sender number
   - Click **"Test Connection"** to verify settings

3. **Customize Login Page**
   - Upload your logo image
   - Customize login page texts and messages
   - Save changes

4. **Test the System**
   - Visit `/lab-portal` on your WordPress site
   - Test registration or login process
   - Use test code `939393` for development testing

## 📖 Usage

### For End Users

1. **Access Portal**
   - Navigate to `/lab-portal` on your website

2. **Registration Process**
   - Enter your personal information
   - Receive verification code via SMS
   - Enter the 6-digit code to complete registration

3. **Login Process**
   - Enter your mobile phone number
   - Receive verification code via SMS
   - Enter the 6-digit code to access your account

### For Administrators

1. **Monitor System**
   - View system logs in **"Arta PoyeshLab → System Logs"**
   - Check SMS sending statistics
   - Monitor authentication attempts

2. **Manage Settings**
   - Update SMS gateway credentials
   - Customize login page appearance
   - Clear system logs when needed

## 📁 File Structure

```
arta-poyeshLab/
├── arta-poyeshLab.php              # Main plugin file
├── include/
│   ├── apl-main.php                # Core plugin class
│   ├── function.php                # Helper functions
│   ├── classes/
│   │   ├── apl-sms-handler.php     # SMS handler class
│   │   ├── apl-logger.php          # Logging system class
│   │   ├── apl-admin-settings.php  # Admin settings class
│   │   ├── apl-ajax-handlers.php   # AJAX request handlers
│   │   ├── apl-auth.php            # Authentication class
│   │   ├── apl-cron.php            # Scheduled tasks handler
│   │   └── apl-my-account.php      # User account management
│   └── template/
│       ├── layout.php              # Main template layout
│       ├── auth.php                # Login/Registration page
│       └── dashbord.php            # User dashboard
└── assets/
    ├── css/
    │   └── style.css               # Custom styles
    └── js/
        └── script.js               # JavaScript functionality
```

## 🔧 Advanced Configuration

### Customize OTP Expiration Time

Edit `include/classes/apl-sms-handler.php`:

```php
'expires' => time() + 120, // 2 minutes (120 seconds)
```

Change the value to adjust expiration time in seconds.

### Modify Log Retention

Edit `include/classes/apl-logger.php`:

```php
private $max_logs = 50; // Number of logs to retain
```

Adjust the value to change how many logs are stored.

### Change OTP Attempt Limit

Edit `include/classes/apl-sms-handler.php`:

```php
if ($otp_data['attempts'] >= 3) { // Maximum 3 attempts
```

Modify the number to change the maximum verification attempts.

## 🐛 Troubleshooting

### SMS Not Sending

1. **Verify SMS Gateway Settings**
   - Check MeliPayamak credentials in admin panel
   - Ensure sender number is approved and active
   - Verify account balance

2. **Test Connection**
   - Use the "Test Connection" feature in admin panel
   - Check system logs for error messages

3. **Check System Logs**
   - Review logs in **"Arta PoyeshLab → System Logs"**
   - Look for SMS sending errors or API failures

### Login Issues

1. **User Verification**
   - Ensure user exists in WordPress database
   - Check user meta data for mobile number

2. **OTP Code Issues**
   - Verify OTP code hasn't expired (2-minute window)
   - Check if maximum attempts (3) haven't been exceeded
   - Use test code `939393` for testing

3. **Session Problems**
   - Clear browser cookies and cache
   - Check WordPress session configuration

### Display Problems

1. **Plugin Activation**
   - Verify plugin is activated in WordPress admin
   - Check for plugin conflicts with other plugins

2. **Asset Loading**
   - Ensure CSS and JS files are loading correctly
   - Check browser console for JavaScript errors
   - Verify file permissions (should be 644 for files, 755 for directories)

3. **Template Issues**
   - Clear WordPress cache if using caching plugins
   - Check theme compatibility

## 🔒 Security Considerations

- OTP codes expire after 2 minutes
- Maximum 3 verification attempts per code
- Secure storage of OTP data in WordPress options
- WordPress native session management
- Input validation and sanitization
- Protection against brute force attacks

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## 📝 Changelog

### Version 1.0.0
- Initial release
- SMS authentication system
- Admin settings panel
- Logging system
- Responsive UI with Tailwind CSS

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## 📄 License

This plugin is licensed under the **GPL v2 or later**.

```
Copyright (C) 2024 Arta PoyeshLab

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Arta PoyeshLab Development Team**

## 📧 Support

For support, bug reports, or feature requests, please open an issue on GitHub or contact the development team.

---

**Made with ❤️ for the WordPress community**
