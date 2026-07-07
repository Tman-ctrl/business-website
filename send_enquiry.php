<?php
/**
 * ═══════════════════════════════════════════════════
 * DASH DRONES – Contact & Quote Enquiry Handler
 * File: send_enquiry.php
 * Sends form submissions to rapetswathapelo.10@gmail.com
 * and a confirmation email back to the client.
 * ═══════════════════════════════════════════════════
 */

// ── CONFIGURATION ──────────────────────────────────
define('ADMIN_EMAIL', 'rapetswathapelo.10@gmail.com');
define('SENDER_NAME', 'Dash Drones Website');
define('SENDER_EMAIL', 'noreply@dashdrones.com');
define('COMPANY_PHONE', '078 677 9334');
define('COMPANY_SITE', 'www.dashdrones.com');
define('LOG_FILE', __DIR__ . '/quote_requests.log');
// ───────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

function sanitizeText(string $value, int $maxLength = 1000): string {
    $value = strip_tags(trim($value));
    $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value);
    return mb_substr($value, 0, $maxLength);
}

function sanitizeEmail(string $value): string {
    return strtolower(trim($value));
}

function sanitizePhone(string $value): string {
    return trim((string) preg_replace('/[^\d\s\+\-\(\)]/u', '', $value));
}

function validateName(string $value): bool {
    return preg_match('/^[\p{L}][\p{L}\s\'\-\.]{1,79}$/u', $value) === 1;
}

function validatePhone(string $value): bool {
    return preg_match('/^\+?(?:\d[\s.-]?){9,14}\d$/', $value) === 1;
}

function hasHeaderInjection(string $value): bool {
    return preg_match('/[\r\n]/', $value) === 1;
}

$fnameRaw = trim((string) ($_POST['fname'] ?? ''));
$lnameRaw = trim((string) ($_POST['lname'] ?? ''));
$emailRaw = trim((string) ($_POST['email'] ?? ''));
$phoneRaw = trim((string) ($_POST['phone'] ?? ''));
$typeRaw = trim((string) ($_POST['type'] ?? ''));
$cleanTypeRaw = trim((string) ($_POST['clean_type'] ?? ''));
$buildingRaw = trim((string) ($_POST['building'] ?? ''));

$fname = sanitizeText($fnameRaw, 80);
$lname = sanitizeText($lnameRaw, 80);
$email = sanitizeEmail($emailRaw);
$phone = sanitizePhone($phoneRaw);
$type = sanitizeText($typeRaw, 80);
$cleanType = sanitizeText($cleanTypeRaw, 80);
$building = sanitizeText($buildingRaw, 3000);

$full_name = trim("{$fname} {$lname}");
$errors = [];

if ($fnameRaw === '' || mb_strlen($fnameRaw) < 2) {
    $errors['fname'] = 'Please enter your first name.';
} elseif (!validateName($fnameRaw)) {
    $errors['fname'] = 'First name contains unsupported characters.';
}

if ($lnameRaw === '' || mb_strlen($lnameRaw) < 2) {
    $errors['lname'] = 'Please enter your last name.';
} elseif (!validateName($lnameRaw)) {
    $errors['lname'] = 'Last name contains unsupported characters.';
}

if ($emailRaw === '' || !filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email address is required.';
} elseif (hasHeaderInjection($emailRaw)) {
    $errors['email'] = 'Email address contains invalid characters.';
} else {
    $email = sanitizeEmail($emailRaw);
}

if ($phoneRaw === '' || !validatePhone($phoneRaw)) {
    $errors['phone'] = 'Please enter a valid phone number.';
}

$allowedTypes = [
    'Commercial Property Manager',
    'Residential / Estate Owner',
    'University / Institution',
    'Investor',
    'Other',
];

if ($typeRaw === '' || !in_array($typeRaw, $allowedTypes, true)) {
    $errors['type'] = 'Please select a valid enquiry type.';
}

$allowedCleanTypes = [
    'Standard Cleaning',
    'Deep Cleaning',
    'Facade Inspection',
];

if ($cleanTypeRaw === '' || !in_array($cleanTypeRaw, $allowedCleanTypes, true)) {
    $errors['clean_type'] = 'Please select a valid cleaning type.';
}

if ($buildingRaw === '' || mb_strlen($buildingRaw) < 10) {
    $errors['building'] = 'Please describe the property in at least 10 characters.';
} elseif (mb_strlen($buildingRaw) > 3000) {
    $errors['building'] = 'Please keep your message under 3000 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'errors' => $errors,
    ]);
    exit;
}

