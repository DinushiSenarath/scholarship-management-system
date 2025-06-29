<?php
session_start();
include 'includes/db.php';

$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$role || !$user_id) {
    header("Location: login.php");
    exit();
}

// Resolve student_id or provider_id based on role using prepared statements
$student_id = $provider_id = null;
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT student_id FROM STUDENT WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'] ?? null;
    $stmt->close();
} elseif ($role === 'provider') {
    $stmt = $conn->prepare("SELECT provider_id FROM SCHOLARSHIP_PROVIDER WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $provider_id = $row['provider_id'] ?? null;
    $stmt->close();
}

// Mark as read with prepared statement
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE NOTIFICATION SET read_status = 1 WHERE notification_id = ?");
    $stmt->bind_param("i", $nid);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    if ($role === 'student' && $student_id) {
        $stmt = $conn->prepare("UPDATE NOTIFICATION SET read_status = 1 WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
    } elseif ($role === 'provider' && $provider_id) {
        $stmt = $conn->prepare("UPDATE NOTIFICATION SET read_status = 1 WHERE provider_id = ?");
        $stmt->bind_param("s", $provider_id);
    }
    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php");
    exit();
}

// Filter logic with prepared statements
$type = $_GET['type'] ?? '';
$date = $_GET['date'] ?? '';
$status = $_GET['status'] ?? '';

// Build query with proper parameters
$query = "SELECT * FROM NOTIFICATION WHERE 1=1";
$params = [];
$types = "";

if ($role === 'student' && $student_id) {
    $query .= " AND student_id = ?";
    $params[] = $student_id;
    $types .= "s";
}
if ($role === 'provider' && $provider_id) {
    $query .= " AND provider_id = ?";
    $params[] = $provider_id;
    $types .= "s";
}
if (!empty($type)) {
    $query .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}
if (!empty($date)) {
    $query .= " AND DATE(sent_date) = ?";
    $params[] = $date;
    $types .= "s";
}
if ($status === 'unread') {
    $query .= " AND read_status = 0";
} elseif ($status === 'read') {
    $query .= " AND read_status = 1";
}

$query .= " ORDER BY sent_date DESC, notification_id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get notification counts
$unread_count = 0;
$total_count = 0;
$count_query = "SELECT COUNT(*) as total, SUM(CASE WHEN read_status = 0 THEN 1 ELSE 0 END) as unread FROM NOTIFICATION WHERE 1=1";
$count_params = [];
$count_types = "";

if ($role === 'student' && $student_id) {
    $count_query .= " AND student_id = ?";
    $count_params[] = $student_id;
    $count_types .= "s";
} elseif ($role === 'provider' && $provider_id) {
    $count_query .= " AND provider_id = ?";
    $count_params[] = $provider_id;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$counts = $count_result->fetch_assoc();
$total_count = $counts['total'];
$unread_count = $counts['unread'];
$count_stmt->close();

function getIcon($type) {
    return [
        'approval' => '✅',
        'rejection' => '❌',
        'payment' => '💰',
        'info' => 'ℹ️',
        'submission' => '📨',
        'provider_alert' => '📢',
        'application' => '📃',
        'reminder' => '⏰',
        'update' => '🔄',
        'warning' => '⚠️'
    ][$type] ?? '📍';
}

function getTypeColor($type) {
    return [
        'approval' => '#27ae60',
        'rejection' => '#e74c3c',
        'payment' => '#f39c12',
        'info' => '#3498db',
        'submission' => '#9b59b6',
        'provider_alert' => '#e67e22',
        'application' => '#2ecc71',
        'reminder' => '#f1c40f',
        'update' => '#34495e',
        'warning' => '#e74c3c'
    ][$type] ?? '#95a5a6';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

$back = ($role === 'student') ? 'dashboard/student.php' :
         (($role === 'coordinator') ? 'dashboard/coordinator.php' : 'dashboard/provider.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📬 Notifications - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .notifications-container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        
        .notifications-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .notifications-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .notifications-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .back-btn {
            position: absolute;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .stats-bar {
            background: #f8f9fa;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .stats {
            display: flex;
            gap: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .mark-all-btn {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .mark-all-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .filter-section {
            padding: 1.5rem 2rem;
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .filter-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .filter-input {
            padding: 0.5rem;
            border: 2px solid #e3f2fd;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #fafbfc;
            transition: all 0.3s ease;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: #3498db;
            background: #ffffff;
        }
        
        .filter-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .notifications-list {
            padding: 1rem 2rem 2rem;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .notification-item {
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .notification-item.unread {
            border-left: 4px solid #3498db;
            background: linear-gradient(90deg, #ebf8ff 0%, #ffffff 100%);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .notification-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }
        
        .new-badge {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .notification-message {
            color: #34495e;
            line-height: 1.5;
            margin: 0.5rem 0;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .mark-read-btn {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .mark-read-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .no-notifications {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .no-notifications-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .notifications-container {
                margin: 0;
                border-radius: 0;
            }
            
            .notifications-header {
                padding: 1.5rem 1rem;
            }
            
            .back-btn {
                position: static;
                transform: none;
                margin-bottom: 1rem;
                display: inline-block;
            }
            
            .stats-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .stats {
                justify-content: space-around;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .notifications-list {
                padding: 1rem;
            }
            
            .notification-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .notification-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }
        }
        
        /* Custom scrollbar */
        .notifications-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .notifications-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .notifications-list::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 4px;
        }
        
        .notifications-list::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <!-- Header -->
        <div class="notifications-header">
            <a href="<?= htmlspecialchars($back) ?>" class="back-btn">← Back to Dashboard</a>
            <h2>📬 Notifications</h2>
            <p>Stay updated with your latest activities</p>
        </div>
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?= $total_count ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: #e74c3c;"><?= $unread_count ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: #27ae60;"><?= $total_count - $unread_count ?></div>
                    <div class="stat-label">Read</div>
                </div>
            </div>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="mark-all-btn" onclick="return confirm('Mark all notifications as read?')">
                    ✅ Mark All Read
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Type</label>
                    <select name="type" class="filter-input">
                        <option value="">All Types</option>
                        <option value="approval" <?= $type === 'approval' ? 'selected' : '' ?>>✅ Approval</option>
                        <option value="rejection" <?= $type === 'rejection' ? 'selected' : '' ?>>❌ Rejection</option>
                        <option value="payment" <?= $type === 'payment' ? 'selected' : '' ?>>💰 Payment</option>
                        <option value="submission" <?= $type === 'submission' ? 'selected' : '' ?>>📨 Submission</option>
                        <option value="provider_alert" <?= $type === 'provider_alert' ? 'selected' : '' ?>>📢 Provider Alert</option>
                        <option value="application" <?= $type === 'application' ? 'selected' : '' ?>>📃 Application</option>
                        <option value="info" <?= $type === 'info' ? 'selected' : '' ?>>ℹ️ Information</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-input">
                        <option value="">All Status</option>
                        <option value="unread" <?= $status === 'unread' ? 'selected' : '' ?>>Unread</option>
                        <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Read</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="filter-input">
                </div>
                
                <button type="submit" class="filter-btn">🔍 Filter</button>
                
                <?php if (!empty($type) || !empty($date) || !empty($status)): ?>
                    <a href="notifications.php" class="filter-btn" style="background: #95a5a6; text-decoration: none;">
                        🔄 Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="notification-item <?= $row['read_status'] ? '' : 'unread' ?>">
                        <div class="notification-header">
                            <div class="notification-type">
                                <span style="font-size: 1.2rem;"><?= getIcon($row['type']) ?></span>
                                <span class="type-badge" style="background: <?= getTypeColor($row['type']) ?>;">
                                    <?= strtoupper(str_replace('_', ' ', $row['type'])) ?>
                                </span>
                                <?php if (!$row['read_status']): ?>
                                    <span class="new-badge">NEW</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="notification-message">
                            <?= htmlspecialchars($row['message']) ?>
                        </div>
                        
                        <div class="notification-meta">
                            <div class="notification-time">
                                🕐 <?= timeAgo($row['sent_date']) ?>
                                <span style="margin-left: 0.5rem; color: #bdc3c7;">
                                    <?= date('M j, Y g:i A', strtotime($row['sent_date'])) ?>
                                </span>
                            </div>
                            
                            <?php if (!$row['read_status']): ?>
                                <a href="?read=<?= $row['notification_id'] ?>" class="mark-read-btn">
                                    ✅ Mark as Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <div class="no-notifications-icon">📭</div>
                    <h3>No notifications found</h3>
                    <p>You're all caught up! Check back later for new updates.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh notifications every 5 minutes
        setInterval(function() {
            // Only refresh if no filters are applied
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('type') && !urlParams.has('date') && !urlParams.has('status')) {
                location.reload();
            }
        }, 300000); // 5 minutes
        
        // Smooth scroll for mark as read
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;
                
                // Add loading state
                this.innerHTML = '⏳ Processing...';
                this.style.pointerEvents = 'none';
                
                // Navigate after brief delay for user feedback
                setTimeout(() => {
                    window.location.href = url;
                }, 500);
            });
        });
    </script>
</body>
</html>