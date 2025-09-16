<?php
/**
 * Email Configuration for Cabinet Inventory System
 * Configure SMTP settings for sending user credentials
 */

// Email configuration - Update these with your email provider settings
class EmailConfig {
    // SMTP Settings - Configure based on your email provider
    public static $smtp_settings = [
        'host' => 'smtp.gmail.com',        // Gmail SMTP server
        'port' => 587,                     // SMTP port (587 for TLS, 465 for SSL)
        'encryption' => 'tls',             // 'tls' or 'ssl'
        'username' => '',                  // Your email address
        'password' => '',                  // Your app password (not regular password)
        'from_name' => 'Cabinet Inventory System',
        'from_email' => '',                // Same as username usually
        'reply_to' => '',                  // Reply-to email
    ];
    
    // Email templates
    public static function getNewUserTemplate($userData) {
        $subject = "🔐 Your New Account - Cabinet Inventory Management System";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0d6efd; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .credentials { background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #0d6efd; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #6c757d; font-size: 0.9em; }
                .btn { display: inline-block; background: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏢 Welcome to Cabinet Inventory System</h1>
                    <p>Your account has been successfully created!</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($userData['first_name']) . " " . htmlspecialchars($userData['last_name']) . ",</h2>
                    
                    <p>Good day! You have been granted access to our <strong>Cabinet Inventory Management System</strong>. This system will help you efficiently manage and track cabinet contents across our organization.</p>
                    
                    <div class='credentials'>
                        <h3>🔑 Your Login Credentials:</h3>
                        <p><strong>Username:</strong> " . htmlspecialchars($userData['username']) . "</p>
                        <p><strong>Password:</strong> " . htmlspecialchars($userData['password']) . "</p>
                        <p><strong>Role:</strong> " . ucfirst(htmlspecialchars($userData['role'])) . "</p>
                        <p><strong>Office:</strong> " . htmlspecialchars($userData['office']) . "</p>
                        <p><strong>Division:</strong> " . htmlspecialchars($userData['division']) . "</p>
                    </div>
                    
                    <p>🚀 <strong>Getting Started:</strong></p>
                    <ul>
                        <li>Use your credentials to log into the system</li>
                        <li>Change your password after first login for security</li>
                        <li>Explore the dashboard to familiarize yourself with the features</li>
                        <li>Contact your administrator if you need assistance</li>
                    </ul>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='#LOGIN_URL#' class='btn'>🔐 Login to System</a>
                    </div>
                    
                    <p>⚠️ <strong>Important Security Notes:</strong></p>
                    <ul>
                        <li>Keep your credentials secure and confidential</li>
                        <li>Do not share your login information with others</li>
                        <li>Change your password after first login</li>
                        <li>Report any suspicious activity immediately</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>Best regards,<br>
                    <strong>Cabinet Inventory System</strong><br>
                    Administrative Team</p>
                    
                    <p><small>This is an automated message. Please do not reply to this email.</small></p>
                </div>
            </div>
        </body>
        </html>";
        
        return ['subject' => $subject, 'message' => $message];
    }
    
    // Alternative plain text template for email clients that don't support HTML
    public static function getNewUserPlainTemplate($userData) {
        $subject = "Your New Account - Cabinet Inventory Management System";
        
        $message = "
Hello " . $userData['first_name'] . " " . $userData['last_name'] . ",

Good day! You have been granted access to our Cabinet Inventory Management System.

Your Login Credentials:
=======================
Username: " . $userData['username'] . "
Password: " . $userData['password'] . "
Role: " . ucfirst($userData['role']) . "
Office: " . $userData['office'] . "
Division: " . $userData['division'] . "

Getting Started:
================
1. Use your credentials to log into the system
2. Change your password after first login for security
3. Explore the dashboard to familiarize yourself with the features
4. Contact your administrator if you need assistance

Important Security Notes:
========================
- Keep your credentials secure and confidential
- Do not share your login information with others
- Change your password after first login
- Report any suspicious activity immediately

Best regards,
Cabinet Inventory System
Administrative Team

---
This is an automated message. Please do not reply to this email.
        ";
        
        return ['subject' => $subject, 'message' => $message];
    }
}

// Helper function to validate email configuration
function validateEmailConfig() {
    $config = EmailConfig::$smtp_settings;
    $errors = [];
    
    if (empty($config['username'])) {
        $errors[] = "SMTP username not configured";
    }
    
    if (empty($config['password'])) {
        $errors[] = "SMTP password not configured";
    }
    
    if (empty($config['from_email'])) {
        $errors[] = "From email not configured";
    }
    
    return empty($errors) ? true : $errors;
}
?>