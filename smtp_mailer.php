<?php
/**
 * Minimalny klient SMTP (STARTTLS + AUTH LOGIN), bez zewnętrznych bibliotek.
 * Wysyła jedną wiadomość tekstową przez uwierzytelnioną skrzynkę.
 */

class SmtpMailer {
  private $host;
  private $port;
  private $username;
  private $password;
  private $timeout = 15;

  public function __construct($host, $port, $username, $password) {
    $this->host = $host;
    $this->port = $port;
    $this->username = $username;
    $this->password = $password;
  }

  /**
   * @throws Exception przy jakimkolwiek błędzie protokołu/połączenia
   */
  public function send($fromEmail, $fromName, $toEmail, $subject, $body, $replyTo = null) {
    $conn = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
    if (!$conn) {
      throw new Exception("Nie udało się połączyć z {$this->host}:{$this->port} ({$errstr})");
    }
    stream_set_timeout($conn, $this->timeout);

    try {
      $this->expect($conn, '220');
      $this->command($conn, "EHLO protectivgroup.pl", '250');

      $this->command($conn, "STARTTLS", '220');
      if (!stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        throw new Exception('Nie udało się nawiązać szyfrowanego połączenia TLS');
      }

      $this->command($conn, "EHLO protectivgroup.pl", '250');
      $this->command($conn, "AUTH LOGIN", '334');
      $this->command($conn, base64_encode($this->username), '334');
      $this->command($conn, base64_encode($this->password), '235');

      $this->command($conn, "MAIL FROM:<{$fromEmail}>", '250');
      $this->command($conn, "RCPT TO:<{$toEmail}>", '250');
      $this->command($conn, "DATA", '354');

      $headers = [];
      $headers[] = 'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>";
      $headers[] = "To: <{$toEmail}>";
      if ($replyTo) {
        $headers[] = "Reply-To: <{$replyTo}>";
      }
      $headers[] = 'Subject: ' . $this->encodeHeader($subject);
      $headers[] = 'MIME-Version: 1.0';
      $headers[] = 'Content-Type: text/plain; charset=UTF-8';
      $headers[] = 'Date: ' . date('r');
      $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@protectivgroup.pl>';

      $escapedBody = preg_replace('/^\./m', '..', $body);
      $data = implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.";

      $this->command($conn, $data, '250');
      $this->command($conn, "QUIT", '221');
    } finally {
      fclose($conn);
    }
  }

  private function encodeHeader($value) {
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
  }

  private function command($conn, $line, $expectedCode) {
    fwrite($conn, $line . "\r\n");
    $this->expect($conn, $expectedCode, $line);
  }

  private function expect($conn, $expectedCode, $context = '') {
    $response = $this->readResponse($conn);
    if (substr($response, 0, 3) !== $expectedCode) {
      $where = $context ? " po komendzie \"" . substr($context, 0, 40) . "\"" : '';
      throw new Exception("Serwer SMTP zwrócił nieoczekiwaną odpowiedź{$where}: {$response}");
    }
    return $response;
  }

  private function readResponse($conn) {
    $data = '';
    while (($line = fgets($conn, 515)) !== false) {
      $data .= $line;
      // ostatnia linia bloku odpowiedzi ma spację (nie myślnik) na 4. pozycji
      if (isset($line[3]) && $line[3] === ' ') {
        break;
      }
    }
    if ($data === '') {
      throw new Exception('Brak odpowiedzi z serwera SMTP (timeout lub zerwane połączenie)');
    }
    return $data;
  }
}
