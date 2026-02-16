<?php
// Load SMTP credentials from external configuration file
require_once '../mail-config.php';

// ... Other existing code

// Set From details - changed to new email
$mail->setFrom('contact@jodalsamglobal.com', 'Jodalsam Global Limited');

// Keep addAddress for admin
$mail->addAddress('info@jodalsamglobal.com');

// Add Reply-To header with customer's email
$mail->addReplyTo($email, $name);

// ... Other existing code

// Change Set From for the client confirmation email
$mail->setFrom('contact@jodalsamglobal.com');

// Update footer email
$footerEmail = 'contact@jodalsamglobal.com';

// SMTP settings
$smtp_host = 'smtp.hostinger.com';
$smtp_user = 'info@jodalsamglobal.com';
$smtp_pass = '(password)';
$smtp_port = 465;