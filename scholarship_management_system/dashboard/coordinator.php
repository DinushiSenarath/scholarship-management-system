<?php
/********************************************************************
 *  COORDINATOR DASHBOARD
 *******************************************************************/
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// find the coordinator_id that belongs to this logged-in user
$result = $conn->query("SELECT coordinator_id
                        FROM COORDINATOR
                        WHERE user_id = '$user_id'");
$coordRow           = $result->fetch_assoc();
$coordinator_id     = $coordRow['coordinator_id'];
$_SESSION['coordinator_id'] = $coordinator_id;

/* -------------------- pull data for each tab -------------------- */
$pendingApps  = $conn->query("
    SELECT  A.*,
            U.name           AS student_name,
            ST.student_id,
            ST.gpa,
            ST.family_size,
            ST.income,
            S.scholarship_id,
            S.name           AS scholarship_name,
            S.eligibility_criteria
    FROM    APPLICATION A
            JOIN STUDENT_APPLICATION SA ON A.application_id = SA.application_id
            JOIN STUDENT ST            ON SA.student_id     = ST.student_id
            JOIN USER   U              ON ST.user_id        = U.user_id
            JOIN SCHOLARSHIP S         ON A.scholarship_id  = S.scholarship_id
    WHERE   A.status = 'pending'
      AND   A.coordinator_id = '$coordinator_id'
");

$approvedApps = $conn->query("
    SELECT  A.*,
            SA.student_id,
            U.name                  AS student_name,
            S.scholarship_id,
            S.name                  AS scholarship_name,
            S.amount
    FROM    APPLICATION A
            JOIN STUDENT_APPLICATION SA ON A.application_id = SA.application_id
            JOIN STUDENT ST            ON SA.student_id     = ST.student_id
            JOIN USER   U              ON ST.user_id        = U.user_id
            JOIN SCHOLARSHIP S         ON A.scholarship_id  = S.scholarship_id
    WHERE   A.status = 'approved'
      AND   A.coordinator_id = '$coordinator_id'
");

$rejectedApps = $conn->query("
    SELECT  A.*,
            U.name         AS student_name,
            S.name         AS scholarship_name
    FROM    APPLICATION A
            JOIN STUDENT_APPLICATION SA ON A.application_id = SA.application_id
            JOIN STUDENT ST            ON SA.student_id     = ST.student_id
            JOIN USER   U              ON ST.user_id        = U.user_id
            JOIN SCHOLARSHIP S         ON A.scholarship_id  = S.scholarship_id
    WHERE   A.status = 'rejected'
      AND   A.coordinator_id = '$coordinator_id'
");

// Get coordinator info for display
$coordInfo = $conn->query("SELECT C.*, U.name, U.email 
                          FROM COORDINATOR C 
                          JOIN USER U ON C.user_id = U.user_id 
                          WHERE C.coordinator_id = '$coordinator_id'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard - Scholarship Management System</title>
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
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .welcome-section h1 {
            color: var(--gray-800);
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .welcome-section p {
            color: var(--gray-600);
            margin-top: 5px;
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .nav-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .nav-btn.profile { background: var(--info-color); }
        .nav-btn.profile:hover { background: var(--info-hover); }
        .nav-btn.notifications { background: var(--warning-color); }
        .nav-btn.notifications:hover { background: var(--warning-hover); }
        .nav-btn.payments { background: var(--success-color); }
        .nav-btn.payments:hover { background: var(--success-hover); }
        .nav-btn.logout { background: var(--danger-color); }
        .nav-btn.logout:hover { background: var(--danger-hover); }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        }

        .stat-card.pending .stat-icon { color: var(--warning-color); }
        .stat-card.approved .stat-icon { color: var(--success-color); }
        .stat-card.rejected .stat-icon { color: var(--danger-color); }

        .stat-number {
            font-size: 2.5rem;
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

        /* Section Cards */
        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .section-header {
            padding: 25px 30px;
            border-bottom: 2px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .section-header.pending { border-left: 4px solid var(--warning-color); }
        .section-header.approved { border-left: 4px solid var(--success-color); }
        .section-header.rejected { border-left: 4px solid var(--danger-color); }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: var(--gray-50);
            color: var(--gray-700);
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-approve {
            background: var(--success-color);
            color: white;
        }

        .btn-approve:hover {
            background: var(--success-hover);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-reject {
            background: var(--danger-color);
            color: white;
        }

        .btn-reject:hover {
            background: var(--danger-hover);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-payment {
            background: var(--info-color);
            color: white;
        }

        .btn-payment:hover {
            background: var(--info-hover);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Amount styling */
        .amount {
            font-weight: 600;
            color: var(--success-color);
            font-size: 15px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 1.25rem;
            margin-bottom: 10px;
            color: var(--gray-700);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .welcome-section h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                justify-content: center;
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

        /* Form styling */
        form {
            display: inline-block;
        }

        .confirmation-form {
            position: relative;
        }

        .confirmation-form:hover::after {
            content: "Click to confirm payment";
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gray-800);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-top">
                <div class="welcome-section">
                    <h1><i class="fas fa-user-tie"></i> Coordinator Dashboard</h1>
                    <p>Welcome back, <strong><?= htmlspecialchars($coordInfo['name']) ?></strong></p>
                </div>
                <div class="header-actions">
                    <a href="profile.php" class="nav-btn profile">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <a href="../notifications.php" class="nav-btn notifications">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                    <a href="done_payments.php" class="nav-btn payments">
                        <i class="fas fa-credit-card"></i> View Payments
                    </a>
                    <a href="../logout.php" class="nav-btn logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= $pendingApps->num_rows ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $approvedApps->num_rows ?></div>
                <div class="stat-label">Approved Applications</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number"><?= $rejectedApps->num_rows ?></div>
                <div class="stat-label">Rejected Applications</div>
            </div>
        </div>

        <!-- PENDING APPLICATIONS -->
        <div class="section-card">
            <div class="section-header pending">
                <i class="fas fa-clock"></i>
                <h3>Pending Applications</h3>
            </div>
            <div class="table-container">
                <?php if ($pendingApps->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user-graduate"></i> Student</th>
                                <th><i class="fas fa-award"></i> Scholarship</th>
                                <th><i class="fas fa-chart-line"></i> GPA</th>
                                <th><i class="fas fa-users"></i> Family Size</th>
                                <th><i class="fas fa-dollar-sign"></i> Income (Rs.)</th>
                                <th><i class="fas fa-list-check"></i> Eligibility Criteria</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pendingApps->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['student_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['scholarship_name']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['gpa']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['family_size']) ?></td>
                                    <td>Rs. <?= number_format($row['income'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['eligibility_criteria']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" action="process_application.php" style="display:inline;">
                                                <input type="hidden" name="application_id" value="<?= $row['application_id'] ?>">
                                                <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
                                                <input type="hidden" name="scholarship_id" value="<?= $row['scholarship_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button class="btn-action btn-approve" type="submit" 
                                                        onclick="return confirm('Are you sure you want to approve this application?');">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="process_application.php" style="display:inline;">
                                                <input type="hidden" name="application_id" value="<?= $row['application_id'] ?>">
                                                <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
                                                <input type="hidden" name="scholarship_id" value="<?= $row['scholarship_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button class="btn-action btn-reject" type="submit"
                                                        onclick="return confirm('Are you sure you want to reject this application?');">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>No Pending Applications</h4>
                        <p>All applications have been processed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- APPROVED APPLICATIONS -->
        <div class="section-card">
            <div class="section-header approved">
                <i class="fas fa-check-circle"></i>
                <h3>Approved Applications</h3>
            </div>
            <div class="table-container">
                <?php if ($approvedApps->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user-graduate"></i> Student</th>
                                <th><i class="fas fa-award"></i> Scholarship</th>
                                <th><i class="fas fa-money-bill-wave"></i> Amount</th>
                                <th><i class="fas fa-credit-card"></i> Payment Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $approvedApps->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['student_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['scholarship_name']) ?></td>
                                    <td>
                                        <span class="amount">Rs. <?= number_format($row['amount'], 2) ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" action="add_payment.php" class="confirmation-form"
                                              onsubmit="return confirm('Confirm payment of Rs. <?= number_format($row['amount'], 2) ?> for <?= htmlspecialchars($row['student_name']) ?>?');">
                                            <input type="hidden" name="application_id" value="<?= $row['application_id'] ?>">
                                            <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
                                            <input type="hidden" name="scholarship_id" value="<?= $row['scholarship_id'] ?>">
                                            <input type="hidden" name="amount" value="<?= $row['amount'] ?>">
                                            <button class="btn-action btn-payment" type="submit">
                                                <i class="fas fa-money-bill-transfer"></i> Process Payment
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h4>No Approved Applications</h4>
                        <p>No applications are currently approved for payment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- REJECTED APPLICATIONS -->
        <div class="section-card">
            <div class="section-header rejected">
                <i class="fas fa-times-circle"></i>
                <h3>Rejected Applications</h3>
            </div>
            <div class="table-container">
                <?php if ($rejectedApps->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user-graduate"></i> Student</th>
                                <th><i class="fas fa-award"></i> Scholarship</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $rejectedApps->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['student_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['scholarship_name']) ?></td>
                                    <td>
                                        <span class="status-badge status-rejected">
                                            <i class="fas fa-times"></i> Rejected
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-times-circle"></i>
                        <h4>No Rejected Applications</h4>
                        <p>No applications have been rejected yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add loading states to buttons when clicked
        document.querySelectorAll('.btn-action').forEach(button => {
            button.addEventListener('click', function() {
                if (this.type === 'submit') {
                    setTimeout(() => {
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.className = 'loading';
                        }
                        this.disabled = true;
                    }, 100);
                }
            });
        });

        // Add smooth scrolling to sections
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Auto-refresh page every 5 minutes to get latest data
        setTimeout(() => {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>