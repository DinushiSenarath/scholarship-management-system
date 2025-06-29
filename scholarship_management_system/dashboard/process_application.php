<?php
/********************************************************************
 *  HANDLE APPROVE / REJECT  (Coordinator)
 *******************************************************************/
session_start();
include('../includes/db.php');

/* throw mysqli exceptions so we can catch them */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: coordinator.php");
    exit();
}

/* ----------- posted fields ----------- */
$action         = $_POST['action'];           // "approve" | "reject"
$application_id = $_POST['application_id'];
$student_id     = $_POST['student_id'];
$scholarship_id = $_POST['scholarship_id'];
$coordinator_id = $_SESSION['coordinator_id'];

$status = ($action === 'approve') ? 'approved' : 'rejected';
$today  = date('Y-m-d');

$conn->begin_transaction();

try {
    /* 1️⃣  update APPLICATION status */
    $up = $conn->prepare(
        "UPDATE APPLICATION
         SET status = ?
         WHERE application_id = ? AND coordinator_id = ?"
    );
    $up->bind_param('sss', $status, $application_id, $coordinator_id);
    $up->execute();
    $up->close();

    /* 2️⃣  if approved, ensure one award row exists */
    if ($action === 'approve') {
        $aw = $conn->prepare(
            "SELECT award_id
             FROM scholarship_awarded
             WHERE student_id = ? AND scholarship_id = ?"
        );
        $aw->bind_param('ss', $student_id, $scholarship_id);
        $aw->execute();
        $aw->store_result();                       // 🟢 frees connection
        $hasAward = $aw->num_rows > 0;
        $award_id = null;

        if ($hasAward) {
            $aw->bind_result($award_id);
            $aw->fetch();
        }
        $aw->close();

        if (!$hasAward) {
            $award_id = uniqid('AWD');
            $insAw = $conn->prepare(
                "INSERT INTO scholarship_awarded
                       (award_id, student_id, scholarship_id, award_date)
                 VALUES (?,?,?,?)"
            );
            $insAw->bind_param('ssss', $award_id, $student_id, $scholarship_id, $today);
            $insAw->execute();
            $insAw->close();
        }
    }

    /* 3️⃣  notification to student */
    $ntf_id  = uniqid('NTF');
    $msgStu  = ($action === 'approve')
        ? "Good news! Your scholarship application has been approved."
        : "Unfortunately, your scholarship application has been rejected.";

    $ntfStu = $conn->prepare(
        "INSERT INTO notification
              (notification_id, student_id, message, sent_date, type, read_status)
         VALUES (?,?,?,?,?,0)"
    );
    $ntfStu->bind_param('sssss', $ntf_id, $student_id, $msgStu, $today, $action);
    $ntfStu->execute();
    $ntfStu->close();

    /* 4️⃣  provider notification only when approved */
    if ($action === 'approve') {
        $prov = $conn->prepare(
            "SELECT provider_id, name
             FROM SCHOLARSHIP
             WHERE scholarship_id = ?"
        );
        $prov->bind_param('s', $scholarship_id);
        $prov->execute();
        $prov->store_result();                     // 🟢 frees connection
        $provider_id = $sch_name = null;
        if ($prov->num_rows > 0) {
            $prov->bind_result($provider_id, $sch_name);
            $prov->fetch();
        }
        $prov->close();

        if ($provider_id) {
            /* fetch student name (simple buffered query is fine now) */
            $rowStu = $conn->query("
                SELECT U.name
                FROM STUDENT S JOIN USER U ON S.user_id = U.user_id
                WHERE S.student_id = '$student_id'
            ")->fetch_assoc();
            $student_name = $rowStu['name'] ?? 'A student';

            $ntfProvID = uniqid('NTF');
            $msgProv   = "Student $student_name has been approved for the \"$sch_name\" scholarship.";
            $ntfProv = $conn->prepare(
                "INSERT INTO notification
                      (notification_id, provider_id, message, sent_date, type, read_status)
                 VALUES (?,?,?,?,?,0)"
            );
            $typeProv = 'app_approved';
            $ntfProv->bind_param('sssss', $ntfProvID, $provider_id, $msgProv, $today, $typeProv);
            $ntfProv->execute();
            $ntfProv->close();
        }
    }

    $conn->commit();
    header("Location: coordinator.php?process=success");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();   // you can style / log this as needed
}
