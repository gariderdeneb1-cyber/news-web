<?php
/**
 * Newsletter signup handler. Plain POST + redirect (progressive
 * enhancement) so the form works with JavaScript disabled.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$redirectTo = (string) ($_POST['redirect_to'] ?? '/');
if (!str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
    $redirectTo = url_home(); // guard against open redirects
}
$sep = str_contains($redirectTo, '?') ? '&' : '?';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    redirect($redirectTo);
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email) VALUES (:email) ON DUPLICATE KEY UPDATE email = email');
    $stmt->execute([':email' => $email]);
    redirect($redirectTo . $sep . 'subscribed=1');
}

redirect($redirectTo . $sep . 'subscribed=0');
