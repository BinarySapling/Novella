<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
  
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

 
    $pdo->exec("CREATE DATABASE IF NOT EXISTS book_explorer");
    echo "Database 'book_explorer' created or already exists.<br>";

    $pdo->exec("USE book_explorer");
    echo "Selected database 'book_explorer'.<br>";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS reviews, read_books, reading_goals, users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

   
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table 'users' created successfully.<br>";

   
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            goal_type ENUM('weekly', 'monthly', 'yearly') NOT NULL,
            goal_count INT NOT NULL DEFAULT 0,
            books_remaining INT NOT NULL DEFAULT 0,
            period_start DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_goal (user_id, goal_type, period_start)
        )
    ");
    echo "Table 'reading_goals' created successfully.<br>";

   
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS read_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            book_olid VARCHAR(50) NOT NULL,
            status ENUM('want_to_read', 'currently_reading', 'read') DEFAULT 'read',
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_book_per_user (user_id, book_olid)
        )
    ");
    echo "Table 'read_books' created successfully.<br>";


    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            book_olid VARCHAR(50) NOT NULL,
            review TEXT,
            rating TINYINT CHECK (rating BETWEEN 1 AND 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_review_per_user (user_id, book_olid)
        )
    ");
    echo "Table 'reviews' created successfully.<br>";

    echo "Database and tables created successfully!";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
}
?>
