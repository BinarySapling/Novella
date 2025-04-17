<?php
session_start();
require_once 'php/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session, redirecting to login.php");
    header("Location: login.php");
    exit;
}

// Validate required parameters
if (!isset($_GET['olid'])) {
    error_log("No olid provided, redirecting to index.php");
    header("Location: dashboard.php");
    exit;
}

// Initialize variables
$olid = filter_var($_GET['olid'], FILTER_SANITIZE_STRING);
$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : '';
unset($_SESSION['status_message']);

// Fetch user data
$user = getUserData($pdo, $user_id);
$base_url = "http://localhost/book_explorer/"; // Match index.php and profile.php
$profile_picture = "https://via.placeholder.com/40";
if (!empty($user['profile_picture'])) {
    $file_path = "Uploads/profile_pictures/" . $user['profile_picture'];
    error_log("Checking profile picture: $file_path");
    if (file_exists($file_path)) {
        $profile_picture = $base_url . $file_path . "?v=" . time();
        error_log("Profile picture set: $profile_picture");
    } else {
        error_log("Profile picture file not found: $file_path");
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $result = handleReviewSubmission($pdo, $user_id, $olid);
    if (isset($result['error'])) {
        $errors[] = $result['error'];
    } else {
        $success_message = "Your review has been submitted!";
    }
}

// Fetch book data from OpenLibrary API
$book_data = fetchBookData($olid);

// Get user's current reading status
$current_status = getCurrentStatus($pdo, $user_id, $olid);
error_log("Current status for olid $olid: " . ($current_status ?: 'none'));

// Fetch reviews for this book
$reviews = getBookReviews($pdo, $olid);

/**
 * Fetch user data from database
 */
function getUserData($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            error_log("No user found for ID: $user_id");
            header("Location: logout.php");
            exit;
        }
        return $user;
    } catch (PDOException $e) {
        error_log("Database error in getUserData: " . $e->getMessage());
        return ['username' => 'User', 'profile_picture' => null];
    }
}

/**
 * Handle review submission
 */
function handleReviewSubmission($pdo, $user_id, $olid) {
    $review = trim($_POST['review']);
    $rating = filter_input(INPUT_POST, 'star-radio', FILTER_VALIDATE_INT);
    
    if (empty($review)) {
        return ['error' => 'Please enter a review'];
    }
    
    if ($rating < 1 || $rating > 5) {
        return ['error' => 'Please select a valid rating'];
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, book_olid, review, rating) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                review = VALUES(review), 
                rating = VALUES(rating), 
                created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$user_id, $olid, $review, $rating]);
        error_log("Review submitted for olid $olid by user $user_id");
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        return ['error' => 'An error occurred while submitting your review'];
    }
}

/**
 * Fetch JSON data from an API endpoint
 */
