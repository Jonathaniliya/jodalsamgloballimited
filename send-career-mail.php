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

// Load mail configuration
$configPath = dirname(__DIR__) . '/mail-config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Mail configuration file not found']);
    exit;
}
$config = require $configPath;
if (!is_array($config) || !isset($config['smtp_host'], $config['smtp_user'], $config['smtp_pass'])) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Invalid mail configuration']);
    exit;
}
$smtpHost = $config['smtp_host'];
$smtpUser = $config['smtp_user'];
$smtpPass = $config['smtp_pass'];

// Get form data
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$department = trim($_POST['department'] ?? '');
$position = trim($_POST['position'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$qualification = trim($_POST['qualification'] ?? '');
$linkedIn = trim($_POST['linkedIn'] ?? '');
$coverLetter = trim($_POST['coverLetter'] ?? '');

// Sanitize single-line fields
$fullName = str_replace(["\r", "\n"], '', $fullName);
$phone = str_replace(["\r", "\n"], '', $phone);
$location = str_replace(["\r", "\n"], '', $location);
$department = str_replace(["\r", "\n"], '', $department);
$position = str_replace(["\r", "\n"], '', $position);
$linkedIn = str_replace(["\r", "\n"], '', $linkedIn);

// Validate required fields
if (empty($fullName) || empty($email) || empty($phone) || empty($location) || 
    empty($department) || empty($position) || empty($experience) || empty($qualification)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'All required fields must be filled']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid email address']);
    exit;
}

// Handle file upload
$cvPath = null;
$cvFileName = '';
if (isset($_FILES['cvUpload']) && $_FILES['cvUpload']['error'] === UPLOAD_ERR_OK) {
    $cvTmpPath = $_FILES['cvUpload']['tmp_name'];
    $cvFileName = $_FILES['cvUpload']['name'];
    $cvSize = $_FILES['cvUpload']['size'];
    $cvType = $_FILES['cvUpload']['type'];
    
    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($cvType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'CV must be a PDF or Word document']);
        exit;
    }
    
    // Validate file content using magic bytes (file signatures)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($finfo, $cvTmpPath);
    finfo_close($finfo);
    
    if (!in_array($detectedType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Invalid file format. CV must be a genuine PDF or Word document']);
        exit;
    }
    
    // Validate file size (5MB max)
    if ($cvSize > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'CV file size must be less than 5MB']);
        exit;
    }
    
    // Move file to temporary location
    $cvPath = sys_get_temp_dir() . '/' . uniqid('cv_', true) . '_' . basename($cvFileName);
    if (!move_uploaded_file($cvTmpPath, $cvPath)) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'Failed to process CV upload']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'CV/Resume is required']);
    exit;
}

// Handle cover letter file upload (optional)
$coverLetterPath = null;
$coverLetterFileName = '';
if (isset($_FILES['coverLetterUpload']) && $_FILES['coverLetterUpload']['error'] === UPLOAD_ERR_OK) {
    $coverLetterTmpPath = $_FILES['coverLetterUpload']['tmp_name'];
    $coverLetterFileName = $_FILES['coverLetterUpload']['name'];
    $coverLetterSize = $_FILES['coverLetterUpload']['size'];
    $coverLetterType = $_FILES['coverLetterUpload']['type'];
    
    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($coverLetterType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Cover letter must be a PDF or Word document']);
        exit;
    }
    
    // Validate file content using magic bytes (file signatures)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($finfo, $coverLetterTmpPath);
    finfo_close($finfo);
    
    if (!in_array($detectedType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Invalid file format. Cover letter must be a genuine PDF or Word document']);
        exit;
    }
    
    // Validate file size (5MB max)
    if ($coverLetterSize > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Cover letter file size must be less than 5MB']);
        exit;
    }
    
    // Move file to temporary location
    $coverLetterPath = sys_get_temp_dir() . '/' . uniqid('cover_', true) . '_' . basename($coverLetterFileName);
    if (!move_uploaded_file($coverLetterTmpPath, $coverLetterPath)) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'Failed to process cover letter upload']);
        exit;
    }
}

