<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? 'No Subject');
$message = trim($_POST['message'] ?? '');

$subject = str_replace(["\r", "\n"], '', $subject);
$name    = str_replace(["\r", "\n"], '', $name);
$phone   = str_replace(["\r", "\n"], '', $phone);

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid email']);
    exit;
}

$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safePhone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$smtpHost = 'smtp.hostinger.com';
$smtpUser = 'info@jodalsamglobal.com';
$smtpPass = '######';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // ===== EMAIL 1: TO ADMIN (Notification) =====
    $mail->setFrom('info@jodalsamglobal.com', 'Jodalsamglobal Limited');
    $mail->addAddress('info@jodalsamglobal.com');

    $adminBody = "
        <h2>New Website Enquiry</h2>
        <table style='border-collapse:collapse; width:100%; max-width:600px;'>
            <tr>
                <td style='padding:8px; border:1px solid #ddd; font-weight:bold; width:120px;'>Name</td>
                <td style='padding:8px; border:1px solid #ddd;'>$safeName</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd; font-weight:bold;'>Email</td>
                <td style='padding:8px; border:1px solid #ddd;'><a href='mailto:$safeEmail'>$safeEmail</a></td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd; font-weight:bold;'>Phone</td>
                <td style='padding:8px; border:1px solid #ddd;'>$safePhone</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd; font-weight:bold;'>Subject</td>
                <td style='padding:8px; border:1px solid #ddd;'>$safeSubject</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd; font-weight:bold;'>Message</td>
                <td style='padding:8px; border:1px solid #ddd;'>$safeMessage</td>
            </tr>
        </table>
    ";

    $mail->isHTML(true);
    $mail->Subject = 'New Website Enquiry: ' . ($subject ?: 'Website Enquiry');
    $mail->Body    = $adminBody;
    $mail->AltBody = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\nMessage:\n$message";

    $mail->send();

    // ===== EMAIL 2: TO CLIENT (Confirmation) =====
    // Clear previous recipients but keep SMTP settings
    $mail->clearAllRecipients();
    $mail->clearReplyTos();
    $mail->clearBCCs();

    // Add client as recipient
    if ($email) {
        $mail->addAddress($email, $safeName);
    }

    $clientBody = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:0 auto; color:#333;'>
            <div style='background:#0a1e3d; padding:30px; text-align:center;'>
                <h1 style='color:#d4a815; margin:0; font-size:24px;'>JODALSAM GLOBAL</h1>
            </div>
            <div style='padding:30px; background:#ffffff;'>
                <p>Dear $safeName,</p>
                <p>Thank you for reaching out to <strong>Jodalsamglobal Limited</strong>. We have received your message and a member of our team will get back to you within 24-48 hours.</p>
                <p><strong>Here is a summary of your enquiry:</strong></p>
                <div style='background:#f5f5f5; padding:15px; border-left:4px solid #d4a815; margin:15px 0;'>
                    <p style='margin:5px 0;'><strong>Subject:</strong> $safeSubject</p>
                    <p style='margin:5px 0;'><strong>Message:</strong> $safeMessage</p>
                </div>
                <p>If your matter is urgent, please do not hesitate to call us at <strong>08036010955</strong>.</p>
                <p>Warm regards,<br><strong>The Jodalsam Global Team</strong></p>
            </div>
            <div style='background:#0a1e3d; padding:20px; text-align:center;'>
                <p style='color:#888; font-size:12px; margin:0;'>Jodalsamglobal Limited | Plateau State, Nigeria</p>
                <p style='color:#888; font-size:12px; margin:5px 0 0;'>info@jodalsamglobal.com</p>
            </div>
        </div>
    ";

    $mail->Subject = 'Thank You for Your Enquiry';
    $mail->Body    = $clientBody;
    $mail->AltBody = "Dear $name,\n\nThank you for reaching out to Jodalsamglobal Limited. We have received your message and a member of our team will get back to you within 24-48 hours.\n\nSubject: $subject\n\nMessage:\n$message\n\nIf your matter is urgent, please call us at 08036010955.\n\nWarm regards,\nThe Jodalsam Global Team";

    $mail->send();

    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$mail->ErrorInfo]);
}
?>