$ref = 'DDE-' . strtoupper(substr(md5($email . time()), 0, 7));
$submitted_at = date('l, d F Y \a\t H:i');

$admin_subject = "📋 New Quote Request [{$ref}] – {$full_name} ({$type})";
$admin_body = <<<TEXT
═══════════════════════════════════════════
  DASH DRONES – NEW QUOTE ENQUIRY
  Reference: {$ref}
═══════════════════════════════════════════

Submitted  : {$submitted_at}
Category   : {$type}
Service    : {$cleanType}

──────────────────────────────────────────
CLIENT DETAILS
──────────────────────────────────────────
Name       : {$full_name}
Email      : {$email}
Phone      : {$phone}

──────────────────────────────────────────
PROPERTY / BUILDING DESCRIPTION
──────────────────────────────────────────
{$building}

══════════════════════════════════════════
Reply to client: {$email}
Dash Drones | {$company_phone} | {$company_site}
══════════════════════════════════════════
TEXT;

$admin_body = str_replace(
    ['{$company_phone}', '{$company_site}'],
    [COMPANY_PHONE, COMPANY_SITE],
    $admin_body
);

$client_subject = "✅ Quote Request Received [{$ref}] – Dash Drones Window Cleaning";
$client_body = <<<TEXT
Hi {$full_name},

Thank you for reaching out to Dash Drones Window Cleaning!

We've received your enquiry and our team will review your building details.
We will get back to you within 24 hours with a tailored proposal.

YOUR REFERENCE NUMBER: {$ref}
────────────────────────────────────────────
Category   : {$type}
Service    : {$cleanType}
Submitted  : {$submitted_at}

WHAT HAPPENS NEXT
─────────────────
1. Our team reviews your building description.
2. We prepare a custom, fixed-price quote.
3. We contact you within 24 hours to confirm details.
4. You approve the quote and we schedule your clean.

CONTACT US
──────────
Phone   : 078 677 9334
Email   : dashdrones@gmail.com
Website : www.dashdrones.com

Thank you for choosing Dash Drones!
The Dash Drones Team
www.dashdrones.com | Reg No: 2025/87611/07
TEXT;

try {
    sendMail(ADMIN_EMAIL, $admin_subject, $admin_body, $email, $full_name);
    sendMail($email, $client_subject, $client_body, SENDER_EMAIL, SENDER_NAME);

    echo json_encode([
        'success' => true,
        'reference' => $ref,
        'message' => 'Enquiry submitted successfully.',
    ]);
} catch (RuntimeException $e) {
    appendQuoteLog($full_name, $email, $phone, $type, $cleanType, $building);

    echo json_encode([
        'success' => false,
        'reference' => $ref,
        'message' => 'Email delivery is unavailable from this server. Your email app will open so you can send the request manually.',
        'fallback' => true,
    ]);
}

function appendQuoteLog(string $fullName, string $email, string $phone, string $type, string $cleanType, string $building): void {
    $line = sprintf(
        "[%s] Name: %s | Email: %s | Phone: %s | Type: %s | Service: %s | Building: %s\n",
        date('c'),
        str_replace(["\r", "\n"], ' ', $fullName),
        str_replace(["\r", "\n"], ' ', $email),
        str_replace(["\r", "\n"], ' ', $phone),
        str_replace(["\r", "\n"], ' ', $type),
        str_replace(["\r", "\n"], ' ', $cleanType),
        str_replace(["\r", "\n"], ' ', $building)
    );

    @file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function sendMail(
    string $to,
    string $subject,
    string $body,
    string $replyEmail = '',
    string $replyName = ''
): void {
    $subject = mb_encode_mimeheader($subject, 'UTF-8', 'Q');

    $headers = "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "X-Mailer: Dash Drones PHP Mailer\r\n";

    if (!empty($replyEmail)) {
        if (hasHeaderInjection($replyEmail) || hasHeaderInjection($replyName)) {
            throw new RuntimeException('The provided contact details are invalid.');
        }

        $safeName = str_replace(['"', "\r", "\n"], '', $replyName);
        $headers .= "Reply-To: \"{$safeName}\" <{$replyEmail}>\r\n";
    }

    $sent = mail($to, $subject, $body, $headers);

    if (!$sent) {
        throw new RuntimeException('Email delivery failed. Please try again or call us on ' . COMPANY_PHONE . '.');
    }
}
?>
