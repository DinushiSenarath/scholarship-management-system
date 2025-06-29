<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user information
$result = $conn->query("SELECT * FROM USER WHERE user_id = '$user_id'");
$user = $result->fetch_assoc();

// Get role-specific information
$role_info = null;
if ($role === 'student') {
    $role_result = $conn->query("SELECT * FROM STUDENT WHERE user_id = '$user_id'");
    $role_info = $role_result->fetch_assoc();
} elseif ($role === 'coordinator') {
    $role_result = $conn->query("SELECT * FROM COORDINATOR WHERE user_id = '$user_id'");
    $role_info = $role_result->fetch_assoc();
} elseif ($role === 'provider') {
    $role_result = $conn->query("SELECT * FROM SCHOLARSHIP_PROVIDER WHERE user_id = '$user_id'");
    $role_info = $role_result->fetch_assoc();
}

$back_link = ($role === 'student') ? "student.php" :
             (($role === 'coordinator') ? "coordinator.php" : "provider.php");

// Get profile picture path (if exists)
$profile_picture = isset($user['profile_picture']) ? $user['profile_picture'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Scholarship Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --success-color: #10b981;
            --success-hover: #059669;
            --danger-color: #ef4444;
            --danger-hover: #dc2626;
            --warning-color: #f59e0b;
            --warning-hover: #d97706;
            --info-color: #3b82f6;
            --info-hover: #2563eb;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            padding: 20px;
        }

        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Header */
        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-info h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 5px;
        }

        .profile-info .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-student {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .role-coordinator {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .role-provider {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Profile Cards */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-100);
        }

        .card-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-group:last-child {
            margin-bottom: 0;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-label i {
            font-size: 14px;
            color: var(--gray-500);
        }

        .info-value {
            font-size: 16px;
            color: var(--gray-800);
            padding-left: 22px;
            font-weight: 500;
        }

        .info-value.email {
            color: var(--primary-color);
            text-decoration: none;
        }

        .info-value.email:hover {
            text-decoration: underline;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: var(--success-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-secondary {
            background: var(--gray-600);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--gray-700);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3);
        }

        /* Stats Cards (for role-specific info) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-container {
                padding: 0 10px;
            }

            .header-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-header {
                padding: 20px;
            }

            .profile-card {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                justify-content: center;
            }

            .profile-info h1 {
                font-size: 1.5rem;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Hover effects */
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        /* Additional styling for better visual hierarchy */
        .divider {
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            border-radius: 1px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="profile-avatar">
                        <?php if ($profile_picture && file_exists($profile_picture)): ?>
                            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture">
                        <?php else: ?>
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1><?= htmlspecialchars($user['name']) ?></h1>
                        <span class="role-badge role-<?= $role ?>">
                            <i class="fas fa-<?= $role === 'student' ? 'user-graduate' : ($role === 'coordinator' ? 'user-tie' : 'building') ?>"></i>
                            <?= ucfirst($role) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role-specific Stats (if applicable) -->
        <?php if ($role_info): ?>
            <div class="stats-grid">
                <?php if ($role === 'student'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?= htmlspecialchars($role_info['gpa'] ?? 'N/A') ?></div>
                        <div class="stat-label">Current GPA</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?= htmlspecialchars($role_info['family_size'] ?? 'N/A') ?></div>
                        <div class="stat-label">Family Size</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value">Rs. <?= number_format($role_info['income'] ?? 0, 0) ?></div>
                        <div class="stat-label">Monthly Income</div>
                    </div>
                <?php elseif ($role === 'coordinator'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="stat-value"><?= htmlspecialchars($role_info['coordinator_id']) ?></div>
                        <div class="stat-label">Coordinator ID</div>
                    </div>
                <?php elseif ($role === 'provider'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="stat-value"><?= htmlspecialchars($role_info['provider_id']) ?></div>
                        <div class="stat-label">Provider ID</div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="profile-grid">
            <!-- Personal Information -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <h3>Personal Information</h3>
                </div>
                
                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-signature"></i>
                        Full Name
                    </div>
                    <div class="info-value"><?= htmlspecialchars($user['name']) ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </div>
                    <div class="info-value">
                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="info-value email">
                            <?= htmlspecialchars($user['email']) ?>
                        </a>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </div>
                    <div class="info-value">
                        <a href="tel:<?= htmlspecialchars($user['phone']) ?>" class="info-value email">
                            <?= htmlspecialchars($user['phone']) ?>
                        </a>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-user-tag"></i>
                        Account Type
                    </div>
                    <div class="info-value"><?= ucfirst($user['role']) ?></div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-cog"></i>
                    <h3>Account Details</h3>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-id-badge"></i>
                        User ID
                    </div>
                    <div class="info-value">#<?= htmlspecialchars($user['user_id']) ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-calendar-plus"></i>
                        Account Created
                    </div>
                    <div class="info-value">
                        <?= isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'Not available' ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-clock"></i>
                        Last Updated
                    </div>
                    <div class="info-value">
                        <?= isset($user['updated_at']) ? date('M d, Y', strtotime($user['updated_at'])) : 'Not available' ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-shield-alt"></i>
                        Account Status
                    </div>
                    <div class="info-value">
                        <span class="role-badge role-<?= $user['role'] ?>">
                            <i class="fas fa-check-circle"></i>
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="edit_profile.php" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                Edit Profile
            </a>
            <a href="<?= $back_link ?>" class="btn btn-success">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <a href="../logout.php" class="btn btn-danger" 
               onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <script>
        // Add loading states to buttons when clicked
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.href && !this.href.includes('mailto:') && !this.href.includes('tel:')) {
                    const icon = this.querySelector('i');
                    if (icon && !this.onclick) {
                        setTimeout(() => {
                            icon.className = 'loading';
                        }, 100);
                    }
                }
            });
        });

        // Add smooth hover effects
        document.querySelectorAll('.profile-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add click to copy functionality for user ID
        document.addEventListener('DOMContentLoaded', function() {
            const userIdElement = document.querySelector('.info-value:contains("#<?= $user['user_id'] ?>")');
            if (userIdElement) {
                userIdElement.style.cursor = 'pointer';
                userIdElement.title = 'Click to copy';
                userIdElement.addEventListener('click', function() {
                    navigator.clipboard.writeText('<?= $user['user_id'] ?>').then(() => {
                        // Show temporary feedback
                        const originalText = this.textContent;
                        this.textContent = 'Copied!';
                        setTimeout(() => {
                            this.textContent = originalText;
                        }, 2000);
                    });
                });
            }
        });
    </script>
</body>
</html>