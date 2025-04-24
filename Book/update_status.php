<?php
session_start();
require_once 'php/db_connect.php';


if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session, redirecting to login.php");
    $_SESSION['status_message'] = "Error: Please log in to update status.";
    header("Location: login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_olid'], $_POST['status'])) {
    error_log("Invalid POST data: book_olid or status missing");
    $_SESSION['status_message'] = "Error: Invalid request.";
    header("Location: book_details.php?olid=" . ($_POST['book_olid'] ?? ''));
    exit;
}

$user_id = $_SESSION['user_id'];
$book_olid = filter_var($_POST['book_olid'], FILTER_SANITIZE_STRING);
$status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

$valid_statuses = ['want_to_read', 'currently_reading', 'read'];
if (!in_array($status, $valid_statuses)) {
    error_log("Invalid status: $status");
    $_SESSION['status_message'] = "Error: Invalid status.";
    header("Location: book_details.php?olid=" . $book_olid);
    exit;
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT status FROM read_books WHERE user_id = ? AND book_olid = ?");
    $stmt->execute([$user_id, $book_olid]);
    $existing_book = $stmt->fetch(PDO::FETCH_ASSOC);


    $is_new_read = ($status === 'read' && (!$existing_book || $existing_book['status'] !== 'read'));

   
    $stmt = $pdo->prepare("
        INSERT INTO read_books (user_id, book_olid, status, added_at) 
        VALUES (?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            status = ?, 
            added_at = NOW()
    ");
    $stmt->execute([$user_id, $book_olid, $status, $status]);
    error_log("Status updated for user $user_id, olid $book_olid to $status");

    
    if ($is_new_read) {
        $current_date = new DateTime();
        $week_start = (clone $current_date)->modify('monday this week')->format('Y-m-d');
        $month_start = (clone $current_date)->modify('first day of this month')->format('Y-m-d');
        $year_start = (clone $current_date)->modify('first day of January this year')->format('Y-m-d');

        error_log("Updating goals for user_id=$user_id, periods: week=$week_start, month=$month_start, year=$year_start");

        // Update books_remaining for matching goals
        $stmt = $pdo->prepare("UPDATE reading_goals SET books_remaining = GREATEST(books_remaining - 1, 0) WHERE user_id = ? AND goal_type IN ('weekly', 'monthly', 'yearly') AND period_start IN (?, ?, ?) AND books_remaining > 0");
        $stmt->execute([$user_id, $week_start, $month_start, $year_start]);

        // Debug: Log affected rows
        $affected_rows = $stmt->rowCount();
        error_log("Updated $affected_rows goals for user_id=$user_id");
    } else {
        error_log("Not updating goals: status=$status, is_new_read=" . ($is_new_read ? 'true' : 'false'));
    }

    
    $pdo->commit();

    $_SESSION['status_message'] = "Status updated to '" . str_replace('_', ' ', ucwords($status)) . "'!";
    header("Location: book_details.php?olid=" . $book_olid);
    exit;
} catch (PDOException $e) {
  
    $pdo->rollBack();
    error_log("Status update error for user $user_id, olid $book_olid: " . $e->getMessage());
    $_SESSION['status_message'] = "Error: Failed to update status. Please try again.";
    header("Location: book_details.php?olid=" . $book_olid);
    exit;
}
?>
