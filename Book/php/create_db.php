<?php
require_once 'db_connect.php';

try {
    // Create Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Users table created successfully.<br>";

    // Create Books table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            olid VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255),
            description TEXT,
            cover_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Books table created successfully.<br>";

    // Create Reviews table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            book_olid VARCHAR(50) NOT NULL,
            review TEXT NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (book_olid) REFERENCES books(olid) ON DELETE CASCADE
        )
    ");
    echo "Reviews table created successfully.<br>";

    // Create Reading Status table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS read_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            book_olid VARCHAR(50) NOT NULL,
            status ENUM('Read', 'Currently Reading', 'Want to Read') NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (book_olid) REFERENCES books(olid) ON DELETE CASCADE
        )
    ");
    echo "Reading Status table created successfully.<br>";

    // Add sample data (optional)
    $pdo->exec("
        INSERT IGNORE INTO users (username, email, password) VALUES
        ('testuser', 'testuser@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "')
    ");
    echo "Sample user added successfully.<br>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
