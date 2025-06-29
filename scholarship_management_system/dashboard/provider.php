<?php
session_start();
include('../includes/db.php');

if ($_SESSION['role'] !== 'provider') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$providerRow = $conn->query("SELECT provider_id FROM SCHOLARSHIP_PROVIDER WHERE user_id = '$user_id'")->fetch_assoc();
$provider_id = $providerRow['provider_id'];

// Get list of coordinators
$coordinators = $conn->query("
    SELECT C.coordinator_id, U.name 
    FROM COORDINATOR C
    JOIN USER U ON C.user_id = U.user_id
");

// Handle new scholarship submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $amount = $_POST['amount'];
    $slots = $_POST['available_slots'];
    $deadline = $_POST['deadline'];
    $criteria = $_POST['eligibility_criteria'];
    $coordinator_id = $_POST['coordinator_id'];

    $scholarship_id = uniqid('S');
    $conn->query("INSERT INTO SCHOLARSHIP (scholarship_id, provider_id, coordinator_id, name, amount, available_slots, deadline, eligibility_criteria)
                  VALUES ('$scholarship_id', '$provider_id', '$coordinator_id', '$name', '$amount', '$slots', '$deadline', '$criteria')");

    // Notify coordinator
    $message = "📢 You have been assigned to coordinate the scholarship \"$name\".";
    $notif_id = uniqid("N");
    $date = date("Y-m-d");

    $conn->query("INSERT INTO NOTIFICATION (notification_id, message, sent_date, type, coordinator_id)
                  VALUES ('$notif_id', '$message', '$date', 'assignment', '$coordinator_id')");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - Scholarship Management</title>
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

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,0 1000,0 1000,60 0,100"/></svg>');
            background-size: cover;
        }

        .header h2 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .nav-menu {
            background: #34495e;
            padding: 20px 30px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 40px;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 15px;
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-1px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .form-group select {
            appearance: none;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="%23666" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 20px;
            cursor: pointer;
        }

        .submit-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 30px auto 0;
            min-width: 200px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .success-message {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h2 {
                font-size: 2rem;
            }

            .nav-menu {
                padding: 15px;
                gap: 10px;
            }

            .nav-link {
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .main-content {
                padding: 20px;
            }

            .form-container {
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 12px;
            }

            .submit-btn {
                padding: 15px 30px;
                font-size: 1rem;
                min-width: 180px;
            }
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 1.1rem;
        }

        .input-icon input {
            padding-left: 45px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-building"></i> Provider Dashboard</h2>
            <p class="subtitle">Create and manage scholarship opportunities</p>
        </div>

        <nav class="nav-menu">
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="../notifications.php" class="nav-link">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>

        <div class="main-content">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Scholarship successfully created and coordinator notified!
                </div>
            <?php endif; ?>

            <div class="form-container">
                <h3 class="form-title">
                    <i class="fas fa-graduation-cap"></i> Add New Scholarship
                </h3>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-tag"></i> Scholarship Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   placeholder="Enter scholarship name" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="amount">
                                <i class="fas fa-dollar-sign"></i> Amount
                            </label>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   placeholder="Enter amount" 
                                   min="1" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="available_slots">
                                <i class="fas fa-users"></i> Available Slots
                            </label>
                            <input type="number" 
                                   id="available_slots" 
                                   name="available_slots" 
                                   placeholder="Number of slots" 
                                   min="1" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="deadline">
                                <i class="fas fa-calendar-alt"></i> Application Deadline
                            </label>
                            <input type="date" 
                                   id="deadline" 
                                   name="deadline" 
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="eligibility_criteria">
                                <i class="fas fa-list-check"></i> Eligibility Criteria
                            </label>
                            <textarea id="eligibility_criteria" 
                                      name="eligibility_criteria" 
                                      placeholder="Describe the eligibility criteria for this scholarship..." 
                                      required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="coordinator_id">
                                <i class="fas fa-user-tie"></i> Select Coordinator
                            </label>
                            <select name="coordinator_id" id="coordinator_id" required>
                                <option value="">-- Choose a Coordinator --</option>
                                <?php while ($c = $coordinators->fetch_assoc()): ?>
                                    <option value="<?php echo $c['coordinator_id']; ?>">
                                        <?php echo $c['name'] . ' (ID: ' . $c['coordinator_id'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-plus-circle"></i>
                        Create Scholarship
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today for deadline field
        document.getElementById('deadline').min = new Date().toISOString().split('T')[0];
        
        // Add form validation feedback
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#27ae60';
                } else {
                    this.style.borderColor = '#e74c3c';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.style.borderColor === '#e74c3c' && this.checkValidity()) {
                    this.style.borderColor = '#3498db';
                }
            });
        });
    </script>
</body>
</html>