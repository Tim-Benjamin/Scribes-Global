<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($conn);
        break;
    case 'register':
        handleRegister($conn);
        break;
    case 'forgot_password':
        handleForgotPassword($conn);
        break;
    case 'reset_password':
        handleResetPassword($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleLogin($conn) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $errors = [];
    
    // Validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }
    
    // Check user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'errors' => ['email' => 'Invalid email or password']]);
        return;
    }
    
    // Check email verification
    if (!$user['email_verified']) {
        echo json_encode(['success' => false, 'message' => 'Please verify your email first. Check your inbox for the verification link.']);
        return;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    // Handle remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME);
        
        $tokenStmt = $conn->prepare("
            INSERT INTO user_sessions (user_id, token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $tokenStmt->execute([
            $user['id'],
            $token,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expires
        ]);
        
        setcookie('remember_token', $token, time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
    }
    
    // Log activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_log (user_id, action, ip_address, user_agent)
        VALUES (?, 'login', ?, ?)
    ");
    $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => SITE_URL . '/pages/dashboard'
    ]);
}

function handleRegister($conn) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required';
    }
    
    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors['email'] = 'Email already registered';
        }
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'You must accept the terms and conditions';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    
    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    
    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, email, phone, password, verification_token, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    try {
        $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword, $verificationToken]);
        $userId = $conn->lastInsertId();
        
        // Send verification email
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();
        $emailSent = $mailer->sendVerificationEmail($email, $firstName, $verificationToken);
        
        if (!$emailSent) {
            error_log("Failed to send verification email to: {$email}");
        }
        
        // Log activity
        $logStmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, ip_address, user_agent)
            VALUES (?, 'register', ?, ?)
        ");
        $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $_SESSION['success_message'] = 'Registration successful! Please check your email to verify your account.';
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Please check your email to verify your account.',
            'redirect' => SITE_URL . '/auth/login'
        ]);
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
}

function handleForgotPassword($conn) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'errors' => ['email' => 'Valid email is required']]);
        return;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);
        
        // Update user
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET reset_token = ?, reset_token_expiry = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$resetToken, $expiry, $user['id']]);
        
        // Send reset email
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();
        $emailSent = $mailer->sendPasswordResetEmail($email, $user['first_name'], $resetToken);
        
        if (!$emailSent) {
            error_log("Failed to send password reset email to: {$email}");
        }
    }
    
    // Always return success to prevent email enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account exists with that email, a password reset link has been sent.'
    ]);
}

function handleResetPassword($conn) {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }
    
    // Verify token
    $stmt = $conn->prepare("
        SELECT id FROM users 
        WHERE reset_token = ? AND reset_token_expiry > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        return;
    }
    
    // Update password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $updateStmt = $conn->prepare("
        UPDATE users 
        SET password = ?, reset_token = NULL, reset_token_expiry = NULL
        WHERE id = ?
    ");
    $updateStmt->execute([$hashedPassword, $user['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successful! You can now login with your new password.',
        'redirect' => SITE_URL . '/auth/login'
    ]);
}
?>