// Sanitize for HTML output
$safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safeLocation = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
$safeDepartment = htmlspecialchars($department, ENT_QUOTES, 'UTF-8');
$safePosition = htmlspecialchars($position, ENT_QUOTES, 'UTF-8');
$safeExperience = htmlspecialchars($experience, ENT_QUOTES, 'UTF-8');
$safeQualification = htmlspecialchars($qualification, ENT_QUOTES, 'UTF-8');
$safeLinkedIn = htmlspecialchars($linkedIn, ENT_QUOTES, 'UTF-8');

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    // ===== EMAIL 1: TO HR (Job Application) =====
    $mail->setFrom('no-reply@jodalsamglobal.com', 'Jodalsam Global Careers');
    $mail->addReplyTo($safeEmail, $safeName);
    $mail->addAddress('hr@jodalsamglobal.com');

    // Attach CV
    if ($cvPath && file_exists($cvPath)) {
        $mail->addAttachment($cvPath, $cvFileName);
    }
    
    // Attach cover letter if uploaded
    if ($coverLetterPath && file_exists($coverLetterPath)) {
        $mail->addAttachment($coverLetterPath, $coverLetterFileName);
    }

    $hrBody = "
        <div style='font-family:Arial,sans-serif; max-width:700px; margin:0 auto; color:#333;'>
            <div style='background:#0a1e3d; padding:30px; text-align:center;'>
                <h1 style='color:#d4a815; margin:0; font-size:24px;'>NEW JOB APPLICATION</h1>
            </div>
            <div style='padding:30px; background:#ffffff;'>
                <h2 style='color:#0a1e3d; margin-top:0;'>Application for: $safePosition</h2>
                <p>A new job application has been submitted through the careers portal.</p>
                
                <h3 style='color:#0a1e3d; margin-top:30px; border-bottom:2px solid #d4a815; padding-bottom:8px;'>Personal Information</h3>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold; width:180px;'>Full Name</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>$safeName</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Email</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'><a href='mailto:$safeEmail'>$safeEmail</a></td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Phone</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>$safePhone</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Location/State</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>$safeLocation</td>
                    </tr>
                </table>
                
                <h3 style='color:#0a1e3d; margin-top:30px; border-bottom:2px solid #d4a815; padding-bottom:8px;'>Professional Information</h3>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold; width:180px;'>Department</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>$safeDepartment</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Position Applied For</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'><strong style='color:#d4a815;'>$safePosition</strong></td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Years of Experience</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>$safeExperience</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Highest Qualification</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>$safeQualification</td>
                    </tr>
                </table>
                
                <h3 style='color:#0a1e3d; margin-top:30px; border-bottom:2px solid #d4a815; padding-bottom:8px;'>Additional Information</h3>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold; width:180px;'>CV/Resume</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>Attached: $cvFileName</td>
                    </tr>
                    " . ($safeLinkedIn ? "
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>LinkedIn Profile</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'><a href='$safeLinkedIn' target='_blank'>$safeLinkedIn</a></td>
                    </tr>
                    " : "") . "
                    " . ($coverLetterFileName ? "
                    <tr>
                        <td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold;'>Cover Letter</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>Attached: $coverLetterFileName</td>
                    </tr>
                    " : "") . "
                </table>
                
                <p style='margin-top:30px; padding:15px; background:#f5f5f5; border-left:4px solid #d4a815;'>
                    <strong>Action Required:</strong> Please review this application and contact the candidate if their qualifications match our requirements.
                </p>
            </div>
            <div style='background:#0a1e3d; padding:20px; text-align:center;'>
                <p style='color:#888; font-size:12px; margin:0;'>Jodalsam Global Limited | HR Department</p>
            </div>
        </div>
    ";

    $mail->isHTML(true);
    $mail->Subject = 'New Job Application — ' . $safePosition . ' — ' . $safeName;
    $mail->Body = $hrBody;
    $mail->AltBody = "New Job Application\n\nPosition: $position\n\nPersonal Information:\nName: $fullName\nEmail: $email\nPhone: $phone\nLocation: $location\n\nProfessional Information:\nDepartment: $department\nPosition: $position\nExperience: $experience\nQualification: $qualification\n\nLinkedIn: $linkedIn\n\nCV/Resume attached: $cvFileName" . ($coverLetterFileName ? "\nCover Letter attached: $coverLetterFileName" : "");

    $mail->send();

    // ===== EMAIL 2: TO APPLICANT (Confirmation - ONE-WAY ONLY) =====
    $mail->clearAllRecipients();
    $mail->clearReplyTos();
    $mail->clearBCCs();
    $mail->clearAttachments();

    $mail->setFrom('no-reply@jodalsamglobal.com', 'Jodalsam Global Limited');
    $mail->addReplyTo('no-reply@jodalsamglobal.com', 'Do Not Reply'); // ONE-WAY - cannot reply
    $mail->addAddress($email, $safeName);

    $applicantBody = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:0 auto; color:#333;'>
            <div style='background:#0a1e3d; padding:30px; text-align:center;'>
                <h1 style='color:#d4a815; margin:0; font-size:24px;'>JODALSAM GLOBAL</h1>
            </div>
            <div style='padding:30px; background:#ffffff;'>
                <p>Dear $safeName,</p>
                <p>Thank you for applying for the <strong>$safePosition</strong> position at <strong>Jodalsam Global Limited</strong>.</p>
                <p>We have received your application and our HR team will review it shortly. If your qualifications match our requirements, we will contact you for the next steps.</p>
                <div style='background:#f5f5f5; padding:20px; border-left:4px solid #d4a815; margin:25px 0;'>
                    <p style='margin:0; color:#0a1e3d;'><strong>Application Summary:</strong></p>
                    <p style='margin:8px 0 0 0;'>Position: <strong>$safePosition</strong></p>
                    <p style='margin:8px 0 0 0;'>Department: $safeDepartment</p>
                    <p style='margin:8px 0 0 0;'>Date Submitted: " . date('F j, Y') . "</p>
                </div>
                <p><strong>What happens next?</strong></p>
                <ul style='color:#666; line-height:1.8;'>
                    <li>Our HR team will carefully review your application and CV</li>
                    <li>If your profile matches our requirements, we will contact you via email or phone</li>
                    <li>The review process typically takes 1-2 weeks</li>
                </ul>
                <p style='color:#666; font-size:14px; margin-top:30px;'><em>Please note: This is an automated confirmation email. Do not reply to this message. If you have any questions, please visit our website or call us at +2348036010955.</em></p>
                <p style='margin-top:30px;'>Best Regards,<br><strong>Jodalsam Global Limited</strong><br>HR Department</p>
            </div>
            <div style='background:#0a1e3d; padding:20px; text-align:center;'>
                <p style='color:#888; font-size:12px; margin:0;'>Jodalsam Global Limited | Plateau State, Nigeria</p>
                <p style='color:#888; font-size:12px; margin:5px 0 0;'>Building the Future, Powering the Nation</p>
            </div>
        </div>
    ";

    $mail->isHTML(true);
    $mail->Subject = 'Application Received — Jodalsam Global Limited';
    $mail->Body = $applicantBody;
    $mail->AltBody = "Dear $fullName,\n\nThank you for applying for the $position position at Jodalsam Global Limited.\n\nWe have received your application and our HR team will review it shortly. If your qualifications match our requirements, we will contact you for the next steps.\n\nApplication Summary:\nPosition: $position\nDepartment: $department\nDate Submitted: " . date('F j, Y') . "\n\nWhat happens next?\n- Our HR team will carefully review your application and CV\n- If your profile matches our requirements, we will contact you via email or phone\n- The review process typically takes 1-2 weeks\n\nPlease note: This is an automated confirmation email. Do not reply to this message. If you have any questions, please visit our website or call us at +2348036010955.\n\nBest Regards,\nJodalsam Global Limited\nHR Department";

    $mail->send();

    // Clean up uploaded files
    if ($cvPath && file_exists($cvPath)) {
        unlink($cvPath);
    }
    if ($coverLetterPath && file_exists($coverLetterPath)) {
        unlink($coverLetterPath);
    }

    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    // Clean up uploaded files on error
    if ($cvPath && file_exists($cvPath)) {
        unlink($cvPath);
    }
    if ($coverLetterPath && file_exists($coverLetterPath)) {
        unlink($coverLetterPath);
    }
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$mail->ErrorInfo]);
}
?>
