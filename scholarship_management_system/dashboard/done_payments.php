<?php
session_start();
include('../includes/db.php');

// Security: Check if user is logged in and has coordinator role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../login.php");
    exit();
}

// Security: Validate coordinator_id exists
if (!isset($_SESSION['coordinator_id'])) {
    header("Location: ../login.php");
    exit();
}

$coordinator_id = intval($_SESSION['coordinator_id']); // Type casting for security

// Prepared statement to prevent SQL injection
$query = "
    SELECT 
        P.payment_id,
        P.payment_date,
        P.method,
        P.amount,
        U.name AS student_name,
        S.name AS scholarship_name
    FROM PAYMENT P
    JOIN SCHOLARSHIP_AWARDED A ON P.award_id = A.award_id
    JOIN STUDENT S2 ON A.student_id = S2.student_id
    JOIN USER U ON S2.user_id = U.user_id
    JOIN SCHOLARSHIP S ON A.scholarship_id = S.scholarship_id
    WHERE S.coordinator_id = ?
    ORDER BY P.payment_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $coordinator_id);
$stmt->execute();
$result = $stmt->get_result();

// Calculate total payments
$total_amount = 0;
$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    $total_amount += $row['amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Scholarship Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 600;
        }

        .header-title i {
            font-size: 2.5rem;
            opacity: 0.9;
        }

        .header-stats {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .controls-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .search-filter {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 15px 10px 40px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .payments-table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .payments-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        .payments-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .payments-table tbody tr {
            transition: all 0.3s ease;
        }

        .payments-table tbody tr:hover {
            background-color: #f8f9ff;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .payment-id {
            font-family: 'Courier New', monospace;
            background: #e3f2fd;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #1565c0;
        }

        .student-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .scholarship-name {
            color: #16a085;
            font-weight: 500;
        }

        .amount {
            font-weight: bold;
            color: #27ae60;
            font-size: 16px;
        }

        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .method-bank {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .method-card {
            background: #fff3e0;
            color: #f57c00;
        }

        .method-cash {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .payment-date {
            color: #6c757d;
            font-size: 13px;
        }

        .no-payments {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-payments i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .header-stats {
                justify-content: center;
            }

            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-filter {
                flex-direction: column;
            }

            .search-box input {
                width: 100%;
            }

            .payments-table {
                font-size: 12px;
            }

            .payments-table th,
            .payments-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-credit-card"></i>
                    <h1>Payment History</h1>
                </div>
                <div class="header-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= count($payments) ?></span>
                        <span class="stat-label">Total Payments</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">Rs. <?= number_format($total_amount, 2) ?></span>
                        <span class="stat-label">Total Amount</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <a href="coordinator.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            
            <div class="search-filter">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by student name or scholarship..." onkeyup="filterTable()">
                </div>
                <select class="filter-select" id="methodFilter" onchange="filterTable()">
                    <option value="">All Methods</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="card">Card Payment</option>
                    <option value="cash">Cash</option>
                </select>
                <a href="#" class="export-btn" onclick="exportToCSV()">
                    <i class="fas fa-download"></i>
                    Export CSV
                </a>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="payments-table-container">
            <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="payments-table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Payment ID</th>
                                <th><i class="fas fa-user-graduate"></i> Student Name</th>
                                <th><i class="fas fa-graduation-cap"></i> Scholarship</th>
                                <th><i class="fas fa-money-bill-wave"></i> Amount</th>
                                <th><i class="fas fa-credit-card"></i> Method</th>
                                <th><i class="fas fa-calendar-alt"></i> Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><span class="payment-id">#<?= htmlspecialchars($payment['payment_id']) ?></span></td>
                                    <td><span class="student-name"><?= htmlspecialchars($payment['student_name']) ?></span></td>
                                    <td><span class="scholarship-name"><?= htmlspecialchars($payment['scholarship_name']) ?></span></td>
                                    <td><span class="amount">Rs. <?= number_format($payment['amount'], 2) ?></span></td>
                                    <td>
                                        <span class="payment-method method-<?= strtolower($payment['method']) ?>">
                                            <?php
                                            $method_icons = [
                                                'bank' => 'fas fa-university',
                                                'card' => 'fas fa-credit-card',
                                                'cash' => 'fas fa-money-bill-alt'
                                            ];
                                            $method = strtolower($payment['method']);
                                            $icon = $method_icons[$method] ?? 'fas fa-money-bill';
                                            ?>
                                            <i class="<?= $icon ?>"></i>
                                            <?= ucfirst(htmlspecialchars($payment['method'])) ?>
                                        </span>
                                    </td>
                                    <td><span class="payment-date"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-payments">
                    <i class="fas fa-receipt"></i>
                    <h3>No Payments Found</h3>
                    <p>There are no completed payments to display at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const methodFilter = document.getElementById('methodFilter').value.toLowerCase();
            const table = document.getElementById('paymentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const studentName = rows[i].cells[1].textContent.toLowerCase();
                const scholarshipName = rows[i].cells[2].textContent.toLowerCase();
                const paymentMethod = rows[i].cells[4].textContent.toLowerCase();

                const matchesSearch = studentName.includes(searchInput) || scholarshipName.includes(searchInput);
                const matchesMethod = methodFilter === '' || paymentMethod.includes(methodFilter);

                if (matchesSearch && matchesMethod) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        function exportToCSV() {
            const table = document.getElementById('paymentsTable');
            const rows = table.querySelectorAll('tr');
            let csvContent = '';

            // Add headers
            const headers = Array.from(rows[0].cells).map(cell => cell.textContent.trim());
            csvContent += headers.join(',') + '\n';

            // Add data rows (only visible ones)
            for (let i = 1; i < rows.length; i++) {
                if (rows[i].style.display !== 'none') {
                    const rowData = Array.from(rows[i].cells).map(cell => {
                        return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
                    });
                    csvContent += rowData.join(',') + '\n';
                }
            }

            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'payment_history_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Auto-refresh every 30 seconds to show new payments
        setInterval(function() {
            // Only refresh if user hasn't interacted recently
            if (document.getElementById('searchInput').value === '' && document.getElementById('methodFilter').value === '') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>