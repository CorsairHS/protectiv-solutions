<?php
/**
 * Prosty endpoint formularza kontaktowego.
 * Odbiera dane POST z formularza (kontakt.html), waliduje je
 * i wysyła e-mail przez wbudowaną funkcję mail() (SMTP hostingu home.pl).
 */

header('Content-Type: application/json; charset=utf-8');

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

$to      = 'biuro@protectivgroup.pl';
$subject = '=?UTF-8?B?' . base64_encode('Zapytanie ze strony – ' . $name) . '?=';

$body  = "Nowe zapytanie ze strony Protectiv Solutions\n\n";
$body .= "Imię i nazwisko: {$name}\n";
$body .= "Telefon: {$phone}\n";
$body .= "E-mail: {$email}\n\n";
$body .= "Wiadomość:\n" . $message . "\n";

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: Formularz Protectiv Solutions <biuro@protectivgroup.pl>';
$headers[] = 'Reply-To: ' . $email;

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if ($sent) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'send_failed']);
}
