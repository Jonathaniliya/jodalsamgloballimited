<?php
// Load SMTP credentials from mail-config.php located in the parent directory
$configPath = dirname(__DIR__) . '/mail-config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Mail configuration file not found']);
    exit;
}
$config = require $configPath;
$smtpHost = $config['smtp_host'] ?? '';
$smtpUser = $config['smtp_user'] ?? '';
$smtpPass = $config['smtp_pass'] ?? '';
$smtpPort = $config['smtp_port'] ?? 465;

// Set up PHPMailer
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $smtpHost;
$mail->SMTPAuth = true;
$mail->Username = $smtpUser;
$mail->Password = $smtpPass;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = $smtpPort;

// Other mail functionalities remain unchanged
$mail->setFrom('contact@jodalsamglobal.com');
$mail->addReplyTo('contact@jodalsamglobal.com');
$mail->addAddress('info@jodalsamglobal.com'); // Updated email address
// client confirmation email and footer email can stay the same as before
