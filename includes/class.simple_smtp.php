<?php
/**
 * Minimal SMTP Class for MovieFizz
 * A lightweight alternative to PHPMailer for basic SMTP sending.
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $crypto;
    private $timeout = 10;
    private $conn;

    public function __construct($host, $port, $user, $pass, $crypto = 'tls') {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->crypto = strtolower($crypto);
    }

    public function send($to, $from_name, $subject, $message, $from_email = '') {
        $from_email = $from_email ?: "noreply@" . $_SERVER['HTTP_HOST'];
        
        $remote = ($this->crypto == 'ssl' ? 'ssl://' : '') . $this->host;
        $this->conn = @fsockopen($remote, $this->port, $errno, $errstr, $this->timeout);
        
        if (!$this->conn) return false;

        $this->getResponse();
        $this->sendCommand("EHLO " . $_SERVER['HTTP_HOST']);
        
        if ($this->crypto == 'tls') {
            $this->sendCommand("STARTTLS");
            if (!@stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_ANY_CLIENT)) {
                return false;
            }
            $this->sendCommand("EHLO " . $_SERVER['HTTP_HOST']);
        }

        if (!empty($this->user)) {
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->user));
            $this->sendCommand(base64_encode($this->pass));
        }

        if (!$this->isSuccess($this->sendCommand("MAIL FROM:<$from_email>"))) return false;
        if (!$this->isSuccess($this->sendCommand("RCPT TO:<$to>"))) return false;
        if (!$this->isSuccess($this->sendCommand("DATA"))) return false;

        $header = "To: $to\r\n";
        $header .= "From: $from_name <$from_email>\r\n";
        $header .= "Reply-To: <$from_email>\r\n";
        $header .= "Date: " . date('r') . "\r\n";
        $header .= "Message-ID: <" . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)) . "@" . $this->host . ">\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\r\n";
        $header .= "Content-Transfer-Encoding: 8bit\r\n";
        $header .= "X-Priority: 3 (Normal)\r\n";
        $header .= "X-Mailer: MovieFizz-Mailer/v1.1\r\n\r\n";

        if (!$this->isSuccess($this->sendCommand($header . $message . "\r\n."))) return false;
        $this->sendCommand("QUIT");
        
        fclose($this->conn);
        return true;
    }

    private function isSuccess($resp) {
        $code = (int)substr($resp, 0, 3);
        return ($code >= 200 && $code < 400);
    }

    private function sendCommand($cmd) {
        if (!$this->conn) return "";
        fwrite($this->conn, $cmd . "\r\n");
        return $this->getResponse();
    }

    private function getResponse() {
        if (!$this->conn) return "";
        $response = "";
        while ($line = fgets($this->conn, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        return $response;
    }
}
?>
