<?php
/**
 * Simplified PHPMailer for Gmail SMTP
 * Based on PHPMailer library
 */

namespace PHPMailer\PHPMailer;

class PHPMailer {
    public $Host = '';
    public $Port = 587;
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = 'tls';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $CharSet = 'UTF-8';
    
    private $to = [];
    private $replyTo = [];
    
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';
    
    public function __construct($exceptions = false) {
        // Constructor
    }
    
    public function isSMTP() {
        // Enable SMTP
        return true;
    }
    
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }
    
    public function addAddress($address, $name = '') {
        $this->to[] = ['address' => $address, 'name' => $name];
        return true;
    }
    
    public function addReplyTo($address, $name = '') {
        $this->replyTo[] = ['address' => $address, 'name' => $name];
        return true;
    }
    
    public function isHTML($isHTML = true) {
        // Set email format
        return true;
    }
    
    public function send() {
        // Use basic mail() for non-Gmail addresses, SMTP for Gmail
        if (strpos($this->From, '@gmail.com') === false) {
            return $this->sendViaMail();
        }
        
        // Create SMTP connection for Gmail
        $smtp = new SMTP();
        
        try {
            // Say EHLO first
            if (!$smtp->connect($this->Host, $this->Port, 30)) {
                throw new Exception('SMTP connect failed: ' . $smtp->getLastReply());
            }
            
            // Send EHLO
            fputs($smtp->smtp_conn, "EHLO localhost\r\n");
            $reply = fgets($smtp->smtp_conn, 512);
            
            // Start TLS encryption
            if (!$smtp->startTLS()) {
                throw new Exception('SMTP TLS failed: ' . $smtp->getLastReply());
            }
            
            // Send EHLO again after TLS
            fputs($smtp->smtp_conn, "EHLO localhost\r\n");
            $reply = fgets($smtp->smtp_conn, 512);
            
            // Authenticate
            if (!$smtp->authenticate($this->Username, $this->Password)) {
                throw new Exception('SMTP authentication failed: ' . $smtp->getLastReply());
            }
            
            // Set sender
            if (!$smtp->mail($this->From)) {
                throw new Exception('SMTP mail from failed: ' . $smtp->getLastReply());
            }
            
            // Set recipient
            foreach ($this->to as $recipient) {
                if (!$smtp->recipient($recipient['address'])) {
                    throw new Exception('SMTP recipient failed: ' . $smtp->getLastReply());
                }
            }
            
            // Send data
            $data = $this->createMessage();
            if (!$smtp->data($data)) {
                throw new Exception('SMTP data failed: ' . $smtp->getLastReply());
            }
            
            $smtp->quit();
            return true;
            
        } catch (Exception $e) {
            throw new Exception('Email sending failed: ' . $e->getMessage());
        }
    }
    
    private function sendViaMail() {
        // Fallback to basic mail() function for non-Gmail
        $headers = "From: {$this->FromName} <{$this->From}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset={$this->CharSet}\r\n";
        
        return mail($this->to[0]['address'], $this->Subject, $this->Body, $headers);
    }
    
    private function createMessage() {
        $message = "From: {$this->FromName} <{$this->From}>\r\n";
        $message .= "To: " . $this->to[0]['address'] . "\r\n";
        if (!empty($this->replyTo)) {
            $message .= "Reply-To: " . $this->replyTo[0]['address'] . "\r\n";
        }
        $message .= "Subject: {$this->Subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset={$this->CharSet}\r\n";
        $message .= "\r\n";
        $message .= $this->Body;
        
        return $message;
    }
}