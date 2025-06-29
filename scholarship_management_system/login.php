<?php
session_start();
include 'includes/db.php';

$error = '';
$success = '';

// Check for success message from registration
if (isset($_GET['success']) && $_GET['success'] === 'registered') {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM USER WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'student') {
                header("Location: dashboard/student.php");
            } elseif ($user['role'] === 'coordinator') {
                $uid = $user['user_id'];
                $cstmt = $conn->prepare("SELECT coordinator_id FROM COORDINATOR WHERE user_id = ?");
                $cstmt->bind_param("s", $uid);
                $cstmt->execute();
                $cresult = $cstmt->get_result();
                if ($cresult->num_rows === 1) {
                    $cinfo = $cresult->fetch_assoc();
                    $_SESSION['coordinator_id'] = $cinfo['coordinator_id'];
                    header("Location: dashboard/coordinator.php");
                } else {
                    $error = "Coordinator ID not found.";
                }
            } elseif ($user['role'] === 'provider') {
                header("Location: dashboard/provider.php");
            } else {
                $error = "Unknown role.";
            }
            if (empty($error)) {
                exit();
            }
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .role-icon {
            transition: all 0.3s ease;
        }
        .role-icon:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg hero-pattern">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-indigo-600">
                            <i class="fas fa-graduation-cap mr-2"></i>EduConnect
                        </h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="signup.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 transition duration-300">
                        <i class="fas fa-user-plus mr-1"></i>Register
                    </a>
                    <a href="index.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 transition duration-300">
                        <i class="fas fa-home mr-1"></i>Home
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center fade-in">
                <div class="mx-auto h-16 w-16 bg-indigo-500 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-sign-in-alt text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-yellow-300 drop-shadow-2xl">
                    Welcome Back
                </h2>
                <p class="mt-2 text-orange-300 drop-shadow-xl">
                    Sign in to your EduConnect account
                </p>
            </div>

            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="bg-white rounded-xl shadow-2xl p-8 fade-in">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-indigo-500"></i>Email Address
                        </label>
                        <input type="email" name="email" placeholder="Enter your email address" required 
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-indigo-500"></i>Password
                        </label>
                        <input type="password" name="password" placeholder="Enter your password" required 
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                    </div>

                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition duration-300 font-semibold text-lg shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </button>
                </form>

                <!-- Register Link -->
                <div class="text-center mt-6 pt-4 border-t border-gray-200">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <a href="signup.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition duration-300">
                            <i class="fas fa-user-plus mr-1"></i>Register as Student
                        </a>
                    </p>
                </div>
            </div>

            <!-- User Roles Info -->
            <div class="bg-white bg-opacity-90 rounded-xl p-6 fade-in">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                    <i class="fas fa-users mr-2"></i>User Roles
                </h3>
                <div class="flex justify-around space-x-4">
                    <!-- Student -->
                    <div class="text-center">
                        <div class="role-icon bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-user-graduate text-blue-600 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Student</p>
                        <p class="text-xs text-gray-500">Learning & Progress</p>
                    </div>
                    
                    <!-- Coordinator -->
                    <div class="text-center">
                        <div class="role-icon bg-green-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-users-cog text-green-600 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Coordinator</p>
                        <p class="text-xs text-gray-500">Program Management</p>
                    </div>
                    
                    <!-- Provider -->
                    <div class="text-center">
                        <div class="role-icon bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-chalkboard-teacher text-purple-600 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Provider</p>
                        <p class="text-xs text-gray-500">Education Delivery</p>
                    </div>
                </div>
            </div>

            <!-- Security Info -->
            <div class="text-center">
                <p class="text-orange-200 text-sm drop-shadow-lg">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Your login is secure and encrypted
                </p>
            </div>
        </div>
    </div>

    <script>
        // Form enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input');
            
            // Add focus effects
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 500);
                }, 5000);
            });

            // Role icons animation
            const roleIcons = document.querySelectorAll('.role-icon');
            roleIcons.forEach((icon, index) => {
                setTimeout(() => {
                    icon.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        icon.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            });
        });
    </script>
</body>
</html>