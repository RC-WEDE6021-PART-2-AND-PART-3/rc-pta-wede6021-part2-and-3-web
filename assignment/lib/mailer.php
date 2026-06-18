<?php
// Simple mail wrapper. Prefers PHPMailer when available and configured, otherwise falls back to PHP mail().
function send_mail(string $to, string $subject, string $body, array $opts = []): bool {
    $cfg = [];
    $cfgFile = __DIR__ . '/../mail_config.php';
    if (file_exists($cfgFile)) $cfg = include $cfgFile;

    // Try PHPMailer via Composer autoload
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!empty($cfg['use_smtp']) && file_exists($autoload)) {
        try {
            require_once $autoload;
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->setFrom($cfg['from_email'] ?? 'no-reply@localhost', $cfg['from_name'] ?? 'No Reply');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            $smtp = $cfg['smtp'] ?? [];
            $mail->isSMTP();
            $mail->Host = $smtp['host'] ?? 'localhost';
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'] ?? '';
            $mail->Password = $smtp['password'] ?? '';
            $mail->SMTPSecure = $smtp['secure'] ?? 'tls';
            $mail->Port = $smtp['port'] ?? 587;

            return (bool)$mail->send();
        } catch (Exception $e) {
            // fallback to mail()
        }
    }

    // Fallback to PHP mail
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $headers = 'From: ' . ($cfg['from_name'] ?? 'No Reply') . ' <' . ($cfg['from_email'] ?? 'no-reply@' . $host) . '>\r\n';
    $headers .= 'Reply-To: ' . ($cfg['from_email'] ?? 'no-reply@' . $host) . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8\r\n';
    return (bool)@mail($to, $subject, $body, $headers);
}
