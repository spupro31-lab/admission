<?php
/**
 * SCT College Admission Portal - Mailer Helper with PHPMailer Integration
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../libs/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Get email configuration array.
 */
function get_email_config()
{
    $config_file = __DIR__ . '/email_config.php';
    if (file_exists($config_file)) {
        $cfg = include $config_file;
        if (is_array($cfg)) {
            return $cfg;
        }
    }

    return [
        'smtp_enabled'  => false,
        'smtp_host'     => 'smtp.gmail.com',
        'smtp_port'     => 587,
        'smtp_secure'   => 'tls',
        'smtp_username' => 'audit29122006@gmail.com',
        'smtp_password' => 'gydw iwnc sjzi fsiu',
        'from_email'    => 'admissions@statecollege.edu',
        'from_name'     => 'State College of Technology - Admissions'
    ];
}

/**
 * Send email using PHPMailer (SMTP or system fallback) with logging.
 */
function send_app_mail($to, string $to_name, $subject, $html_body, $alt_body = '')
{
    $config = get_email_config();

    // 1. Create email log directory
    $log_dir = __DIR__ . '/../uploads/mail_logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $safe_email = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $to);
    $log_filename = "mail_{$timestamp}_{$safe_email}.html";
    $log_filepath = $log_dir . '/' . $log_filename;

    $log_content = "<!-- EMAIL SENT TO: " . htmlspecialchars($to) . " | TIME: " . date('Y-m-d H:i:s') . " | SUBJECT: " . htmlspecialchars($subject) . " -->\n" . $html_body;
    @file_put_contents($log_filepath, $log_content);

    // Master log file
    $master_log = $log_dir . '/mail_history.log';
    $log_line = sprintf(
        "[%s] TO: %s <%s> | SUBJECT: %s | FILE: %s\n",
        date('Y-m-d H:i:s'),
        $to_name,
        $to,
        $subject,
        $log_filename
    );
    @file_put_contents($master_log, $log_line, FILE_APPEND);

    // 2. Try sending via PHPMailer SMTP if configured
    $mail = new PHPMailer(true);

    try {
        if (!empty($config['smtp_username']) && !empty($config['smtp_password'])) {
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'] ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = ($config['smtp_secure'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = intval($config['smtp_port'] ?: 587);
            $mail->Timeout    = 15;

            // Sender
            $from_address = !empty($config['from_email']) ? $config['from_email'] : $config['smtp_username'];
            $from_name    = !empty($config['from_name']) ? $config['from_name'] : 'State College of Technology';
            $mail->setFrom($from_address, $from_name);
            $mail->addReplyTo($from_address, $from_name);

            // Recipient
            $mail->addAddress($to, $to_name);

            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            $mail->AltBody = $alt_body ?: strip_tags($html_body);

            $mail->send();

            return [
                'success'   => true,
                'live_sent' => true,
                'message'   => 'Email successfully delivered to ' . $to . ' via SMTP (' . $config['smtp_host'] . ').',
                'log_file'  => $log_filename
            ];
        } else {
            // SMTP username or password not yet provided
            return [
                'success'       => true,
                'live_sent'     => false,
                'smtp_missing'  => true,
                'message'       => 'SMTP credentials not configured yet. Email logged to disk.',
                'log_file'      => $log_filename
            ];
        }
    } catch (Exception $e) {
        $error_detail = $mail->ErrorInfo ?: $e->getMessage();
        return [
            'success'   => false,
            'live_sent' => false,
            'message'   => 'SMTP Error: ' . $error_detail,
            'log_file'  => $log_filename
        ];
    }
}

/**
 * Generate a clean, memorable, secure password.
 */
function generate_student_password($length = 8)
{
    $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghijkmnpqrstuvwxyz';
    $numbers = '23456789';
    $special = '@#$';

    $pwd = $letters[random_int(0, strlen($letters) - 1)] .
           $lower[random_int(0, strlen($lower) - 1)] .
           $lower[random_int(0, strlen($lower) - 1)] .
           $special[random_int(0, strlen($special) - 1)] .
           $numbers[random_int(0, strlen($numbers) - 1)] .
           $numbers[random_int(0, strlen($numbers) - 1)] .
           $numbers[random_int(0, strlen($numbers) - 1)] .
           $numbers[random_int(0, strlen($numbers) - 1)];

    return str_shuffle($pwd);
}

