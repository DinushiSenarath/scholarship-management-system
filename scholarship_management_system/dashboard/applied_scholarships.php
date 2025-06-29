<?php
session_start();
include('../includes/db.php');

if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT student_id FROM STUDENT WHERE user_id = '$user_id'");
$student_id = $res->fetch_assoc()['student_id'];

$apps = $conn->query("
    SELECT S.name AS scholarship_name, A.status, A.submit_date
    FROM APPLICATION A
    JOIN STUDENT_APPLICATION SA ON A.application_id = SA.application_id
    JOIN SCHOLARSHIP S ON A.scholarship_id = S.scholarship_id
    WHERE SA.student_id = '$student_id'
    ORDER BY A.submit_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Applications</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h2>📋 Applied Scholarships</h2>
    <a href="student.php">⬅️ Back to Dashboard</a>
    <table>
        <tr><th>Scholarship</th><th>Status</th><th>Date</th></tr>
        <?php while ($row = $apps->fetch_assoc()): ?>
        <tr>
            <td><?= $row['scholarship_name'] ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['submit_date'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>