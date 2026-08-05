<?php
/**
 * Endpoint formularza kontaktowego.
 * Odbiera dane POST z formularza (kontakt.html), waliduje je
 * i wysyła e-mail przez uwierzytelnione SMTP skrzynki biuro@protectivgroup.pl
 * (patrz smtp_mailer.php i config.php).
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/smtp_mailer.php';

// Tylko żądania POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
  exit;
}

// Honeypot — pole niewidoczne dla ludzi, wypełniane tylko przez boty
if (!empty($_POST['website'])) {
  echo json_encode(['ok' => true]); // udajemy sukces, nic nie wysyłamy
  exit;
}

function clean_field($value) {
  $value = trim((string) $value);
  // usuwamy znaki nowej linii, żeby zapobiec header injection
  $value = str_replace(["\r", "\n"], ' ', $value);
  return $value;
}

$name    = clean_field($_POST['name'] ?? '');
$phone   = clean_field($_POST['phone'] ?? '');
$email   = clean_field($_POST['email'] ?? '');
$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];
if ($name === '') {
  $errors[] = 'name';
}
if ($phone === '') {
  $errors[] = 'phone';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'email';
}

if (!empty($errors)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'validation', 'fields' => $errors]);
  exit;
}

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
  error_log('send.php: brak pliku config.php — skopiuj config.example.php i uzupełnij dane SMTP.');
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'server_not_configured']);
  exit;
}
$config = require $configPath;

$subject = 'Zapytanie ze strony – ' . $name;

$body  = "Nowe zapytanie ze strony Protectiv Solutions\n\n";
$body .= "Imię i nazwisko: {$name}\n";
$body .= "Telefon: {$phone}\n";
$body .= "E-mail: {$email}\n\n";
$body .= "Wiadomość:\n" . $message . "\n";

try {
  $mailer = new SmtpMailer(
    $config['smtp_host'],
    $config['smtp_port'],
    $config['smtp_user'],
    $config['smtp_pass']
  );
  $mailer->send(
    $config['smtp_user'],
    'Formularz Protectiv Solutions',
    $config['mail_to'],
    $subject,
    $body,
    $email
  );
  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  error_log('send.php: błąd wysyłki SMTP — ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'send_failed']);
}
