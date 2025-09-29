<?php

/**
 * Email Service for Cabinet Management System
 * Handles sending emails with user credentials and configuration management
 */

// Turn off error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'email_config.php';

// Handle AJAX requests for email configuration management
// Only process if this is a direct request to email_service.php, not when included
if (basename($_SERVER['PHP_SELF']) === 'email_service.php' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request by looking for JSON content type or specific parameters
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $isAjax = (strpos($contentType, 'application/json') !== false) ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

    // Only process as AJAX if it's actually an AJAX request
    if ($isAjax) {
        // Set JSON header for AJAX responses
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
                exit;
            }

            if ($input['action'] === 'save_config') {
                echo json_encode(EmailService::saveEmailConfig($input['config']));
            } elseif ($input['action'] === 'test_email') {
                echo json_encode(EmailService::sendTestEmail($input['test_email']));
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

if (basename($_SERVER['PHP_SELF']) === 'email_service.php' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Only process GET requests that have an action parameter (AJAX requests)
    if (isset($_GET['action'])) {
        // Set JSON header for AJAX responses (except preview which needs HTML)
        if ($_GET['action'] !== 'preview') {
            header('Content-Type: application/json');
        }

        try {
            if ($_GET['action'] === 'get_config') {
                echo json_encode(EmailService::getEmailConfig());
            } elseif ($_GET['action'] === 'preview') {
                // Preview doesn't return JSON, it returns HTML
                header('Content-Type: text/html');
                EmailService::previewEmailTemplate();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

class EmailService
{

    /**
     * Save email configuration to user config file
     */
    public static function saveEmailConfig($config)
    {
        try {
            $configData = [
                'from_email' => $config['from_email'],
                'from_name' => $config['from_name'],
                'reply_to' => $config['reply_to'],
                'smtp_host' => $config['smtp_host'],
                'smtp_port' => intval($config['smtp_port']),
                'smtp_username' => $config['smtp_username'],
                'smtp_password' => $config['smtp_password'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $configFile = __DIR__ . '/email_config_user.json';
            file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

            self::logActivity('CONFIG_UPDATED', 'Email configuration updated by admin');

            return ['success' => true, 'message' => 'Configuration saved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to save configuration: ' . $e->getMessage()];
        }
    }

    public static function getEmailConfig()
    {
        try {
            $configFile = __DIR__ . '/email_config_user.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                // Remove password from response for security
                unset($config['smtp_password']);
                return ['success' => true, 'config' => $config];
            } else {
                return ['success' => true, 'config' => []];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to load configuration: ' . $e->getMessage()];
        }
    }

    public static function sendTestEmail($testEmail)
    {
        try {
            // Validate email address
            if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email address provided'];
            }

            $testData = [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $testEmail,
                'username' => 'testuser',
                'password' => 'test123',
                'role' => 'encoder',
                'office' => 'Test Office',
                'division' => 'Test Division'
            ];

            $result = self::sendNewUserEmail($testData);
            if ($result['success']) {
                self::logActivity('TEST_EMAIL_SENT', "Test email sent to: $testEmail");
                return ['success' => true, 'message' => 'Test email sent successfully'];
            } else {
                self::logActivity('TEST_EMAIL_FAILED', "Test email failed: " . $result['message']);
                return ['success' => false, 'message' => 'Test email failed: ' . $result['message']];
            }
        } catch (Exception $e) {
            self::logActivity('TEST_EMAIL_FAILED', "Test email failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Test email failed: ' . $e->getMessage()];
        }
    }

    public static function previewEmailTemplate()
    {
        $testData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'username' => 'johndoe',
            'password' => 'tempPassword123',
            'role' => 'encoder',
            'office' => 'Main Office',
            'division' => 'IT Department'
        ];

        $template = EmailConfig::getNewUserTemplate($testData);
        $loginUrl = self::getLoginUrl();
        $template['message'] = str_replace('#LOGIN_URL#', $loginUrl, $template['message']);

        echo $template['message'];
    }

    /**
     * Load user configuration merged with defaults
     */
    private static function loadUserConfig()
    {
        $configFile = __DIR__ . '/email_config_user.json';
        $userConfig = [];

        if (file_exists($configFile)) {
            $userConfig = json_decode(file_get_contents($configFile), true) ?: [];
        }

        // Merge with defaults
        $defaultConfig = EmailConfig::$smtp_settings;
        return array_merge($defaultConfig, $userConfig);
    }

    /**
     * Send email using basic PHP mail() function or SMTP if configured
     */
    public static function sendNewUserEmail($userData)
    {
        $config = self::loadUserConfig();

        // Use SMTP if configured
        if (!empty($config['smtp_host']) && !empty($config['smtp_username'])) {
            return self::sendNewUserEmailSMTP($userData);
        } else {
            return self::sendNewUserEmailBasic($userData);
        }
    }

    /**
     * Send email using basic PHP mail() function
     */
    private static function sendNewUserEmailBasic($userData)
    {
        try {
            $config = self::loadUserConfig();

            // Debug: Log configuration (without password)
            $debugConfig = $config;
            unset($debugConfig['smtp_password']);
            self::logActivity('EMAIL_DEBUG', 'Using config: ' . json_encode($debugConfig));

            // Get email template
            $template = EmailConfig::getNewUserTemplate($userData);
            $plainTemplate = EmailConfig::getNewUserPlainTemplate($userData);

            // Replace login URL placeholder
            $loginUrl = self::getLoginUrl();
            $template['message'] = str_replace('#LOGIN_URL#', $loginUrl, $template['message']);

            // For Gmail, we need to use SMTP, not basic mail()
            if (strpos($config['from_email'], '@gmail.com') !== false) {
                self::logActivity('EMAIL_DEBUG', 'Gmail detected - basic mail() function cannot send via Gmail SMTP');
                return ['success' => false, 'message' => 'Gmail requires SMTP configuration. Basic PHP mail() cannot send Gmail emails. Please install PHPMailer for SMTP support.'];
            }

            // Email headers for HTML email
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
                'Reply-To: ' . ($config['reply_to'] ?: $config['from_email']),
                'X-Mailer: Cabinet Management System',
                'X-Priority: 1',
                'Importance: High'
            ];

            // Send email
            $success = mail(
                $userData['email'],
                $template['subject'],
                $template['message'],
                implode("\r\n", $headers)
            );

            if ($success) {
                self::logEmailActivity($userData, true, 'Email sent successfully');
                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                $lastError = error_get_last();
                $errorMsg = $lastError ? $lastError['message'] : 'Unknown error';
                self::logEmailActivity($userData, false, 'Failed to send email: ' . $errorMsg);
                return ['success' => false, 'message' => 'Failed to send email: ' . $errorMsg];
            }
        } catch (Exception $e) {
            self::logEmailActivity($userData, false, 'Email error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
        }
    }

    /**
     * Send email using SMTP (requires PHPMailer) - fallback to basic mail if not available
     */
    private static function sendNewUserEmailSMTP($userData)
    {
        try {
            $config = self::loadUserConfig();

            // Use our custom Gmail SMTP function for Gmail addresses
            if (strpos($config['from_email'], '@gmail.com') !== false) {
                return self::sendGmailSMTP($userData, $config);
            }

            // For non-Gmail, use PHPMailer if available, otherwise basic mail
            $phpMailerPath = __DIR__ . '/../phpmailer/src/PHPMailer.php';
            if (file_exists($phpMailerPath)) {
                require_once __DIR__ . '/../phpmailer/src/Exception.php';
                require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
                require_once __DIR__ . '/../phpmailer/src/SMTP.php';

                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                    $mail->isSMTP();
                    $mail->Host = $config['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['smtp_username'];
                    $mail->Password = $config['smtp_password'];
                    $mail->SMTPSecure = $config['smtp_port'] == 465 ? 'ssl' : 'tls';
                    $mail->Port = $config['smtp_port'];

                    $mail->setFrom($config['from_email'], $config['from_name']);
                    $mail->addAddress($userData['email'], $userData['first_name'] . ' ' . $userData['last_name']);
                    if (!empty($config['reply_to'])) {
                        $mail->addReplyTo($config['reply_to'], $config['from_name']);
                    }

                    $template = EmailConfig::getNewUserTemplate($userData);
                    $loginUrl = self::getLoginUrl();
                    $template['message'] = str_replace('#LOGIN_URL#', $loginUrl, $template['message']);

                    $mail->isHTML(true);
                    $mail->Subject = $template['subject'];
                    $mail->Body = $template['message'];
                    $mail->AltBody = EmailConfig::getNewUserPlainTemplate($userData)['message'];

                    $mail->send();
                    self::logEmailActivity($userData, true, 'Email sent successfully via SMTP');
                    return ['success' => true, 'message' => 'Email sent successfully via SMTP'];
                } catch (Exception $e) {
                    self::logEmailActivity($userData, false, 'SMTP Error: ' . $e->getMessage());
                    return ['success' => false, 'message' => 'SMTP Error: ' . $e->getMessage()];
                }
            }

            // Fallback to basic mail
            return self::sendNewUserEmailBasic($userData);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
        }
    }

    /**
     * Send email via Gmail SMTP using raw socket connection
     */
    private static function sendGmailSMTP($userData, $config)
    {
        try {
            // Validate required Gmail settings
            if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
                return ['success' => false, 'message' => 'Gmail SMTP username and password are required'];
            }

            // Create socket connection to Gmail SMTP
            $smtp = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 30);
            if (!$smtp) {
                return ['success' => false, 'message' => "Gmail SMTP connection failed: $errstr ($errno)"];
            }

            // Helper function to read SMTP responses
            $readResponse = function () use ($smtp) {
                $response = '';
                while ($line = fgets($smtp, 512)) {
                    $response .= $line;
                    if (isset($line[3]) && $line[3] == ' ') break;
                }
                return trim($response);
            };

            // Read initial response
            $response = $readResponse();
            if (strpos($response, '220') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail SMTP server not ready: ' . $response];
            }

            // Send EHLO
            fputs($smtp, "EHLO localhost\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail EHLO failed: ' . $response];
            }

            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            $response = $readResponse();
            if (strpos($response, '220') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail STARTTLS failed: ' . $response];
            }

            // Enable TLS encryption
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail TLS encryption failed'];
            }

            // Send EHLO again after TLS
            fputs($smtp, "EHLO localhost\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail EHLO after TLS failed: ' . $response];
            }

            // Authenticate with Gmail
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = $readResponse();
            if (strpos($response, '334') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail AUTH LOGIN failed: ' . $response];
            }

            fputs($smtp, base64_encode($config['smtp_username']) . "\r\n");
            $response = $readResponse();
            if (strpos($response, '334') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail username rejected: ' . $response];
            }

            fputs($smtp, base64_encode($config['smtp_password']) . "\r\n");
            $response = $readResponse();
            if (strpos($response, '235') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail authentication failed. Please check your App Password: ' . $response];
            }

            // Set sender
            fputs($smtp, "MAIL FROM: <{$config['from_email']}>\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail sender rejected: ' . $response];
            }

            // Set recipient
            fputs($smtp, "RCPT TO: <{$userData['email']}>\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail recipient rejected: ' . $response];
            }

            // Send email data
            fputs($smtp, "DATA\r\n");
            $response = $readResponse();
            if (strpos($response, '354') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail DATA command failed: ' . $response];
            }

            // Prepare email message
            $template = EmailConfig::getNewUserTemplate($userData);
            $loginUrl = self::getLoginUrl();
            $template['message'] = str_replace('#LOGIN_URL#', $loginUrl, $template['message']);

            $message = "From: {$config['from_name']} <{$config['from_email']}>\r\n";
            $message .= "To: {$userData['email']}\r\n";
            if (!empty($config['reply_to'])) {
                $message .= "Reply-To: {$config['reply_to']}\r\n";
            }
            $message .= "Subject: {$template['subject']}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Date: " . date('r') . "\r\n";
            $message .= "\r\n";
            $message .= $template['message'];

            fputs($smtp, $message . "\r\n.\r\n");
            $response = $readResponse();

            // Quit
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);

            if (strpos($response, '250') === 0) {
                self::logEmailActivity($userData, true, 'Email sent successfully via Gmail SMTP');
                return ['success' => true, 'message' => 'Email sent successfully via Gmail SMTP'];
            } else {
                self::logEmailActivity($userData, false, 'Gmail send failed: ' . $response);
                return ['success' => false, 'message' => 'Gmail send failed: ' . $response];
            }
        } catch (Exception $e) {
            if (isset($smtp) && is_resource($smtp)) {
                fclose($smtp);
            }
            self::logEmailActivity($userData, false, 'Gmail SMTP error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gmail SMTP error: ' . $e->getMessage()];
        }
    }

    /**
     * Get the login URL for the system
     */
    private static function getLoginUrl()
    {
        // Try to read from network_config.json first
        $configFile = __DIR__ . '/../network_config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['base_url'])) {
                return $config['base_url'] . '/admin/login.php';
            }
        }
        
        // Fallback to current server detection
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/admin/login.php';
    }

    /**
     * Validate email address
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Log email activity
     */
    public static function logEmailActivity($userData, $success, $message)
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'recipient' => $userData['email'],
            'username' => $userData['username'],
            'success' => $success,
            'message' => $message
        ];

        // Log to file (create logs directory if needed)
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/email_log.txt';
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log general activity
     */
    private static function logActivity($action, $details)
    {
        $logFile = __DIR__ . '/../logs/email_activity.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $action: $details" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail($email, $fullName, $resetUrl, $token)
    {
        try {
            $config = self::loadUserConfig();
            
            // Use SMTP if configured
            if (!empty($config['smtp_host']) && !empty($config['smtp_username'])) {
                return self::sendPasswordResetEmailSMTP($email, $fullName, $resetUrl, $token);
            } else {
                return self::sendPasswordResetEmailBasic($email, $fullName, $resetUrl, $token);
            }
        } catch (Exception $e) {
            self::logActivity('PASSWORD_RESET_ERROR', 'Failed to send password reset email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send password reset email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send password reset email using basic PHP mail() function
     */
    private static function sendPasswordResetEmailBasic($email, $fullName, $resetUrl, $token)
    {
        try {
            $config = self::loadUserConfig();
            
            $subject = 'Password Reset Request - Cabinet Management System';
            $message = self::generatePasswordResetEmailContent($fullName, $resetUrl, $token);
            
            $headers = [
                'From: ' . ($config['from_name'] ?? 'Cabinet Management System') . ' <' . ($config['from_email'] ?? 'noreply@example.com') . '>',
                'Reply-To: ' . ($config['reply_to'] ?? $config['from_email'] ?? 'noreply@example.com'),
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailer: Cabinet Management System'
            ];
            
            $success = mail($email, $subject, $message, implode("\r\n", $headers));
            
            if ($success) {
                self::logActivity('PASSWORD_RESET_SENT', "Password reset email sent to {$email}");
                return ['success' => true, 'message' => 'Password reset email sent successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to send email using basic mail function'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error sending password reset email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send password reset email using SMTP
     */
    private static function sendPasswordResetEmailSMTP($email, $fullName, $resetUrl, $token)
    {
        try {
            $config = self::loadUserConfig();
            
            // Use Gmail SMTP for Gmail addresses
            if (strpos($config['smtp_username'], '@gmail.com') !== false) {
                return self::sendGmailPasswordResetSMTP($email, $fullName, $resetUrl, $token, $config);
            }
            
            // Use PHPMailer for other SMTP providers
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return self::sendPasswordResetEmailPHPMailer($email, $fullName, $resetUrl, $token, $config);
            }
            
            // Fallback to basic mail
            return self::sendPasswordResetEmailBasic($email, $fullName, $resetUrl, $token);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error sending password reset email via SMTP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send password reset email via Gmail SMTP
     */
    private static function sendGmailPasswordResetSMTP($email, $fullName, $resetUrl, $token, $config)
    {
        try {
            // Validate required Gmail settings
            if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
                return ['success' => false, 'message' => 'Gmail SMTP username and password are required'];
            }

            // Create socket connection to Gmail SMTP
            $smtp = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 30);
            if (!$smtp) {
                return ['success' => false, 'message' => "Gmail SMTP connection failed: $errstr ($errno)"];
            }

            // Helper function to read SMTP responses
            $readResponse = function () use ($smtp) {
                $response = '';
                while ($line = fgets($smtp, 512)) {
                    $response .= $line;
                    if (isset($line[3]) && $line[3] == ' ') break;
                }
                return trim($response);
            };

            // Read initial response
            $response = $readResponse();
            if (strpos($response, '220') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail SMTP server not ready: ' . $response];
            }

            // Send EHLO
            fputs($smtp, "EHLO localhost\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail EHLO failed: ' . $response];
            }

            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            $response = $readResponse();
            if (strpos($response, '220') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail STARTTLS failed: ' . $response];
            }

            // Enable TLS encryption
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail TLS encryption failed'];
            }

            // Send EHLO again after TLS
            fputs($smtp, "EHLO localhost\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail EHLO after TLS failed: ' . $response];
            }

            // Authenticate with Gmail
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = $readResponse();
            if (strpos($response, '334') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail AUTH LOGIN failed: ' . $response];
            }

            fputs($smtp, base64_encode($config['smtp_username']) . "\r\n");
            $response = $readResponse();
            if (strpos($response, '334') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail username rejected: ' . $response];
            }

            fputs($smtp, base64_encode($config['smtp_password']) . "\r\n");
            $response = $readResponse();
            if (strpos($response, '235') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail authentication failed. Please check your App Password: ' . $response];
            }

            // Set sender
            fputs($smtp, "MAIL FROM: <{$config['from_email']}>\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail sender rejected: ' . $response];
            }

            // Set recipient
            fputs($smtp, "RCPT TO: <{$email}>\r\n");
            $response = $readResponse();
            if (strpos($response, '250') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail recipient rejected: ' . $response];
            }

            // Send email data
            fputs($smtp, "DATA\r\n");
            $response = $readResponse();
            if (strpos($response, '354') !== 0) {
                fclose($smtp);
                return ['success' => false, 'message' => 'Gmail DATA command failed: ' . $response];
            }

            // Prepare password reset email message
            $subject = 'Password Reset Request - Cabinet Management System';
            $message = self::generatePasswordResetEmailContent($fullName, $resetUrl, $token);

            $emailMessage = "From: {$config['from_name']} <{$config['from_email']}>\r\n";
            $emailMessage .= "To: {$email}\r\n";
            if (!empty($config['reply_to'])) {
                $emailMessage .= "Reply-To: {$config['reply_to']}\r\n";
            }
            $emailMessage .= "Subject: {$subject}\r\n";
            $emailMessage .= "MIME-Version: 1.0\r\n";
            $emailMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailMessage .= "Date: " . date('r') . "\r\n";
            $emailMessage .= "\r\n";
            $emailMessage .= $message;

            fputs($smtp, $emailMessage . "\r\n.\r\n");
            $response = $readResponse();

            // Quit
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);

            if (strpos($response, '250') === 0) {
                self::logActivity('PASSWORD_RESET_SENT', "Password reset email sent to {$email} via Gmail SMTP");
                return ['success' => true, 'message' => 'Password reset email sent successfully via Gmail SMTP'];
            } else {
                self::logActivity('PASSWORD_RESET_ERROR', 'Gmail password reset send failed: ' . $response);
                return ['success' => false, 'message' => 'Gmail password reset send failed: ' . $response];
            }
        } catch (Exception $e) {
            if (isset($smtp) && is_resource($smtp)) {
                fclose($smtp);
            }
            self::logActivity('PASSWORD_RESET_ERROR', 'Gmail password reset SMTP error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gmail password reset SMTP error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send password reset email using PHPMailer
     */
    private static function sendPasswordResetEmailPHPMailer($email, $fullName, $resetUrl, $token, $config)
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'];
            
            // Recipients
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($email, $fullName);
            $mail->addReplyTo($config['reply_to'] ?? $config['from_email']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Cabinet Management System';
            $mail->Body = self::generatePasswordResetEmailContent($fullName, $resetUrl, $token);
            
            $mail->send();
            
            self::logActivity('PASSWORD_RESET_SENT', "Password reset email sent to {$email} via PHPMailer");
            return ['success' => true, 'message' => 'Password reset email sent successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error sending password reset email via PHPMailer: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate password reset email content
     */
    private static function generatePasswordResetEmailContent($fullName, $resetUrl, $token)
    {
        $expiryTime = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .button:hover { opacity: 0.9; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                    <p>Cabinet Management System</p>
                </div>
                <div class='content'>
                    <h2>Hello {$fullName},</h2>
                    <p>We received a request to reset your password for the Cabinet Management System account.</p>
                    
                    <p>To reset your password, please click the button below:</p>
                    <p style='text-align: center; color: #ffffff;'>
                        <a href='{$resetUrl}' class='button'>Reset My Password</a>
                    </p>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px; font-family: monospace;'>
                        {$resetUrl}
                    </p>
                    
                    <div class='warning'>
                        <strong>⚠️ Important Security Information:</strong>
                        <ul>
                            <li>This link will expire in <strong>1 hour</strong> (by {$expiryTime})</li>
                            <li>This link can only be used <strong>once</strong></li>
                            <li>If you didn't request this password reset, please ignore this email</li>
                            <li>Your password will remain unchanged until you complete the reset process</li>
                        </ul>
                    </div>
                    
                    <p>If you're having trouble with the button above, you can also manually enter this reset token:</p>
                    <p style='text-align: center; font-family: monospace; font-size: 18px; background: #f8f9fa; padding: 15px; border: 2px dashed #667eea; border-radius: 5px;'>
                        <strong>{$token}</strong>
                    </p>
                </div>
                <div class='footer'>
                    <p>This email was sent from the Cabinet Management System</p>
                    <p>If you didn't request this password reset, please contact your system administrator</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Test email configuration
     */
    public static function testEmailConfig()
    {
        $testData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => EmailConfig::$smtp_settings['from_email'], // Send to self for testing
            'username' => 'testuser',
            'password' => 'test123',
            'role' => 'encoder',
            'office' => 'Test Office',
            'division' => 'Test Division'
        ];

        return self::sendNewUserEmail($testData);
    }
}

/**
 * Quick setup function - call this to configure email settings
 */
function setupEmailConfig($fromEmail, $fromName, $replyTo = null)
{
    EmailConfig::$smtp_settings['from_email'] = $fromEmail;
    EmailConfig::$smtp_settings['username'] = $fromEmail;
    EmailConfig::$smtp_settings['from_name'] = $fromName;
    EmailConfig::$smtp_settings['reply_to'] = $replyTo ?: $fromEmail;

    return "Email configuration updated. Remember to set the SMTP password in email_config.php";
}
