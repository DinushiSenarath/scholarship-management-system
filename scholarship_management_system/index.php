<?php
session_start();

// Check if user is already logged in and redirect to appropriate dashboard
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'student') {
        header("Location: dashboard/student.php");
        exit();
    } elseif ($role === 'coordinator') {
        header("Location: dashboard/coordinator.php");
        exit();
    } elseif ($role === 'provider') {
        header("Location: dashboard/provider.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduConnect - Educational Management Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-indigo-600">
                            <i class="fas fa-graduation-cap mr-2"></i>EduConnect
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="register.php" class="border border-indigo-600 text-indigo-600 hover:bg-indigo-600 hover:text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg hero-pattern pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center fade-in">
                <h1 class="text-4xl md:text-6xl font-bold text-yellow-300 mb-6 drop-shadow-2xl">
                    Welcome to <span class="text-orange-400">EduConnect</span>
                </h1>
                <p class="text-xl md:text-2xl text-orange-300 font-semibold mb-8 max-w-3xl mx-auto drop-shadow-2xl">
                    Connecting students, coordinators, and education providers in one comprehensive platform
                </p>
                <div class="space-x-4">
                    <a href="login.php" class="bg-yellow-400 text-gray-900 hover:bg-yellow-300 px-8 py-4 rounded-lg text-lg font-bold transition duration-300 inline-block shadow-lg hover:shadow-xl">
                        Get Started
                    </a>
                    <a href="#features" class="border-2 border-yellow-400 text-yellow-400 hover:bg-yellow-400 hover:text-gray-900 px-8 py-4 rounded-lg text-lg font-bold transition duration-300 inline-block shadow-lg hover:shadow-xl">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Powerful Features for Everyone
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Our platform provides tailored experiences for different user types
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Student Card -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 p-8 rounded-xl card-hover">
                    <div class="text-center">
                        <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-graduate text-white text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Students</h3>
                        <p class="text-gray-700 mb-6">
                            Access your courses, track progress, submit assignments, and connect with educators seamlessly.
                        </p>
                        <ul class="text-left text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Course Management</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Progress Tracking</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Assignment Submission</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Grade Monitoring</li>
                        </ul>
                    </div>
                </div>

                <!-- Coordinator Card -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-100 p-8 rounded-xl card-hover">
                    <div class="text-center">
                        <div class="bg-green-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users-cog text-white text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Coordinators</h3>
                        <p class="text-gray-700 mb-6">
                            Manage programs, oversee student progress, coordinate with providers, and ensure quality education.
                        </p>
                        <ul class="text-left text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Program Management</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Student Oversight</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Provider Coordination</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Quality Assurance</li>
                        </ul>
                    </div>
                </div>

                <!-- Provider Card -->
                <div class="bg-gradient-to-br from-purple-50 to-violet-100 p-8 rounded-xl card-hover">
                    <div class="text-center">
                        <div class="bg-purple-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Providers</h3>
                        <p class="text-gray-700 mb-6">
                            Deliver quality education, manage course content, track student performance, and collaborate effectively.
                        </p>
                        <ul class="text-left text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Content Management</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Student Assessment</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Performance Analytics</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Collaboration Tools</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-indigo-600 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Ready to Get Started?
            </h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">
                Join thousands of users who are already experiencing the power of EduConnect
            </p>
            <div class="space-x-4">
                <a href="register.php" class="bg-white text-indigo-600 hover:bg-gray-100 px-8 py-4 rounded-lg text-lg font-semibold transition duration-300 inline-block">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </a>
                <a href="login.php" class="border-2 border-white text-white hover:bg-white hover:text-indigo-600 px-8 py-4 rounded-lg text-lg font-semibold transition duration-300 inline-block">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="col-span-2">
                    <h3 class="text-2xl font-bold mb-4">
                        <i class="fas fa-graduation-cap mr-2"></i>EduConnect
                    </h3>
                    <p class="text-gray-300">
                        Empowering education through technology. Connecting students, coordinators, and providers for a better learning experience.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="login.php" class="hover:text-white transition">Login</a></li>
                        <li><a href="register.php" class="hover:text-white transition">Register</a></li>
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="contact.php" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="help.php" class="hover:text-white transition">Help Center</a></li>
                        <li><a href="faq.php" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="privacy.php" class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="terms.php" class="hover:text-white transition">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 EduConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>