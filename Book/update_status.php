<?php
session_start();
require_once 'php/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session, redirecting to login.php");
    $_SESSION['status_message'] = "Error: Please log in to update status.";
    header("Location: login.php");
    exit;
}

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_olid'], $_POST['status'])) {
    error_log("Invalid POST data: book_olid or status missing");
    $_SESSION['status_message'] = "Error: Invalid request.";
    header("Location: book_details.php?olid=" . ($_POST['book_olid'] ?? ''));
    exit;
}

$user_id = $_SESSION['user_id'];
$book_olid = filter_var($_POST['book_olid'], FILTER_SANITIZE_STRING);
$status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

// Validate status
$valid_statuses = ['want_to_read', 'currently_reading', 'read'];
if (!in_array($status, $valid_statuses)) {
    error_log("Invalid status: $status");
    $_SESSION['status_message'] = "Error: Invalid status.";
    header("Location: book_details.php?olid=" . $book_olid);
    exit;
}

try {
    // Insert or update status
    $stmt = $pdo->prepare("
        INSERT INTO read_books (user_id, book_olid, status, added_at) 
        VALUES (?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            status = ?, 
            added_at = NOW()
    ");
    $stmt->execute([$user_id, $book_olid, $status, $status]);
    error_log("Status updated for user $user_id, olid $book_olid to $status");
    
    $_SESSION['status_message'] = "Status updated to '" . str_replace('_', ' ', ucwords($status)) . "'!";
    header("Location: book_details.php?olid=" . $book_olid);
    exit;
} catch (PDOException $e) {
    error_log("Status update error for user $user_id, olid $book_olid: " . $e->getMessage());
    $_SESSION['status_message'] = "Error: Failed to update status. Please try again.";
    header("Location: book_details.php?olid=" . $book_olid);
    exit;
}
?>