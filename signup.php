<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const CONFIRM_TTL_SECONDS = 60 * 60 * 48;

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method-not-allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '', true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$email = strtolower(trim((string)($payload['email'] ?? '')));
$consentRaw = $payload['consent'] ?? false;
$consent = $consentRaw === true || $consentRaw === 'on' || $consentRaw === '1' || $consentRaw === 1;
$website = trim((string)($payload['website'] ?? ''));
$lang = ($payload['lang'] ?? 'de') === 'en' ? 'en' : 'de';

if ($website !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid-email']);
    exit;
}

if (!$consent) {
    http_response_code(400);
    echo json_encode(['error' => 'consent-required']);
    exit;
}

$signingKey = (string)(getenv('WAITLIST_SIGNING_KEY') ?: '');
if ($signingKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'missing-signing-key']);
    exit;
}

$siteHost = $_SERVER['HTTP_HOST'] ?? 'www.solarisone.eu';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$expiresAt = time() + CONFIRM_TTL_SECONDS;

$payloadData = [
    'email' => $email,
    'lang' => $lang,
    'exp' => $expiresAt
];

$payloadEncoded = base64UrlEncode(json_encode($payloadData, JSON_UNESCAPED_UNICODE));
$signature = hash_hmac('sha256', $payloadEncoded, $signingKey);
$token = $payloadEncoded . '.' . $signature;
$confirmLink = $scheme . '://' . $siteHost . '/confirm.php?token=' . urlencode($token);

if ($lang === 'en') {
    $userSubject = 'Confirm your SolarisOne waiting list signup';
    $userMessage = "Hello,\n\n"
        . "please confirm your email address to complete your SolarisOne waiting list signup:\n"
        . "{$confirmLink}\n\n"
        . "Only after confirmation you are added to the waiting list.\n\n"
        . "If you don't see the email, please check your spam/junk folder.\n\n"
        . "If you want to unsubscribe, simply send an email to info@ebctech.eu.\n\n"
        . "Best regards,\nSolarisOne";
} else {
    $userSubject = 'Bitte bestätigen Sie Ihre SolarisOne-Anmeldung';
    $userMessage = "Hallo,\n\n"
        . "bitte bestätigen Sie Ihre E-Mail-Adresse, um die Anmeldung zur SolarisOne-Warteliste abzuschließen:\n"
        . "{$confirmLink}\n\n"
        . "Erst nach der Bestätigung werden Sie in die Warteliste aufgenommen.\n\n"
        . "Wenn Sie die Nachricht nicht finden, schauen Sie bitte auch im Spam-Ordner nach.\n\n"
        . "Wenn Sie sich abmelden möchten, schreiben Sie einfach eine E-Mail an info@ebctech.eu.\n\n"
        . "Viele Grüße\nSolarisOne";
}

$userHeaders = [
    'From: SolarisOne <info@solarisone.eu>',
    'Reply-To: info@solarisone.eu',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8'
];

$mailParams = '-finfo@solarisone.eu';
$userMailOk = @mail($email, '=?UTF-8?B?' . base64_encode($userSubject) . '?=', $userMessage, implode("\r\n", $userHeaders), $mailParams);

if (!$userMailOk) {
    http_response_code(502);
    echo json_encode(['error' => 'confirmation-mail-failed']);
    exit;
}

echo json_encode(['ok' => true]);
