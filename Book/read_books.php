<?php
session_start();
require_once 'php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$goals_error = null;

try {
    $stmt = $pdo->prepare("SELECT book_olid, status FROM read_books WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $read_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $read_books = [];
    $goals_error = "Error fetching book collection: " . $e->getMessage();
}


$want_to_read = 0;
$currently_reading = 0;
$read = 0;

foreach ($read_books as $book) {
    if ($book['status'] == 'want_to_read') $want_to_read++;
    if ($book['status'] == 'currently_reading') $currently_reading++;
    if ($book['status'] == 'read') $read++;
}


$goals = [
    'weekly' => ['goal_count' => 0, 'books_remaining' => 0, 'completed' => false],
    'monthly' => ['goal_count' => 0, 'books_remaining' => 0, 'completed' => false],
    'yearly' => ['goal_count' => 0, 'books_remaining' => 0, 'completed' => false]
];


try {
    $current_date = new DateTime();
    $week_start = $current_date->modify('monday this week')->format('Y-m-d');
    $month_start = $current_date->modify('first day of this month')->format('Y-m-d');
    $year_start = $current_date->modify('first day of January this year')->format('Y-m-d');

    $stmt = $pdo->prepare("SELECT goal_type, goal_count, books_remaining FROM reading_goals WHERE user_id = ? AND period_start IN (?, ?, ?)");
    $stmt->execute([$user_id, $week_start, $month_start, $year_start]);
    $goal_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($goal_rows as $row) {
        $goals[$row['goal_type']] = [
            'goal_count' => $row['goal_count'],
            'books_remaining' => $row['books_remaining'],
            'completed' => $row['books_remaining'] == 0
        ];
    }

    $stmt = $pdo->prepare("SELECT goal_type, goal_count, books_remaining FROM reading_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $latest_goal = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['goal_type' => '', 'goal_count' => 0, 'books_remaining' => 0];
} catch (PDOException $e) {
    $goals_error = "Unable to fetch reading goals. Please ensure the database is set up correctly (run php/create_db.php).";
    $latest_goal = ['goal_type' => '', 'goal_count' => 0, 'books_remaining' => 0];
}

$completed_goals = array_filter($goals, function($goal) {
    return $goal['completed'];
});
$show_congratulations = !empty($completed_goals) && isset($_SESSION['goal_completed']) && $_SESSION['goal_completed'];
unset($_SESSION['goal_completed']); 
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
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #FF8383;
            --secondary: #FFF574; 
            --accent: #A1D6CB; 
            --light: #A19AD3; 
            --dark: #2D2A40;
            --light-bg: #f0f4ff; 
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #E6BEE6, #DCE4FF); 
            color: var(--dark);
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            filter: blur(1px);
            opacity: 1.0;
        }

        .glass-card {
            backdrop-filter: blur(10px); 
            -webkit-backdrop-filter: blur(10px); 
            background: rgba(255, 255, 255, 0.3); 
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

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            margin-right: 0.5rem;
        }

        .goal-input {
            width: 100px;
            border: 1px solid rgba(45, 42, 64, 0.2);
            border-radius: 8px;
            padding: 0.5rem;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.9);
        }

        .goal-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 131, 131, 0.2);
        }

        .goal-select {
            border: 1px solid rgba(45, 42, 64, 0.2);
            border-radius: 8px;
            padding: 0.5rem;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.9);
            width: 150px;
        }

        .goal-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 131, 131, 0.2);
        }

        .goal-button {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .goal-button:hover {
            background-color: #e67575;
        }

        .error-message {
            background-color: rgba(255, 99, 71, 0.1);
            border: 1px solid rgba(255, 99, 71, 0.3);
            border-radius: 8px;
            padding: 0.75rem;
            color: #d32f2f;
            font-size: 0.9rem;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem;
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .toast-success {
            background-color: #4caf50;
        }

        .toast-error {
            background-color: #f44336;
        }

        .tooltip {
            position: relative;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 140px;
            background-color: var(--dark);
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -70px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Congratulatory Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: popIn 0.5s ease-out;
        }

        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .modal-content h2 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .modal-content p {
            color: white;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .modal-content button {
            background: var(--dark);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .modal-content button:hover {
            background: #3f3b5c;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <!-- Congratulatory Modal -->
    <?php if ($show_congratulations): ?>
        <div id="congratulations-modal" class="modal" style="display: flex;">
            <div class="modal-content">
                <h2>Hooray! Goal Achieved! 🎉</h2>
                <p>
                    <?php
                    $completed_types = array_keys($completed_goals);
                    $first_type = $completed_types[0];
                    $messages = [
                        'weekly' => "You've smashed your weekly reading goal! Keep the momentum going!",
                        'monthly' => "Wow, you conquered your monthly reading goal! You're a reading rockstar!",
                        'yearly' => "Incredible! You've completed your yearly reading goal! Time to celebrate!"
                    ];
                    echo $messages[$first_type];
                    ?>
                </p>
                <button onclick="closeModal()">Continue Reading</button>
            </div>
        </div>
    <?php endif; ?>

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
            <?php if ($goals_error): ?>
                <div class="error-message mb-4"><?php echo htmlspecialchars($goals_error); ?></div>
            <?php endif; ?>
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Reading Stats Section -->
                <div class="w-full lg:w-1/2">
                    <h2 class="text-xl font-bold text-[var(--dark)] mb-4">Reading Stats</h2>
                    <div class="flex flex-col sm:flex-row items-center">
                        <div class="w-full sm:w-1/2">
                            <canvas id="readingStatsChart" height="120"></canvas>
                        </div>
                        <div class="w-full sm:w-1/2 mt-4 sm:mt-0 sm:pl-8">
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #FF8383;"></span>
                                <span class="text-gray-600">Want to Read: <?php echo $want_to_read; ?> book<?php echo $want_to_read !== 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #A19AD3;"></span>
                                <span class="text-gray-600">Currently Reading: <?php echo $currently_reading; ?> book<?php echo $currently_reading !== 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #A1D6CB;"></span>
                                <span class="text-gray-600">Read: <?php echo $read; ?> book<?php echo $read !== 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Set Reading Goals Section -->
                <div class="w-full lg:w-1/2">
                    <h2 class="text-xl font-bold text-[var(--dark)] mb-4">Set Reading Goals</h2>
                    <div class="space-y-6">
                        <!-- Goal Setter -->
                        <div class="glass-card p-4">
                            <label for="goal-type" class="text-gray-600 font-medium block mb-2">Select Goal Period</label>
                            <div class="flex items-center gap-3">
                                <select id="goal-type" class="goal-select" <?php echo $goals_error ? 'disabled' : ''; ?>>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                                <div class="tooltip">
                                    <input type="number" id="goal-count" class="goal-input" min="0" step="1" placeholder="Books" <?php echo $goals_error ? 'disabled' : ''; ?>>
                                    <span class="tooltip-text">Number of books for the selected period</span>
                                </div>
                                <button class="goal-button" onclick="updateGoal(document.getElementById('goal-type').value, document.getElementById('goal-count').value)" <?php echo $goals_error ? 'disabled' : ''; ?>>Set Goal</button>
                            </div>
                        </div>
                        <!-- Active Goal Display -->
                        <div class="glass-card p-4">
                            <h3 class="text-lg font-semibold text-[var(--dark)] mb-2">Active Goal</h3>
                            <p id="active-goal-text" class="text-gray-600">
                                <?php if ($latest_goal['goal_type']): ?>
                                    <?php echo ucfirst($latest_goal['goal_type']); ?> Goal: <?php echo $latest_goal['goal_count']; ?> book<?php echo $latest_goal['goal_count'] !== 1 ? 's' : ''; ?>, <?php echo $latest_goal['books_remaining']; ?> remaining
                                <?php else: ?>
                                    No active goal set
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card p-6">
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
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                    foreach ($read_books as $book):
                        $olid = $book['book_olid'];
                        $api_url = "https://openlibrary.org/works/$olid.json";
                        curl_setopt($ch, CURLOPT_URL, $api_url);
                        $response = curl_exec($ch);
                        $book_data = $response ? json_decode($response, true) : null;

                        $cover_url = isset($book_data['covers'][0]) ? "https://covers.openlibrary.org/b/id/{$book_data['covers'][0]}-M.jpg" : "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1yg_rIUE_FzrkgJGIrpCu_e45OFLXH5GByg&s";
                        $title = $book_data['title'] ?? "Unknown Title";

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
    </div>

    <script>
      
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                "particles": {
                    "number": {
                        "value": 80,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": ["#F06262", "#4DB6AC", "#7B5EAB"]
                    },
                    "opacity": {
                        "value": 0.5,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 1,
                            "opacity_min": 0.3,
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
                        "distance": 190,
                        "color": "#4DB6AC",
                        "opacity": 0.4,
                        "width": 0.5
                    },
                    "move": {
                        "enable": true,
                        "speed": 0.7,
                        "direction": "none",
                        "random": true,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false
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
                            "size": 4,
                            "duration": 2,
                            "opacity": 0.8,
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

       
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-button');
            const books = document.querySelectorAll('.book-card');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const status = this.getAttribute('data-status');
                    tabs.forEach(t => {
                        t.classList.remove('tab-button-active');
                        t.classList.add('tab-button-inactive');
                    });
                    this.classList.remove('tab-button-inactive');
                    this.classList.add('tab-button-active');
                    books.forEach(book => {
                        const bookStatus = book.getAttribute('data-status');
                        book.style.display = (status === 'all' || status === bookStatus) ? 'block' : 'none';
                    });
                });
            });
        });

      
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('readingStatsChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Want to Read', 'Currently Reading', 'Read'],
                    datasets: [{
                        data: [<?php echo $want_to_read; ?>, <?php echo $currently_reading; ?>, <?php echo $read; ?>],
                        backgroundColor: ['#FF8383', '#A19AD3', '#A1D6CB'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed || 0;
                                    return `${label}: ${value} book${value !== 1 ? 's' : ''}`;
                                }
                            }
                        }
                    }
                }
            });
        });

      
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '1';
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }, 100);
        }

     
        function updateGoal(goalType, goalCount) {
            if (!goalType || !['weekly', 'monthly', 'yearly'].includes(goalType)) {
                showToast('Please select a valid goal period.', 'error');
                return;
            }
            if (!goalCount || isNaN(goalCount) || goalCount < 0) {
                showToast('Please enter a valid non-negative number.', 'error');
                return;
            }
            const parsedGoal = parseInt(goalCount);
            if (parsedGoal > 1000) {
                showToast('Goal cannot exceed 1000 books.', 'error');
                return;
            }

            const url = './updated_goal.php';
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `goal_type=${encodeURIComponent(goalType)}&goal_count=${encodeURIComponent(parsedGoal)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} (${response.statusText})`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(`Successfully set ${goalType} goal to ${parsedGoal} books!`, 'success');
                    const activeGoalText = document.getElementById('active-goal-text');
                    activeGoalText.textContent = `${goalType.charAt(0).toUpperCase() + goalType.slice(1)} Goal: ${data.goal_count} book${data.goal_count !== 1 ? 's' : ''}, ${data.books_remaining} remaining`;
                    document.getElementById('goal-count').value = '';
                } else {
                    showToast('Failed to update goal: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred while updating the goal: ' + error.message, 'error');
            });
        }

     
        function closeModal() {
            const modal = document.getElementById('congratulations-modal');
            if (modal) {
                modal.style.display = 'none';
                // Store completion in localStorage to prevent re-display
                <?php foreach ($completed_goals as $type => $goal): ?>
                    localStorage.setItem('goal_completed_<?php echo $type; ?>_<?php echo $week_start . '_' . $month_start . '_' . $year_start; ?>', 'true');
                <?php endforeach; ?>
            }
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('congratulations-modal')) {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#FF8383', '#A1D6CB', '#A19AD3']
                });
            }
        });
    </script>
</body>
</html>
