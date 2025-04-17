<?php
session_start();
require_once 'php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT book_olid, status FROM read_books WHERE user_id = ?");
$stmt->execute([$user_id]);
$read_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collection - Novella</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <style>
        :root {
            --primary: #FF8383; /* Coral Pink */
            --secondary: #FFF574; /* Bright Yellow */
            --accent: #A1D6CB; /* Mint Green */
            --light: #A19AD3; /* Lavender */
            --dark: #2D2A40; /* Deep Purple for contrast */
            --light-bg: #f0f4ff; /* Light blue-ish background */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffeffd, #f0f4ff);
            color: var(--dark);
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .glass-card {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .book-card {
            transition: all 0.3s ease;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.85);
            box-shadow: 0 10px 20px rgba(45, 42, 64, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(45, 42, 64, 0.15);
        }

        .book-cover {
            transition: all 0.4s ease;
            filter: brightness(0.95);
        }

        .book-card:hover .book-cover {
            filter: brightness(1.1);
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            border-radius: 50px;
            padding: 5px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
        }

        .status-want_to_read {
            background-color: var(--primary);
        }

        .status-currently_reading {
            background-color: var(--light);
        }

        .status-read {
            background-color: var(--accent);
        }

        .tab-button {
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab-button-active {
            background-color: var(--dark);
            color: white;
        }

        .tab-button-inactive {
            background-color: var(--light-bg);
            color: var(--dark);
        }

        .tab-button-inactive:hover {
            background-color: rgba(45, 42, 64, 0.1);
        }

        .book-count {
            background-color: var(--light-bg);
            border-radius: 50px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <div class="container mx-auto px-4 sm:px-6 py-12">
        <nav class="flex justify-between items-center mb-8">
            <a href="dashboard.php" class="flex items-center text-[var(--dark)] hover:text-[var(--primary)] transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                <span class="font-medium">Back to Explore</span>
            </a>
            <a href="dashboard.php" class="flex items-center">
                <i class="fas fa-book-open text-2xl text-[var(--primary)] mr-3"></i>
                <span class="font-bold text-xl text-[var(--dark)]">Novella</span>
            </a>
        </nav>

        <div class="glass-card p-6 mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[var(--dark)]">My Book Collection</h1>
                    <p class="text-gray-600 mt-1">Track and manage your reading journey</p>
                </div>
                <div class="book-count mt-2 sm:mt-0">
                    <?php echo count($read_books); ?> Books
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-8">
                <button class="tab-button tab-button-active" data-status="all">All Books</button>
                <button class="tab-button tab-button-inactive" data-status="want_to_read">Want to Read</button>
                <button class="tab-button tab-button-inactive" data-status="currently_reading">Currently Reading</button>
                <button class="tab-button tab-button-inactive" data-status="read">Read</button>
            </div>

            <?php if (empty($read_books)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4 text-gray-300">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-600">Your collection is empty</h3>
                    <p class="text-gray-500 mt-2 mb-6">Start adding books to your collection from the explore page</p>
                    <a href="dashboard.php" class="inline-block bg-[var(--primary)] hover:bg-opacity-90 text-white font-medium py-2 px-6 rounded-full transition-all">
                        Discover Books
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <?php
                    // Initialize cURL with SSL verification disabled for local development
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable host verification

                    foreach ($read_books as $book):
                        $olid = $book['book_olid'];
                        $api_url = "https://openlibrary.org/works/$olid.json";

                        // Fetch book data
                        curl_setopt($ch, CURLOPT_URL, $api_url);
                        $response = curl_exec($ch);
                        $book_data = $response ? json_decode($response, true) : null;

                        $cover_url = isset($book_data['covers'][0]) ? "https://covers.openlibrary.org/b/id/{$book_data['covers'][0]}-M.jpg" : "https://via.placeholder.com/150x220";
                        $title = $book_data['title'] ?? "Unknown Title";

                        // Fetch authors
                        $author_names = [];
                        if (isset($book_data['authors'])) {
                            foreach ($book_data['authors'] as $author) {
                                $author_key = $author['author']['key'];
                                curl_setopt($ch, CURLOPT_URL, "https://openlibrary.org$author_key.json");
                                $author_response = curl_exec($ch);
                                $author_data = $author_response ? json_decode($author_response, true) : null;
                                if ($author_data && isset($author_data['name'])) {
                                    $author_names[] = $author_data['name'];
                                }
                            }
                        }
                        $author = !empty($author_names) ? implode(", ", $author_names) : "Unknown Author";
                    ?>
                        <a href="book_details.php?olid=<?php echo htmlspecialchars($olid); ?>" class="book-card" data-status="<?php echo $book['status']; ?>">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($cover_url); ?>"
                                     alt="<?php echo htmlspecialchars($title); ?>"
                                     class="book-cover w-full h-64 object-cover rounded-t-lg">
                                <div class="status-badge status-<?php echo $book['status']; ?>">
                                    <?php
                                    $status_text = str_replace('_', ' ', $book['status']);
                                    echo ucwords($status_text);
                                    ?>
                                </div>
                                <?php if ($book['status'] == 'currently_reading'): ?>
                                    <div class="absolute bottom-0 left-0 w-full bg-[var(--dark)] bg-opacity-70 py-1 px-3">
                                        <div class="h-1 bg-gray-300 rounded overflow-hidden">
                                            <div class="bg-[var(--accent)] h-full" style="width: <?php echo rand(10, 90); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="font-medium text-[var(--dark)] line-clamp-2 h-12"><?php echo htmlspecialchars($title); ?></h3>
                                <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($author); ?></p>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="<?php echo $i < rand(3, 5) ? 'fas' : 'far'; ?> fa-star text-yellow-400 text-xs"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <?php if ($book['status'] == 'read'): ?>
                                            <i class="fas fa-check-circle text-[var(--accent)] mr-1"></i> Completed
                                        <?php elseif ($book['status'] == 'currently_reading'): ?>
                                            <i class="fas fa-book-open text-[var(--light)] mr-1"></i> In Progress
                                        <?php else: ?>
                                            <i class="fas fa-bookmark text-[var(--primary)] mr-1"></i> Saved
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php curl_close($ch); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card p-6">
            <h2 class="text-xl font-bold text-[var(--dark)] mb-4">Reading Stats</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php
                $want_to_read = 0;
                $currently_reading = 0;
                $read = 0;

                foreach ($read_books as $book) {
                    if ($book['status'] == 'want_to_read') $want_to_read++;
                    if ($book['status'] == 'currently_reading') $currently_reading++;
                    if ($book['status'] == 'read') $read++;
                }
                ?>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <div class="flex justify-between items-center">
                        <h3 class="text-gray-600 font-medium">Want to Read</h3>
                        <span class="text-xl font-bold text-[var(--primary)]"><?php echo $want_to_read; ?></span>
                    </div>
                    <div class="mt-2 h-1 bg-gray-200 rounded-full">
                        <div class="bg-[var(--primary)] h-full rounded-full" style="width: <?php echo count($read_books) > 0 ? ($want_to_read / count($read_books) * 100) : 0; ?>%"></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <div class="flex justify-between items-center">
                        <h3 class="text-gray-600 font-medium">Currently Reading</h3>
                        <span class="text-xl font-bold text-[var(--light)]"><?php echo $currently_reading; ?></span>
                    </div>
                    <div class="mt-2 h-1 bg-gray-200 rounded-full">
                        <div class="bg-[var(--light)] h-full rounded-full" style="width: <?php echo count($read_books) > 0 ? ($currently_reading / count($read_books) * 100) : 0; ?>%"></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <div class="flex justify-between items-center">
                        <h3 class="text-gray-600 font-medium">Completed</h3>
                        <span class="text-xl font-bold text-[var(--accent)]"><?php echo $read; ?></span>
                    </div>
                    <div class="mt-2 h-1 bg-gray-200 rounded-full">
                        <div class="bg-[var(--accent)] h-full rounded-full" style="width: <?php echo count($read_books) > 0 ? ($read / count($read_books) * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Particles.js configuration
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                "particles": {
                    "number": {
                        "value": 40,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": ["#FF8383", "#A1D6CB", "#A19AD3"]
                    },
                    "shape": {
                        "type": ["circle"],
                        "stroke": {
                            "width": 0,
                            "color": "#000000"
                        }
                    },
                    "opacity": {
                        "value": 0.2,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 1,
                            "opacity_min": 0.1,
                            "sync": false
                        }
                    },
                    "size": {
                        "value": 4,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 1,
                            "size_min": 0.1,
                            "sync": false
                        }
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#A19AD3",
                        "opacity": 0.2,
                        "width": 1
                    },
                    "move": {
                        "enable": true,
                        "speed": 0.5,
                        "direction": "none",
                        "random": true,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false,
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": {
                        "onhover": {
                            "enable": true,
                            "mode": "bubble"
                        },
                        "onclick": {
                            "enable": true,
                            "mode": "push"
                        },
                        "resize": true
                    },
                    "modes": {
                        "bubble": {
                            "distance": 150,
                            "size": 6,
                            "duration": 2,
                            "opacity": 0.6,
                            "speed": 3
                        },
                        "push": {
                            "particles_nb": 3
                        }
                    }
                },
                "retina_detect": true
            });
        });

        // Tab filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-button');
            const books = document.querySelectorAll('.book-card');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const status = this.getAttribute('data-status');

                    // Update active tab
                    tabs.forEach(t => {
                        t.classList.remove('tab-button-active');
                        t.classList.add('tab-button-inactive');
                    });
                    this.classList.remove('tab-button-inactive');
                    this.classList.add('tab-button-active');

                    // Filter books
                    books.forEach(book => {
                        const bookStatus = book.getAttribute('data-status');
                        if (status === 'all' || status === bookStatus) {
                            book.style.display = 'block';
                        } else {
                            book.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>