/**
 * Format and send the Admission Inquiry & Credentials Email.
 */
function send_inquiry_credentials_email($name, $email, $mobile, $course_name, $generated_password, $login_url = '')
{
    if (empty($login_url)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $login_url = $protocol . $host . rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/\\') . '/login.php?role=student&email=' . urlencode($email);
    }

    $subject = "Admission Inquiry Received - Your Student Login Credentials | SCT College";

    $html_body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b; }
            .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
            .header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff; padding: 32px 24px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
            .header p { margin: 8px 0 0; opacity: 0.9; font-size: 14px; }
            .content { padding: 32px 24px; }
            .greeting { font-size: 18px; font-weight: 600; margin-bottom: 12px; }
            .text { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 20px; }
            .credentials-box { background-color: #f1f5f9; border-left: 4px solid #2563eb; border-radius: 8px; padding: 18px 20px; margin: 24px 0; }
            .cred-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #cbd5e1; font-size: 14px; }
            .cred-row:last-child { border-bottom: none; }
            .cred-label { font-weight: 600; color: #334155; }
            .cred-value { font-family: "Courier New", Courier, monospace; font-weight: 700; color: #0f172a; }
            .cred-password { font-size: 16px; color: #2563eb; background: #e0e7ff; padding: 2px 8px; border-radius: 4px; }
            .btn-cta { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; text-align: center; margin: 16px 0; }
            .steps-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin: 24px 0; }
            .steps-box h3 { margin: 0 0 10px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; }
            .steps-box ol { margin: 0; padding-left: 20px; font-size: 13px; color: #475569; line-height: 1.7; }
            .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 24px; text-align: center; font-size: 12px; color: #94a3b8; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h1>State College of Technology</h1>
                <p>Official Admission Portal - Academic Year 2026-27</p>
            </div>
            <div class="content">
                <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
                <div class="text">
                    Thank you for your interest in State College of Technology! Your admission inquiry has been received and your official student account has been created.
                </div>

                <div class="credentials-box">
                    <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 10px; font-size: 15px;">
                        Your Admission Portal Login Credentials
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Applicant Name:</span>
                        <span class="cred-value">' .  htmlspecialchars($name) . '</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Selected Course:</span>
                        <span class="cred-value">' . htmlspecialchars($course_name) . '</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Mobile Number:</span>
                        <span class="cred-value">' . htmlspecialchars($mobile) . '</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Login Email ID:</span>
                        <span class="cred-value">' . htmlspecialchars($email) . '</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Generated Password:</span>
                        <span class="cred-value cred-password">' . htmlspecialchars($generated_password) . '</span>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="' . htmlspecialchars($login_url) . '" class="btn-cta">
                        Log In & Complete Admission Form &rarr;
                    </a>
                </div>

                <div class="steps-box">
                    <h3>Application Next Steps</h3>
                    <ol>
                        <li><strong>Step 1 (Inquiry Complete):</strong> Your inquiry details and account have been saved.</li>
                        <li><strong>Step 2 (Student Login):</strong> Click the login button above using your email and generated password.</li>
                        <li><strong>Step 3 (Fill Admission Form):</strong> Your personal, mobile, and course details will be automatically loaded from your inquiry. Complete your academic marks and address.</li>
                        <li><strong>Step 4 (Upload & Track):</strong> Upload required certificates (marksheets, ID proof) and track your verification live.</li>
                    </ol>
                </div>

                <div class="text" style="font-size: 13px; color: #64748b;">
                    If you did not request this inquiry, please contact our admission cell at <a href="mailto:admissions@statecollege.edu" style="color: #2563eb;">admissions@statecollege.edu</a>.
                </div>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' State College of Technology. All Rights Reserved.<br>
                Admissions Cell, SCT Campus | Helpline: +91 98765 43210
            </div>
        </div>
    </body>
    </html>
    ';

    return send_app_mail($email, $name, $subject, $html_body);
}
