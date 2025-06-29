<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name        = $_POST['name'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $password    = $_POST['password'];
    $income      = $_POST['income'];
    $gpa         = $_POST['gpa'];
    $family_size = $_POST['family_size'];

    $role = 'student';
    $user_id = uniqid('U');
    $student_id = uniqid('S');

    // Insert into USER
    $stmt = $conn->prepare("INSERT INTO USER (user_id, name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $user_id, $name, $email, $phone, $password, $role);
    
    if ($stmt->execute()) {
        // Insert into STUDENT
        $stmt2 = $conn->prepare("INSERT INTO STUDENT (student_id, user_id, income, gpa, family_size) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssddi", $student_id, $user_id, $income, $gpa, $family_size);
        if ($stmt2->execute()) {
            header("Location: login.php?success=registered");
            exit();
        } else {
            $error = "Failed to save student details.";
        }
    } else {
        $error = "Failed to register. Email might already be used.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Signup - EduConnect</title>
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
                    <a href="login.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 transition duration-300">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login
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
                <div class="mx-auto h-16 w-16 bg-blue-500 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-user-graduate text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-yellow-300 drop-shadow-2xl">
                    Student Registration
                </h2>
                <p class="mt-2 text-orange-300 drop-shadow-xl">
                    Join EduConnect as a student and start your learning journey
                </p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <div class="bg-white rounded-xl shadow-2xl p-8 fade-in">
                <form method="POST" class="space-y-4">
                    <!-- Personal Information Section -->
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            <i class="fas fa-user mr-2 text-blue-500"></i>Personal Information
                        </h3>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" placeholder="Enter your full name" required 
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" placeholder="Enter your email" required 
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="phone" placeholder="Enter your phone number" required 
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input type="password" name="password" placeholder="Create a secure password" required 
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <!-- Academic & Financial Information Section -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            <i class="fas fa-chart-line mr-2 text-green-500"></i>Academic & Financial Details
                        </h3>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Annual Income</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-500">$</span>
                                    <input type="number" name="income" placeholder="50000" step="0.01" required 
                                           class="form-input w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">GPA (Grade Point Average)</label>
                                <input type="number" name="gpa" placeholder="3.50" step="0.01" min="0" max="4" required 
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                                <p class="text-xs text-gray-500 mt-1">Scale: 0.00 - 4.00</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Family Size</label>
                                <input type="number" name="family_size" placeholder="4" min="1" required 
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                                <p class="text-xs text-gray-500 mt-1">Including yourself</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-4 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition duration-300 font-semibold text-lg shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>Create Student Account
                    </button>
                </form>

                <!-- Login Link -->
                <div class="text-center mt-6 pt-4 border-t border-gray-200">
                    <p class="text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition duration-300">
                            <i class="fas fa-sign-in-alt mr-1"></i>Login here
                        </a>
                    </p>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="text-center">
                <p class="text-orange-200 text-sm drop-shadow-lg">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Your information is secure and encrypted
                </p>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input');
            
            // Add floating label effect
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

            // GPA validation
            const gpaInput = document.querySelector('input[name="gpa"]');
            gpaInput.addEventListener('input', function() {
                const value = parseFloat(this.value);
                if (value > 4.0) {
                    this.value = '4.00';
                }
            });

            // Phone number formatting (basic)
            const phoneInput = document.querySelector('input[name="phone"]');
            phoneInput.addEventListener('input', function() {
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
            });
        });
    </script>
</body>
</html>