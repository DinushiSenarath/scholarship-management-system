<?php
session_start();
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $scholarship_id = $_POST['scholarship_id'];

    // Check if already applied
    $check = $conn->query("SELECT * FROM APPLICATION A
        JOIN STUDENT_APPLICATION SA ON A.application_id = SA.application_id
        WHERE SA.student_id = '$student_id' AND A.scholarship_id = '$scholarship_id'");
    if ($check->num_rows > 0) {
        header("Location: student.php?error=already_applied");
        exit();
    }

    // Get coordinator from scholarship
    $coordinatorRow = $conn->query("SELECT coordinator_id FROM SCHOLARSHIP WHERE scholarship_id = '$scholarship_id'");
    $coordinator = $coordinatorRow->fetch_assoc()['coordinator_id'];

    // Validate coordinator
    if (!$coordinator) {
        die("❌ Error: This scholarship is not assigned to any coordinator. Please contact your provider.");
    }

    // Proceed with application
    $app_id = uniqid('APP');
    $conn->query("INSERT INTO APPLICATION (application_id, scholarship_id, coordinator_id, submit_date, priority, points, status, eligibility_status)
                  VALUES ('$app_id', '$scholarship_id', '$coordinator', CURDATE(), 1, 0, 'pending', 'pending')");
    $conn->query("INSERT INTO STUDENT_APPLICATION (student_id, application_id) VALUES ('$student_id', '$app_id')");

    // Notification
    $notif_id = uniqid();
    $conn->query("INSERT INTO NOTIFICATION (notification_id, student_id, message, sent_date, type)
                  VALUES ('$notif_id', '$student_id', '📨 Your application has been submitted.', CURDATE(), 'submission')");

    header("Location: student.php?success=applied");
    exit();
}
?>
