<?php
/**
 * Admin login handler with two‑factor authentication (password + OTP).
 * Also handles logout (via GET ?logout=1) and resend OTP.
 */

require_once 'config.php';
require_once 'PHPMailer/PHPMailerAutoload.php';

// ---------- Handle logout ----------
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Only respond to POST requests (JSON API)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'error' => '', 'step' => 'password'];

$step = $_POST['step'] ?? 'password';

// ---------- Step 1: Verify password and send OTP ----------
if ($step === 'password') {
    $password = $_POST['password'] ?? '';
    if ($password === ADMIN_PASSWORD) {
        // Generate 6‑digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $_SESSION['admin_otp'] = $otp;
        $_SESSION['admin_otp_expiry'] = time() + 120; // 2 minutes

        // Send OTP email
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(ADMIN_EMAIL, 'Portfolio Admin');
        $mail->addAddress(ADMIN_EMAIL);
        $mail->Subject = 'Your Admin Login OTP';
        $mail->isHTML(true);
        $mail->Body = "
            <html>
            <head><style>body{font-family:Arial,sans-serif;}</style></head>
            <body>
                <h2>Admin Login Verification</h2>
                <p>You requested to log in to your portfolio admin panel.</p>
                <p>Your One-Time Password (OTP) is:</p>
                <h1 style='background:#f4f4f4;padding:15px;text-align:center;letter-spacing:5px;'>$otp</h1>
                <p>This code is valid for <strong>2 minutes</strong>.</p>
                <p>If you did not attempt to log in, please ignore this email.</p>
                <hr><small>Portfolio System</small>
            </body>
            </html>
        ";

        if ($mail->send()) {
            $response['success'] = true;
            $response['step'] = 'otp';
        } else {
            $response['error'] = 'Failed to send OTP email. Please try again.';
        }
    } else {
        $response['error'] = 'Invalid password.';
    }
}
// ---------- Step 2: Verify OTP ----------
elseif ($step === 'otp') {
    $otp = $_POST['otp'] ?? '';
    if (isset($_SESSION['admin_otp']) && isset($_SESSION['admin_otp_expiry']) && time() <= $_SESSION['admin_otp_expiry']) {
        if ($otp === $_SESSION['admin_otp']) {
            $_SESSION['admin_logged_in'] = true;
            unset($_SESSION['admin_otp'], $_SESSION['admin_otp_expiry']);
            $response['success'] = true;
            $response['step'] = 'complete';
        } else {
            $response['error'] = 'Invalid OTP.';
        }
    } else {
        $response['error'] = 'OTP has expired. Please start over.';
    }
}
// ---------- Resend OTP ----------
elseif ($step === 'resend') {
    // Only allow resend if the password was already verified but OTP hasn't been used yet
    if (isset($_SESSION['admin_otp'])) {
        // Generate new OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $_SESSION['admin_otp'] = $otp;
        $_SESSION['admin_otp_expiry'] = time() + 120; // reset expiry

        // Send new OTP email
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(ADMIN_EMAIL, 'Portfolio Admin');
        $mail->addAddress(ADMIN_EMAIL);
        $mail->Subject = 'Your New Admin Login OTP';
        $mail->isHTML(true);
        $mail->Body = "
            <html>
            <head><style>body{font-family:Arial,sans-serif;}</style></head>
            <body>
                <h2>Admin Login Verification (Resent)</h2>
                <p>You requested a new One-Time Password.</p>
                <p>Your new OTP is:</p>
                <h1 style='background:#f4f4f4;padding:15px;text-align:center;letter-spacing:5px;'>$otp</h1>
                <p>This code is valid for <strong>2 minutes</strong>.</p>
                <p>If you did not request this, please ignore this email.</p>
                <hr><small>Portfolio System</small>
            </body>
            </html>
        ";

        if ($mail->send()) {
            $response['success'] = true;
            $response['step'] = 'otp';
            $response['message'] = 'A new OTP has been sent to your email.';
        } else {
            $response['error'] = 'Failed to resend OTP. Please try again.';
        }
    } else {
        $response['error'] = 'No active OTP session. Please start over.';
    }
}

echo json_encode($response);
exit;
?>