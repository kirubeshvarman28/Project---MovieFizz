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

        $this->sendCommand("MAIL FROM:<$from_email>");
        $this->sendCommand("RCPT TO:<$to>");
        $this->sendCommand("DATA");

        $header = "To: $to\r\n";
        $header .= "From: $from_name <$from_email>\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\r\n";
        $header .= "X-Mailer: SimpleSMTP/MovieFizz\r\n\r\n";

        $this->sendCommand($header . $message . "\r\n.");
        $this->sendCommand("QUIT");
        
        fclose($this->conn);
        return true;
    }

    private function sendCommand($cmd) {
        fwrite($this->conn, $cmd . "\r\n");
        return $this->getResponse();
    }

    private function getResponse() {
        $response = "";
        while ($line = fgets($this->conn, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        return $response;
    }
}
?>
