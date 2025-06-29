<?php
/********************************************************************
 *  MAKE PAYMENT  (Coordinator)
 *******************************************************************/
session_start();
include('../includes/db.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: coordinator.php");
    exit();
}

/* ------------- posted fields ------------- */
$application_id = $_POST['application_id'];
$student_id     = $_POST['student_id'];
$scholarship_id = $_POST['scholarship_id'];
$amount         = (float)$_POST['amount'];

$payment_id   = uniqid('PAY');
$payment_date = date('Y-m-d');
$method       = "Bank Transfer";

$conn->begin_transaction();

try {
    /* 1️⃣  find or create award row */
    $aw = $conn->prepare("
        SELECT award_id
        FROM scholarship_awarded
        WHERE student_id = ? AND scholarship_id = ?
    ");
    $aw->bind_param('ss', $student_id, $scholarship_id);
    $aw->execute();
    $aw->store_result();
    $award_id = null;
    if ($aw->num_rows) {
        $aw->bind_result($award_id);
        $aw->fetch();
    }
    $aw->close();

    if (!$award_id) {                            // create if missing
        $award_id = uniqid('AWD');
        $insA = $conn->prepare("
            INSERT INTO scholarship_awarded
                  (award_id, student_id, scholarship_id, award_date)
            VALUES (?,?,?,?)
        ");
        $insA->bind_param('ssss',
            $award_id, $student_id, $scholarship_id, $payment_date
        );
        $insA->execute();
        $insA->close();
    }

    /* 2️⃣  insert payment */
    $pay = $conn->prepare("
        INSERT INTO payment
              (payment_id, award_id, payment_date, method, amount)
        VALUES (?,?,?,?,?)
    ");
    $pay->bind_param('ssssd',
        $payment_id, $award_id, $payment_date, $method, $amount
    );
    $pay->execute();
    $pay->close();

    /* 3️⃣  (optional) mark application as PAID so it leaves the table */
    $app = $conn->prepare("
        UPDATE APPLICATION
        SET status = 'paid'
        WHERE application_id = ?
    ");
    $app->bind_param('s', $application_id);
    $app->execute();
    $app->close();

    /* 4️⃣  notify student */
    $notification_id = uniqid('NTF');
    $msg   = "A payment of Rs. $amount has been made for your scholarship.";
    $ntype = 'payment';
    $read  = 0;                                  // 🟢 must be passed by ref

    $ntf = $conn->prepare("
        INSERT INTO notification
              (notification_id, student_id, message, sent_date, type, read_status)
        VALUES (?,?,?,?,?,?)
    ");
    $ntf->bind_param('sssssi',
        $notification_id, $student_id, $msg, $payment_date, $ntype, $read
    );
    $ntf->execute();
    $ntf->close();

    $conn->commit();
    header("Location: coordinator.php?payment=success");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "<h3 style='color:red'>Payment failed: ".$e->getMessage()."</h3>";
}
