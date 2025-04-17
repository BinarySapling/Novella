<!-- <?php
require_once 'php/db_connect.php';

try {
    // Add profile_picture column to users table if it doesn't exist
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL 
        AFTER password
    ");
    echo "Profile picture column added successfully!";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Column already exists
        echo "Profile picture column already exists.";
    } else {
        die("Error: " . $e->getMessage());
    }
}
?> -->