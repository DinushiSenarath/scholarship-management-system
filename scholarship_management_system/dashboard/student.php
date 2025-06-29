<?php
session_start();
include('../includes/db.php');
if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT student_id FROM STUDENT WHERE user_id = '$user_id'");
$student = $res->fetch_assoc();
$student_id = $student['student_id'];
// Get all scholarships
$scholarships = $conn->query("SELECT * FROM SCHOLARSHIP");
// Get already applied scholarships for this student
$applied_result = $conn->query("SELECT scholarship_id FROM APPLICATION A
    JOIN STUDENT_APPLICATION SA ON A.application_id = SA.application_id
    WHERE SA.student_id = '$student_id'");
$applied_ids = [];
while ($row = $applied_result->fetch_assoc()) {
    $applied_ids[] = $row['scholarship_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Scholarship Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .dashboard-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 30px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .nav-section {
            padding: 25px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .nav-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #4f46e5;
            color: #4f46e5;
        }

        .nav-item i {
            font-size: 1.2rem;
            margin-right: 10px;
            width: 25px;
            text-align: center;
        }

        .content-section {
            padding: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            color: #1f2937;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #4f46e5;
        }

        .scholarships-grid {
            display: grid;
            gap: 20px;
        }

        .scholarship-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .scholarship-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .scholarship-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }

        .scholarship-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .scholarship-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1f2937;
            flex: 1;
            min-width: 200px;
        }

        .scholarship-amount {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .scholarship-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .deadline-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-weight: 500;
        }

        .deadline-info i {
            color: #ef4444;
        }

        .action-button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .apply-button {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
        }

        .apply-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
        }

        .applied-status {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            pointer-events: none;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #d1d5db;
        }

        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .nav-grid {
                grid-template-columns: 1fr;
            }
            
            .scholarship-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .scholarship-details {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <h1><i class="fas fa-graduation-cap"></i> Student Dashboard</h1>
            <p>Manage your scholarship applications and track your progress</p>
        </div>

        <!-- Navigation Section -->
        <div class="nav-section">
            <div class="nav-grid">
                <a href="../notifications.php" class="nav-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <a href="upload_document.php" class="nav-item">
                    <i class="fas fa-upload"></i>
                    <span>Upload Documents</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Content Section -->
        <div class="content-section">
            <!-- Statistics Section -->
            <div class="stats-section">
                <?php
                $total_scholarships = $scholarships->num_rows;
                $applied_count = count($applied_ids);
                $available_count = $total_scholarships - $applied_count;
                $scholarships->data_seek(0); // Reset pointer
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $total_scholarships ?></div>
                    <div class="stat-label">Total Scholarships</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $applied_count ?></div>
                    <div class="stat-label">Applications Submitted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $available_count ?></div>
                    <div class="stat-label">Available to Apply</div>
                </div>
            </div>

            <!-- Scholarships Section -->
            <h2 class="section-title">
                <i class="fas fa-award"></i>
                Available Scholarships
            </h2>

            <div class="scholarships-grid">
                <?php if ($scholarships->num_rows > 0): ?>
                    <?php while ($row = $scholarships->fetch_assoc()): ?>
                        <div class="scholarship-card">
                            <div class="scholarship-header">
                                <h3 class="scholarship-name"><?= htmlspecialchars($row['name']) ?></h3>
                                <div class="scholarship-amount">Rs. <?= number_format($row['amount']) ?></div>
                            </div>
                            
                            <div class="scholarship-details">
                                <div class="deadline-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Deadline: <?= date('M d, Y', strtotime($row['deadline'])) ?></span>
                                </div>
                                
                                <div class="action-section">
                                    <?php if (in_array($row['scholarship_id'], $applied_ids)): ?>
                                        <div class="action-button applied-status">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Already Applied</span>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" action="apply.php" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?= $student_id ?>">
                                            <input type="hidden" name="scholarship_id" value="<?= $row['scholarship_id'] ?>">
                                            <button type="submit" class="action-button apply-button">
                                                <i class="fas fa-paper-plane"></i>
                                                <span>Apply Now</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No Scholarships Available</h3>
                        <p>There are currently no scholarships available. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>