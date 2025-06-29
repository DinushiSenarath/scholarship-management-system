<?php
session_start();
include('../includes/db.php');

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token (add this to your session management)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Invalid request. Please try again.";
    } else {
        // Sanitize and validate inputs
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $current_password = trim($_POST['current_password']);
        
        // Validation
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Name must be at least 2 characters long.";
        }
        
        if (empty($phone) || !preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $phone)) {
            $errors[] = "Please enter a valid phone number.";
        }
        
        if (!empty($password) && strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        if (empty($errors)) {
            // Verify current password first
            $verify_query = "SELECT password FROM USER WHERE user_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("s", $user_id);
            $verify_stmt->execute();
            $result = $verify_stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            // Check if current password matches (assuming you're using password hashing)
            if (password_verify($current_password, $user_data['password']) || $current_password === $user_data['password']) {
                // Prepare update query
                if (!empty($password)) {
                    // Hash new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE USER SET name = ?, phone = ?, password = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssss", $name, $phone, $hashed_password, $user_id);
                } else {
                    // Update without changing password
                    $query = "UPDATE USER SET name = ?, phone = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sss", $name, $phone, $user_id);
                }
                
                if ($stmt->execute()) {
                    $success_msg = "Profile updated successfully!";
                    // Update session data if needed
                    $_SESSION['user_name'] = $name;
                } else {
                    $error_msg = "Failed to update profile. Please try again.";
                }
                $stmt->close();
            } else {
                $error_msg = "Current password is incorrect.";
            }
            $verify_stmt->close();
        } else {
            $error_msg = implode("<br>", $errors);
        }
    }
}

// Fetch current user data using prepared statement
$user_query = "SELECT name, phone, email FROM USER WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $user_id);
$user_stmt->execute();
$result = $user_stmt->get_result();
$user = $result->fetch_assoc();
$user_stmt->close();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - User Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .profile-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e8f4fd;
        }
        
        .profile-header h2 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .profile-header p {
            color: #6c757d;
            margin: 0.5rem 0 0 0;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #34495e;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e3f2fd;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #fafbfc;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            transform: translateY(-1px);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.2rem;
            color: #7f8c8d;
            user-select: none;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #3498db;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: 2px solid transparent;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            border: 2px solid transparent;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #7f8c8d 0%, #6c7b7d 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(127, 140, 141, 0.3);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d5f4e6 0%, #c8e6c9 100%);
            color: #1b5e20;
            border: 1px solid #a5d6a7;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border: 1px solid #ef9a9a;
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.1);
        }
        
        .form-note {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
        
        /* Loading animation for form submission */
        .btn:active {
            transform: scale(0.98);
        }
        
        /* Custom scrollbar for webkit browsers */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(52, 152, 219, 0.3);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(52, 152, 219, 0.5);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2>✏️ Edit Profile</h2>
            <p>Update your personal information</p>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                ❌ <?= $error_msg ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="editProfileForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" 
                       required 
                       minlength="2"
                       placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                       required
                       placeholder="Enter your phone number">
            </div>
            
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <div class="password-container">
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required
                           placeholder="Enter your current password">
                    <span class="password-toggle" onclick="togglePassword('current_password')">👁️</span>
                </div>
                <div class="form-note">Required to verify your identity</div>
            </div>
            
            <div class="form-group">
                <label for="password">New Password (Optional)</label>
                <div class="password-container">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           minlength="6"
                           placeholder="Leave blank to keep current password">
                    <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                </div>
                <div class="form-note">Minimum 6 characters. Leave blank to keep current password.</div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    💾 Save Changes
                </button>
                <a href="profile.php" class="btn btn-secondary">
                    ↩️ Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling;
            
            if (field.type === "password") {
                field.type = "text";
                toggle.textContent = "🙈";
            } else {
                field.type = "password";
                toggle.textContent = "👁️";
            }
        }
        
        // Form validation
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('password').value;
            
            if (name.length < 2) {
                alert('Name must be at least 2 characters long.');
                e.preventDefault();
                return;
            }
            
            if (!phone.match(/^[\+]?[0-9\-\(\)\s]+$/)) {
                alert('Please enter a valid phone number.');
                e.preventDefault();
                return;
            }
            
            if (!currentPassword) {
                alert('Current password is required.');
                e.preventDefault();
                return;
            }
            
            if (newPassword && newPassword.length < 6) {
                alert('New password must be at least 6 characters long.');
                e.preventDefault();
                return;
            }
            
            // Confirm before submitting
            if (!confirm('Are you sure you want to update your profile?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>