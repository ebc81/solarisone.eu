<?php

declare(strict_types=1);

function base64UrlDecode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

$signingKey = (string)(getenv('WAITLIST_SIGNING_KEY') ?: '');
if ($signingKey === '') {
    http_response_code(500);
    echo 'Signing key is missing.';
    exit;
}

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    http_response_code(400);
    echo 'Missing confirmation token.';
    exit;
}

$parts = explode('.', $token);
if (count($parts) !== 2) {
    http_response_code(400);
    echo 'Invalid confirmation token.';
    exit;
}

[$payloadEncoded, $signature] = $parts;
$expectedSignature = hash_hmac('sha256', $payloadEncoded, $signingKey);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(400);
    echo 'Invalid confirmation token signature.';
    exit;
}

$payloadJson = base64UrlDecode($payloadEncoded);
if ($payloadJson === false) {
    http_response_code(400);
    echo 'Invalid confirmation token payload.';
    exit;
}

$payload = json_decode($payloadJson, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo 'Invalid confirmation token data.';
    exit;
}

$email = strtolower(trim((string)($payload['email'] ?? '')));
$lang = ((string)($payload['lang'] ?? 'de') === 'en') ? 'en' : 'de';
$exp = (int)($payload['exp'] ?? 0);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Invalid email in token.';
    exit;
}

if ($exp <= 0 || time() > $exp) {
    http_response_code(410);
    echo 'Confirmation link has expired. Please sign up again.';
    exit;
}

$siteHost = $_SERVER['HTTP_HOST'] ?? 'www.solarisone.eu';
$recipient = 'info@ebctech.eu';
$subject = 'Bestätigte Wartelisten-Anmeldung: ' . $email;
$message = "Neue bestätigte Anmeldung für die SolarisOne-Warteliste\n\n"
    . "E-Mail: {$email}\n"
    . "Sprache: {$lang}\n"
    . "Bestätigt am: " . gmdate('Y-m-d H:i:s') . " UTC\n"
    . "Host: {$siteHost}\n";
$headers = [
    'From: SolarisOne <info@solarisone.eu>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8'
];

@mail($recipient, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, implode("\r\n", $headers), '-finfo@solarisone.eu');

if ($lang === 'en') {
    $title = 'Email confirmed';
    $body = 'Thank you. Your email is now confirmed and you are on the SolarisOne waiting list.';
} else {
    $title = 'E-Mail bestätigt';
    $body = 'Danke. Ihre E-Mail ist jetzt bestätigt und Sie sind auf der SolarisOne-Warteliste.';
}

?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { color-scheme: light dark; }
    body { margin:0; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#f7f9fc; color:#0f172a; }
    .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
    .card { max-width:560px; width:100%; background:#fff; border:1px solid rgba(15,23,42,.12); border-radius:16px; padding:1.5rem; box-shadow:0 10px 30px rgba(15,23,42,.08); text-align:center; }
    h1 { margin:0 0 .75rem; font-size:1.5rem; }
    p { margin:0; line-height:1.6; color:#334155; }
    a { display:inline-block; margin-top:1rem; color:#2563eb; text-decoration:none; }
  </style>
</head>
<body>
  <main class="wrap">
    <section class="card">
      <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></p>
      <a href="/">Zurück zur Website</a>
    </section>
  </main>
</body>
</html>