function fetchJson($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Novella/1.0 (https://novella.example.com)');
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || $response === false) {
        error_log("Failed to fetch API data from $url, HTTP code: $http_code");
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetch book data from OpenLibrary API
 */
function fetchBookData($olid) {
    $api_url = "https://openlibrary.org/works/$olid.json";
    $book_data = fetchJson($api_url);
    
    if (!$book_data) {
        return [
            'title' => 'Book Not Found',
            'cover_url' => 'https://via.placeholder.com/300x450?text=Not+Found',
            'description' => 'We could not find this book. It may have been removed or the ID might be incorrect.',
            'author' => 'Unknown',
            'first_publish_date' => 'Unknown',
            'subjects' => []
        ];
    }
    
    // Process book data
    $title = $book_data['title'] ?? "Unknown Title";
    $cover_url = isset($book_data['covers'][0]) ? 
        "https://covers.openlibrary.org/b/id/{$book_data['covers'][0]}-L.jpg" : 
        "https://via.placeholder.com/300x450";
        
    $description = $book_data['description'] ?? "No description available.";
    if (is_array($description)) $description = $description['value'];
    
    $first_publish_date = $book_data['first_publish_date'] ?? "Unknown";
    
    // Process authors
    $author_names = [];
    if (isset($book_data['authors']) && is_array($book_data['authors'])) {
        foreach ($book_data['authors'] as $author) {
            $author_key = $author['author']['key'] ?? null;
            if ($author_key) {
                $author_data = fetchJson("https://openlibrary.org$author_key.json");
                if ($author_data && isset($author_data['name'])) {
                    $author_names[] = $author_data['name'];
                }
            }
        }
    }
    $author = !empty($author_names) ? implode(", ", $author_names) : "Unknown Author";
    
    // Process subjects
    $subjects = array_slice($book_data['subjects'] ?? [], 0, 5);
    
    return [
        'title' => $title,
        'cover_url' => $cover_url,
        'description' => $description,
        'author' => $author,
        'first_publish_date' => $first_publish_date,
        'subjects' => $subjects
    ];
}

/**
 * Get user's current reading status for this book
 */
function getCurrentStatus($pdo, $user_id, $olid) {
    try {
        $stmt = $pdo->prepare("SELECT status FROM read_books WHERE user_id = ? AND book_olid = ?");
        $stmt->execute([$user_id, $olid]);
        $status = $stmt->fetchColumn();
        return $status ?: null;
    } catch (PDOException $e) {
        error_log("Error fetching reading status: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all reviews for this book
 */
function getBookReviews($pdo, $olid) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.review, r.rating, u.username, r.created_at 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.book_olid = ? 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$olid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching reviews: " . $e->getMessage());
        return [];
    }
}

// Extract book data for easier template access
$title = $book_data['title'];
$cover_url = $book_data['cover_url'];
$description = $book_data['description'];
$author = $book_data['author'];
$first_publish_date = $book_data['first_publish_date'];
$subjects = $book_data['subjects'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Novella</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF8383;
            --secondary: #FFF574;
            --accent: #A1D6CB;
            --light: #A19AD3;
            --dark: #2D2A40;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffeffd, #f0f4ff);
            color: var(--dark);
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        
        .glass-card {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .status-btn {
            transition: all 0.3s ease;
            border-radius: 50px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .status-btn.active {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .status-btn-want_to_read {
            background-color: var(--light);
            color: white;
        }

        .status-btn-want_to_read.active {
            background-color: #9089c8;
        }

        .status-btn-currently_reading {
            background-color: var(--primary);
            color: white;
        }

        .status-btn-currently_reading.active {
            background-color: #ff6b6b;
        }

        .status-btn-read {
            background-color: var(--accent);
            color: white;
        }

        .status-btn-read.active {
            background-color: #8cc4b8;
        }

        /* Star Rating Styles */
        .wrapper {
            max-width: 65ch;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .call-to-action-text {
            margin: 2rem 0;
            text-align: left;
        }

        .star-wrap {
            width: max-content;
            margin: 0 0 0 0; /* Align left by removing auto margin */
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem; /* Space between stars */
        }

        .star-label.hidden {
            display: none;
        }

        .star-label {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 2rem; /* Reduced from 4rem */
            height: 2rem; /* Reduced from 4rem */
        }

        @media (min-width: 840px) {
            .star-label {
                width: 3rem; /* Reduced from 6rem */
                height: 3rem; /* Reduced from 6rem */
            }
        }

        .star-shape {
            background-color: gold;
            width: 80%;
            height: 80%;
            clip-path: polygon(
                50% 0%,
                61% 35%,
                98% 35%,
                68% 57%,
                79% 91%,
                50% 70%,
                21% 91%,
                32% 57%,
                2% 35%,
                39% 35%
            );
        }

        .star:checked + .star-label ~ .star-label .star-shape {
            background-color: lightgray;
        }

        .star {
            position: fixed;
            opacity: 0;
            left: -90000px;
        }

        .star:focus + .star-label {
            outline: 2px dotted black;
        }

        .skip-button {
            display: block;
            width: 1.5rem; /* Reduced from 2rem */
            height: 1.5rem; /* Reduced from 2rem */
            border-radius: 0.75rem; /* Adjusted for smaller size */
            position: absolute;
            top: -1.5rem; /* Adjusted for smaller size */
            right: -0.75rem; /* Adjusted for smaller size */
            text-align: center;
            line-height: 1.5rem; /* Adjusted for smaller size */
            font-size: 1.5rem; /* Reduced from 2rem */
            background-color: rgba(255, 255, 255, 0.1);
        }

        .skip-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        #skip-star:checked ~ .skip-button {
            display: none;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#ffeffd] to-[#f0f4ff] text-gray-900 p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Back to Dashboard Button -->
        <div class="mt-4 mb-8">
            <a href="dashboard.php" 
               class="inline-flex items-center gap-2 bg-[var(--light)] hover:bg-[#9089c8] text-white px-6 py-3 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded glass-card" role="alert">
            <p><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded glass-card" role="alert">
            <?php foreach ($errors as $error): ?>
            <p><i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Book Details Section -->
        <div class="glass-card p-8 mb-8">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Book Cover -->
                <div class="flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($cover_url); ?>" 
                         class="w-48 rounded-lg shadow-lg transform transition hover:scale-105" 
                         alt="<?php echo htmlspecialchars($title); ?> book cover">
                </div>
                
                <!-- Book Info -->
                <div class="flex-grow space-y-4">
                    <h1 class="text-4xl font-bold text-[var(--dark)]">
                        <?php echo htmlspecialchars($title); ?>
                    </h1>
                    <p class="text-xl text-[var(--light)]">
                        by <?php echo htmlspecialchars($author); ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        First published: <?php echo htmlspecialchars($first_publish_date); ?>
                    </p>
                    <div class="bg-white/50 rounded-lg p-4">
                        <p class="text-gray-700 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($description)); ?>
                        </p>
                    </div>
                    
                    <!-- Reading Status Section -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-700">Reading status:</span>
                            <span class="<?php echo getStatusClass($current_status); ?> px-3 py-1 rounded-full text-sm">
                                <?php echo $current_status ? str_replace('_', ' ', ucwords($current_status)) : 'Not started'; ?>
                            </span>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <form method="POST" action="update_status.php" onsubmit="debugForm(this)">
                                <input type="hidden" name="book_olid" value="<?php echo htmlspecialchars($olid); ?>">
                                <input type="hidden" name="status" value="want_to_read">
                                <button type="submit" 
                                        class="status-btn status-btn-want_to_read <?php echo $current_status === 'want_to_read' ? 'active' : ''; ?>">
                                    <i class="fas fa-bookmark mr-1"></i> Want to Read
                                </button>
                            </form>
                            <form method="POST" action="update_status.php" onsubmit="debugForm(this)">
                                <input type="hidden" name="book_olid" value="<?php echo htmlspecialchars($olid); ?>">
                                <input type="hidden" name="status" value="currently_reading">
                                <button type="submit" 
                                        class="status-btn status-btn-currently_reading <?php echo $current_status === 'currently_reading' ? 'active' : ''; ?>">
                                    <i class="fas fa-book-open mr-1"></i> Currently Reading
                                </button>
                            </form>
                            <form method="POST" action="update_status.php" onsubmit="debugForm(this)">
                                <input type="hidden" name="book_olid" value="<?php echo htmlspecialchars($olid); ?>">
                                <input type="hidden" name="status" value="read">
                                <button type="submit" 
                                        class="status-btn status-btn-read <?php echo $current_status === 'read' ? 'active' : ''; ?>">
                                    <i class="fas fa-check mr-1"></i> Completed
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Subjects -->
                    <?php if (!empty($subjects)): ?>
                    <div class="flex gap-2 flex-wrap">
                        <?php foreach ($subjects as $subject): ?>
                            <span class="bg-[var(--primary)] text-white px-3 py-1 rounded-full text-sm">
                                <?php echo htmlspecialchars($subject); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Review Form Section -->
        <div class="glass-card p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-[var(--dark)]">Leave a Review</h2>
            <form method="POST" action="#" name="star-rating-form" class="space-y-4">
                <div>
                    <label for="review" class="block text-gray-700 mb-2">Your thoughts</label>
                    <textarea 
                        id="review"
                        name="review" 
                        rows="4" 
                        class="w-full border border-[var(--light)] p-4 rounded-lg focus:ring-2 focus:ring-[var(--primary)] focus:border-transparent resize-none transition"
                        placeholder="Share your thoughts about this book..."></textarea>
                </div>
                
                <div>
                    <label for="rating" class="call-to-action-text">Select a rating:</label>
                    <div class="star-wrap">
                        <input class="star" checked type="radio" value="-1" id="skip-star" name="star-radio" autocomplete="off" />
                        <label class="star-label hidden"></label>
                        <input class="star" type="radio" id="st-1" value="1" name="star-radio" autocomplete="off" />
                        <label class="star-label" for="st-1">
                            <div class="star-shape"></div>
                        </label>
                        <input class="star" type="radio" id="st-2" value="2" name="star-radio" autocomplete="off" />
                        <label class="star-label" for="st-2">
                            <div class="star-shape"></div>
                        </label>
                        <input class="star" type="radio" id="st-3" value="3" name="star-radio" autocomplete="off" />
                        <label class="star-label" for="st-3">
                            <div class="star-shape"></div>
                        </label>
                        <input class="star" type="radio" id="st-4" value="4" name="star-radio" autocomplete="off" />
                        <label class="star-label" for="st-4">
                            <div class="star-shape"></div>
                        </label>
                        <input class="star" type="radio" id="st-5" value="5" name="star-radio" autocomplete="off" />
                        <label class="star-label" for="st-5">
                            <div class="star-shape"></div>
                        </label>
                        <label class="skip-button" for="skip-star">
                            ×
                        </label>
                    </div>
                </div>
                
                <button type="submit" 
                        name="submit_review" 
                        class="bg-[var(--primary)] hover:bg-[#ff6b6b] text-white px-6 py-3 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Review
                </button>
            </form>
            <p id="result">Not chosen</p>
        </div>

        <!-- Reviews Section -->
        <div class="glass-card p-8">
            <h2 class="text-2xl font-bold mb-6 text-[var(--dark)]">User Reviews</h2>
            <?php if (!empty($reviews)): ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $r): ?>
                        <div class="bg-white/50 rounded-lg p-6 transition-all duration-300 hover:shadow-md">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-[var(--accent)] rounded-full flex items-center justify-center text-white">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <strong class="text-[var(--dark)]">
                                        <?php echo htmlspecialchars($r['username']); ?>
                                    </strong>
                                </div>
                                <span class="rating-stars">
                                    <?php echo str_repeat("★", $r['rating']) . str_repeat("☆", 5 - $r['rating']); ?>
                                </span>
                            </div>
                            <p class="text-gray-700 mb-3">
                                <?php echo nl2br(htmlspecialchars($r['review'])); ?>
                            </p>
                            <small class="text-[var(--light)]">
                                <?php echo date("F j, Y", strtotime($r['created_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="text-[var(--light)] mb-4">
                        <i class="fas fa-book-open text-4xl"></i>
                    </div>
                    <p class="text-gray-600">No reviews yet. Be the first to share your thoughts!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function debugForm(form) {
            console.log('Submitting form:', {
                action: form.action,
                book_olid: form.querySelector('[name="book_olid"]').value,
                status: form.querySelector('[name="status"]').value
            });
        }

        function displayValue() {
            const starVal = document.forms["star-rating-form"]["star-radio"].value;
            const result = document.getElementById("result");
            if (starVal == -1) {
                result.innerText = "Not Chosen";
            } else {
                result.innerText = "You chose: " + starVal + " out of 5.";
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            displayValue();
            const starInputs = document.forms["star-rating-form"]["star-radio"];
            for (let star of starInputs) {
                star.addEventListener("change", () => {
                    displayValue();
                });
            }

            // Preselect rating if form is re-submitted with errors
            const currentRating = <?php echo isset($_POST['star-radio']) && $_POST['star-radio'] >= 1 && $_POST['star-radio'] <= 5 ? (int)$_POST['star-radio'] : -1; ?>;
            if (currentRating >= 1 && currentRating <= 5) {
                document.getElementById(`st-${currentRating}`).checked = true;
                displayValue();
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Helper function to get the appropriate CSS class for a reading status
 */
function getStatusClass($status) {
    switch ($status) {
        case 'currently_reading':
            return 'bg-[var(--primary)] text-white';
        case 'read':
            return 'bg-[var(--accent)] text-white';
        case 'want_to_read':
            return 'bg-[var(--light)] text-white';
        default:
            return 'bg-gray-300 text-gray-700';
    }
}
?>