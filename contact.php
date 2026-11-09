<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'POST only']);
  exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Missing config.php']);
  exit;
}
$config = require $configPath;

// Honeypot
$honeypot = trim((string)($_POST['companyWebsite'] ?? ''));
if ($honeypot !== '') {
  echo json_encode(['ok' => true, 'message' => 'Thanks']);
  exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$subject = trim((string)($_POST['subject'] ?? 'general'));
$message = trim((string)($_POST['message'] ?? ''));

if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($message) < 5) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Name, valid email, and message are required.']);
  exit;
}

$notify = $config['notify'] ?? 'hello@cillianscreatures.com';
$fromName = $config['from_name'] ?? "Cillian's Creatures";
$fromEmail = $config['from_email'] ?? 'noreply@cillianscreatures.com';

$safeSubject = str_replace(["\r", "\n"], ' ', $subject);
$mailSubject = 'Contact form: ' . ucfirst($safeSubject) . ' — ' . $name;

$body = "New contact form message\n\n"
  . "Name: {$name}\n"
  . "Email: {$email}\n"
  . "Subject: {$subject}\n\n"
  . "Message:\n{$message}\n";

$headers = "From: {$fromName} <{$fromEmail}>\r\n"
  . "Reply-To: {$email}\r\n"
  . "Content-Type: text/plain; charset=UTF-8";

$sent = @mail($notify, $mailSubject, $body, $headers);

// Optional confirmation to sender
$confirmBody = "Thanks for reaching out, {$name}.\n\nWe received your message and will get back to you soon.\n\n— Cillian's Creatures";
$confirmHeaders = "From: {$fromName} <{$fromEmail}>\r\nContent-Type: text/plain; charset=UTF-8";
@mail($email, "We received your message", $confirmBody, $confirmHeaders);

echo json_encode(['ok' => true, 'message' => 'Message sent']);
