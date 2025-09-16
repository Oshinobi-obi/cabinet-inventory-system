<?php
/**
 * Simplified SMTP class for Gmail
 */

namespace PHPMailer\PHPMailer;

class SMTP {
    public $smtp_conn;
    public $lastReply = '';
    
    public function connect($host, $port = 587, $timeout = 30) {
        $this->smtp_conn = fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$this->smtp_conn) {
            $this->lastReply = "Connection failed: $errstr ($errno)";
            return false;
        }
        
        $this->lastReply = fgets($this->smtp_conn, 512);
        return strpos($this->lastReply, '220') === 0;
    }
    
    public function startTLS() {
        fputs($this->smtp_conn, "STARTTLS\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        
        if (strpos($this->lastReply, '220') === 0) {
            stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            return true;
        }
        return false;
    }
    
    public function authenticate($username, $password) {
        fputs($this->smtp_conn, "AUTH LOGIN\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        
        fputs($this->smtp_conn, base64_encode($username) . "\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        
        fputs($this->smtp_conn, base64_encode($password) . "\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        
        return strpos($this->lastReply, '235') === 0;
    }
    
    public function mail($from) {
        fputs($this->smtp_conn, "MAIL FROM: <$from>\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        return strpos($this->lastReply, '250') === 0;
    }
    
    public function recipient($to) {
        fputs($this->smtp_conn, "RCPT TO: <$to>\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        return strpos($this->lastReply, '250') === 0;
    }
    
    public function data($message) {
        fputs($this->smtp_conn, "DATA\r\n");
        $this->lastReply = fgets($this->smtp_conn, 512);
        
        if (strpos($this->lastReply, '354') === 0) {
            fputs($this->smtp_conn, $message . "\r\n.\r\n");
            $this->lastReply = fgets($this->smtp_conn, 512);
            return strpos($this->lastReply, '250') === 0;
        }
        return false;
    }
    
    public function quit() {
        fputs($this->smtp_conn, "QUIT\r\n");
        fclose($this->smtp_conn);
        return true;
    }
    
    public function getLastReply() {
        return trim($this->lastReply);
    }
}