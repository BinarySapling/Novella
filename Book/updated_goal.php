<?php
session_start();
require_once 'php/db_connect.php';

header('Content-Type: application/json');


ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$goal_type = $_POST['goal_type'] ?? '';
$goal_count = $_POST['goal_count'] ?? '';

if (empty($goal_type) || !in_array($goal_type, ['weekly', 'monthly', 'yearly'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid goal type']);
    exit;
}

if (empty($goal_count) || !is_numeric($goal_count) || $goal_count < 0 || $goal_count > 1000) {
    echo json_encode(['success' => false, 'message' => 'Invalid goal count']);
    exit;
}

$goal_count = (int)$goal_count;

try {
   
    $current_date = new DateTime();
    switch ($goal_type) {
        case 'weekly':
            $period_start = $current_date->modify('monday this week')->format('Y-m-d');
            break;
        case 'monthly':
            $period_start = $current_date->modify('first day of this month')->format('Y-m-d');
            break;
        case 'yearly':
            $period_start = $current_date->modify('first day of January this year')->format('Y-m-d');
            break;
    }

 
    $pdo->beginTransaction();

  
    $stmt = $pdo->prepare("SELECT id, goal_count, books_remaining FROM reading_goals WHERE user_id = ? AND goal_type = ? AND period_start = ?");
    $stmt->execute([$user_id, $goal_type, $period_start]);
    $existing_goal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_goal) {
      
        $stmt = $pdo->prepare("UPDATE reading_goals SET goal_count = ?, books_remaining = ? WHERE id = ?");
        $stmt->execute([$goal_count, $goal_count, $existing_goal['id']]);
    } else {
      
        $stmt = $pdo->prepare("INSERT INTO reading_goals (user_id, goal_type, goal_count, books_remaining, period_start) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $goal_type, $goal_count, $goal_count, $period_start]);
    }

  
    $pdo->commit();

   
    
    echo json_encode([
        'success' => true,
        'message' => 'Goal updated successfully',
        'goal_type' => $goal_type,
        'goal_count' => $goal_count,
        'books_remaining' => $goal_count
    ]);
} catch (Exception $e) {
  
    $pdo->rollBack();
    error_log("Error in updated_goal.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
