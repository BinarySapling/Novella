<?php
session_start();
require_once 'php/db_connect.php';

$base_url = "http://localhost/book_explorer/";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT username, email, created_at, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        error_log("User found: " . json_encode($user));
        
        // Set profile picture path (use lowercase 'uploads' to match directory)
        $profile_picture_path = $user['profile_picture'] 
            ? "Uploads/profile_pictures/" . $user['profile_picture'] 
            : "https://cdn-icons-png.flaticon.com/512/12225/12225935.png";

        // Check if the file exists
        $full_file_path = __DIR__ . "/Uploads/profile_pictures/" . $user['profile_picture'];
        if ($user['profile_picture'] && !file_exists($full_file_path)) {
            error_log("Profile picture file not found: $full_file_path");
            $profile_picture_path = "https://cdn-icons-png.flaticon.com/512/12225/12225935.png"; // Fallback to default
        }

        // Add cache-busting parameter
        $profile_picture = $profile_picture_path . "?v=" . time();

        // Log the image URL for debugging
        error_log("Profile picture URL: $profile_picture");
    } else {
        error_log("No user found for ID: $user_id");
        header("Location: logout.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in dashboard.php: " . $e->getMessage());
    $user = ['username' => 'User', 'email' => '', 'created_at' => ''];
    $profile_picture = "https://cdn-icons-png.flaticon.com/512/12225/12225935.png?v=" . time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novella | Explore Beautiful Stories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <style>
        :root {
            --primary: #FF8383;
            --secondary: #FFF574;
            --accent: #A1D6CB;
            --light: #A19AD3;
            --dark: #2D2A40;
            --light-bg: #f0f4ff;
            --card-bg: rgba(255, 255, 255, 0.85);
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

        .hero-content {
            position: relative;
            z-index: 10;
            backdrop-filter: blur(5px);
            border-radius: 16px;
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(161, 154, 211, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: background-color 0.3s ease;
        }

        .hero-content:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .hero-content h1 {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-content p {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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
            opacity: 0;
            transform: translateY(20px);
            background-color: var(--card-bg);
            box-shadow: 0 10px 20px rgba(45, 42, 64, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .book-card.visible {
            opacity: 1;
            transform: translateY(0);
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

        .tab-highlight {
            position: relative;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .tab-highlight::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 10px;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .tab-highlight:hover::after {
            transform: scaleX(1);
        }

        .fancy-search {
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(161, 154, 211, 0.25);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .fancy-search:focus {
            box-shadow: 0 4px 20px rgba(161, 154, 211, 0.5);
            border: 2px solid var(--accent);
        }

        .btn-primary {
            border-radius: 50px;
            background-color: var(--primary);
            box-shadow: 0 4px 15px rgba(255, 131, 131, 0.3);
            transition: all 0.3s ease;
            color: white;
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 131, 131, 0.5);
            background-color: #ff6b6b;
        }

        .btn-secondary {
            border-radius: 50px;
            background-color: var(--light);
            box-shadow: 0 4px 15px rgba(161, 154, 211, 0.3);
            transition: all 0.3s ease;
            color: white;
            font-weight: 500;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 154, 211, 0.5);
            background-color: #9089c8;
        }

        .btn-outline {
            border-radius: 50px;
            border: 2px solid var(--accent);
            color: var(--accent);
            background-color: transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-outline:hover {
            background-color: var(--accent);
            color: white;
        }

        .scroll-container::-webkit-scrollbar {
            height: 6px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: rgba(161, 154, 211, 0.1);
            border-radius: 10px;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .scroll-container {
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.7s ease-out, transform 0.7s ease-out;
        }

        .scroll-container.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .genre-title {
            position: relative;
            display: inline-block;
        }

        .genre-title::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 40%;
            height: 3px;
            background: var(--primary);
            border-radius: 10px;
        }

        .category-pill {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(161, 154, 211, 0.25);
        }

        .category-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(161, 154, 211, 0.4);
            cursor: pointer;
        }

        .active-pill {
            background-color: var(--accent);
            transform: translateY(-2px);
        }

        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 20px rgba(161, 154, 211, 0.4);
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .scroll-to-top.visible {
            opacity: 1;
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translate(0, 0px); }
            50% { transform: translate(0, -10px); }
            100% { transform: translate(0, 0px); }
        }

        .profile-avatar {
            border-radius: 50%;
            border: 2px solid var(--accent);
            transition: all 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(161, 214, 203, 0.3);
        }

        .mobile-menu {
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: left 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .mobile-menu.active {
            left: 0;
        }

        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .nav-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <div class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </div>

    <header class="py-16 md:py-24 relative z-10">
        <div class="container mx-auto px-6">
            <div class="hero-content p-8 md:p-12 text-center">
                <div class="mb-6 floating">
                    <i class="fas fa-book-open text-5xl text-[var(--primary)]"></i>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6 text-[var(--dark)]">
                    Novella
                </h1>
                <p class="text-lg md:text-xl mb-8 max-w-3xl mx-auto text-[var(--dark)] opacity-80">
                    Discover beautiful stories, magical tales, and exciting adventures!
                </p>
                <div class="flex flex-col md:flex-row justify-center gap-4 max-w-3xl mx-auto">
                    <input id="searchInput" type="text" placeholder="Search titles, authors, genres..."
                           class="fancy-search p-4 w-full md:flex-1 text-[var(--dark)] bg-white/90 focus:outline-none">
                    <button onclick="searchBooks()"
                            class="btn-primary px-8 py-4">
                        <i class="fas fa-search mr-2"></i> Discover Books
                    </button>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <a href="read_books.php" class="btn-primary px-6 py-3">
                        <i class="fas fa-book mr-2"></i> My Collection
                    </a>
                    <a href="profile.php" class="btn-secondary px-6 py-3">
                        <i class="fas fa-user mr-2"></i> My Profile
                    </a>
                    <a href="logout.php" class="btn-outline px-6 py-3">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="sticky top-0 bg-white/90 backdrop-blur-md shadow-md z-50 py-4">
        <div class="container mx-auto px-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <button id="menuToggle" class="text-[var(--dark)] mr-4 lg:hidden">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <i class="fas fa-book-open text-2xl text-[var(--primary)] mr-3"></i>
                    <span class="font-bold text-xl text-[var(--dark)]">Novella</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="profile.php" class="nav-profile text-[var(--dark)] hover:text-[var(--primary)] transition-colors">
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-avatar w-8 h-8">
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <div class="p-6">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-bold text-[var(--dark)]">Menu</h3>
                <button id="closeMenu" class="text-[var(--dark)]">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <a href="profile.php" class="flex items-center p-3 text-[var(--dark)] hover:bg-[var(--primary)]/10 rounded-lg transition-colors">
                    <i class="fas fa-user w-8"></i>
                    <span>My Profile</span>
                </a>
                <a href="read_books.php" class="flex items-center p-3 text-[var(--dark)] hover:bg-[var(--primary)]/10 rounded-lg transition-colors">
                    <i class="fas fa-book w-8"></i>
                    <span>My Collection</span>
                </a>
                <a href="logout.php" class="flex items-center p-3 text-[var(--dark)] hover:bg-[var(--primary)]/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-8"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    <div class="menu-overlay" id="menuOverlay"></div>

    <div class="container mx-auto px-6">
        <div id="categoryResultsContainer" class="mb-12"></div>
        <div id="searchResultsContainer" class="mb-12"></div>

        <div class="glass-card p-6 mb-12">
            <h2 class="text-2xl font-bold mb-6 text-[var(--dark)] genre-title">Trending This Week</h2>
            <div id="trendingContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6"></div>
        </div>

        <div id="genresContainer" class="space-y-12"></div>
    </div>

    <footer class="glass-card mt-16 py-10 px-6 relative z-10">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="transition-all duration-300 hover:transform hover:translate-y-[-5px]">
                    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] relative inline-block">
                        About Novella
                        <span class="absolute bottom-0 left-0 w-1/2 h-0.5 bg-[var(--primary)] transform scale-x-0 transition-transform duration-300 origin-left group-hover:scale-x-100"></span>
                    </h3>
                    <p class="text-gray-600 leading-relaxed">Discover your next favorite book with our curated collection of stories from around the world.</p>
                    <div class="mt-4 flex space-x-4">
                        <a href="#" class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white transition-all duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white transition-all duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white transition-all duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                <div class="transition-all duration-300 hover:transform hover:translate-y-[-5px]">
                    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] relative inline-block">
                        Quick Links
                        <span class="absolute bottom-0 left-0 w-1/2 h-0.5 bg-[var(--primary)] transform scale-x-0 transition-transform duration-300 origin-left group-hover:scale-x-100"></span>
                    </h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 hover:text-[var(--primary)] transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i>About Us</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-[var(--primary)] transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i>Contact</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-[var(--primary)] transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i>Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-[var(--primary)] transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i>Terms of Service</a></li>
                    </ul>
                </div>
                <div class="transition-all duration-300 hover:transform hover:translate-y-[-5px]">
                    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] relative inline-block">
                        Contact Us
                        <span class="absolute bottom-0 left-0 w-1/2 h-0.5 bg-[var(--primary)] transform scale-x-0 transition-transform duration-300 origin-left group-hover:scale-x-100"></span>
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-center text-gray-600 hover:text-[var(--primary)] transition-colors duration-300">
                            <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] mr-3">
                                <i class="fas fa-envelope"></i>
                            </span>
                            support@novella.com
                        </li>
                        <li class="flex items-center text-gray-600 hover:text-[var(--primary)] transition-colors duration-300">
                            <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] mr-3">
                                <i class="fas fa-phone"></i>
                            </span>
                            +1 234 567 8900
                        </li>
                        <li class="flex items-center text-gray-600 hover:text-[var(--primary)] transition-colors duration-300">
                            <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] mr-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>
                            123 Book Street, Library City
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-200/50 text-center text-gray-600">
                <p>Made with <i class="fas fa-heart text-[var(--primary)] hover:scale-110 transition-transform duration-300 inline-block"></i> for book lovers • Powered by Open Library API</p>
            </div>
        </div>
    </footer>

    <script>
        // Particles.js configuration
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
                        "value": [
                            "#F06262",
                            "#4DB6AC",
                            "#7B5EAB"
                        ]
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
                        "width": 1
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

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.add('active');
            document.getElementById('menuOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        document.getElementById('closeMenu').addEventListener('click', closeMenu);
        document.getElementById('menuOverlay').addEventListener('click', closeMenu);

        function closeMenu() {
            document.getElementById('mobileMenu').classList.remove('active');
            document.getElementById('menuOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        function handleScroll() {
            // Animate book cards
            const cards = document.querySelectorAll('.book-card');
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const windowHeight = window.innerHeight || document.documentElement.clientHeight;
                if (rect.top <= windowHeight * 0.7) {
                    card.classList.add('visible');
                }
            });

            // Animate scroll containers
            const scrollContainers = document.querySelectorAll('.scroll-container');
            scrollContainers.forEach(container => {
                const rect = container.getBoundingClientRect();
                const windowHeight = window.innerHeight || document.documentElement.clientHeight;
                if (rect.top <= windowHeight * 0.8) {
                    container.classList.add('visible');
                }
            });

            // Handle scroll-to-top button visibility
            const scrollButton = document.getElementById('scrollToTop');
            if (window.scrollY > 300) {
                scrollButton.classList.add('visible');
            } else {
                scrollButton.classList.remove('visible');
            }

            // Handle menu toggle visibility
            const menuToggle = document.getElementById('menuToggle');
            if (window.scrollY > 100) {
                menuToggle.classList.remove('lg:hidden');
            } else {
                menuToggle.classList.add('lg:hidden');
            }
        }

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        window.addEventListener('scroll', handleScroll);
        window.addEventListener('load', handleScroll);

        const genreMap = {
            'All': 'popular',
            'Romance': 'love',
            'Fantasy': 'fantasy',
            'SciFi': 'science_fiction',
            'Mystery': 'mystery',
            'Horror': 'horror',
            'Manga': 'manga'
        };

        async function filterByGenre(genre) {
            const categoryResultsContainer = document.getElementById('categoryResultsContainer');
            document.querySelectorAll('.category-pill').forEach(pill => {
                pill.classList.remove('active-pill');
                if (pill.innerText === genre) {
                    pill.classList.add('active-pill');
                }
            });

            if (genre === 'All') {
                categoryResultsContainer.innerHTML = '';
                handleScroll();
                return;
            }

            const genreKey = genreMap[genre];
            categoryResultsContainer.innerHTML = `
                <div class="glass-card p-8 flex justify-center items-center">
                    <div class="animate-pulse flex flex-col items-center">
                        <div class="rounded-full h-12 w-12 border-4 border-t-[var(--primary)] border-r-[var(--accent)] border-b-[var(--light)] border-l-[var(--secondary)] animate-spin"></div>
                        <p class="mt-4 text-[var(--dark)]">Loading ${genre} books...</p>
                    </div>
                </div>
            `;

            try {
                const books = await fetchBooks(genreKey);
                categoryResultsContainer.innerHTML = `
                    <div class="glass-card p-6 mb-8">
                        <div class="flex items-center justify-between mb-6 flex-wrap">
                            <div class="flex items-center mb-2 sm:mb-0">
                                <div class="bg-[var(--primary)] text-white p-3 rounded-full mr-4">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                                <h2 class="text-xl sm:text-2xl font-bold text-[var(--dark)] genre-title">${genre} Books</h2>
                            </div>
                            <span class="text-sm bg-[var(--light-bg)] text-[var(--dark)] py-1 px-4 rounded-full">${books.length} Results</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                            ${books.map(book => `
                                <div class="book-card transition-opacity duration-700 ease-out" onclick="window.location.href='book_details.php?olid=${book.olid}'">
                                    <div class="relative h-48 md:h-56 overflow-hidden rounded-t-lg">
                                        <img src="${book.cover}" alt="${book.title}" class="book-cover w-full h-full object-cover">
                                    </div>
                                    <div class="p-3 bg-white rounded-b-lg">
                                        <h3 class="text-sm font-medium line-clamp-2 h-10 text-[var(--dark)]">${book.title}</h3>
                                        <p class="text-xs text-gray-500 mt-1">${book.author}</p>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs px-2 py-1 bg-[var(--light-bg)] text-[var(--dark)] rounded-full">${book.firstPublishYear}</span>
                                            <div class="flex items-center">
                                                <i class="fas fa-star text-yellow-400 text-xs mr-1"></i>
                                                <span class="text-xs text-gray-700">${(Math.random() * 2 + 3).toFixed(1)}</span>
                                            </div>
                                        </div>
                                        <button class="mt-3 w-full btn-primary py-2 rounded-full text-xs font-medium">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                handleScroll();
                categoryResultsContainer.scrollIntoView({ behavior: 'smooth' });
            } catch (error) {
                console.error(`Failed to fetch ${genre} books:`, error);
                categoryResultsContainer.innerHTML = `
                    <div class="glass-card p-6 mb-8">
                        <div class="flex items-center text-[var(--primary)]">
                            <i class="fas fa-exclamation-circle text-3xl mr-4"></i>
                            <div>
                                <h3 class="font-bold">Error fetching ${genre} books</h3>
                                <p>Try again later.</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        async function fetchBooks(genreKey) {
            const url = `https://openlibrary.org/subjects/${genreKey}.json?limit=12`;
            try {
                const response = await fetch(url);
                const data = await response.json();
                return data.works.map(book => ({
                    title: book.title,
                    author: book.authors?.[0]?.name || "Unknown Author",
                    cover: book.cover_id
                        ? `https://covers.openlibrary.org/b/id/${book.cover_id}-M.jpg`
                        : "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1yg_rIUE_FzrkgJGIrpCu_e45OFLXH5GByg&s",
                    olid: book.key.split("/").pop(),
                    firstPublishYear: book.first_publish_year || "N/A"
                }));
            } catch (error) {
                console.error(`Failed to fetch books for ${genreKey}:`, error);
                return [];
            }
        }

        async function renderTrending() {
            const books = await fetchBooks("popular");
            trendingContainer.innerHTML = books.slice(0, 6).map(book => `
                <div class="book-card transition-opacity duration-700 ease-out" onclick="window.location.href='book_details.php?olid=${book.olid}'">
                    <div class="relative h-56 md:h-64 overflow-hidden rounded-t-lg">
                        <img src="${book.cover}" alt="${book.title}" class="book-cover w-full h-full object-cover">
                        <div class="absolute top-2 right-2 bg-[var(--primary)] text-white rounded-full w-8 h-8 flex items-center justify-center">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                    <div class="p-4 bg-white rounded-b-lg">
                        <h3 class="text-sm font-medium line-clamp-2 h-10 text-[var(--dark)]">${book.title}</h3>
                        <p class="text-xs text-gray-500 mt-1">${book.author}</p>
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex">
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <i class="far fa-star text-yellow-400 text-xs"></i>
                            </div>
                            <span class="text-xs px-2 py-1 bg-[var(--light-bg)] text-[var(--dark)] rounded-full">${book.firstPublishYear}</span>
                        </div>
                        <button class="mt-3 w-full btn-primary py-2 rounded-full text-xs font-medium">
                            View Book
                        </button>
                    </div>
                </div>
            `).join('');
            handleScroll();
        }

        async function renderGenres() {
            const genres = [
                { name: "Romance & Love Stories", key: "love", icon: "fa-heart" },
                { name: "Fantasy & Magic", key: "fantasy", icon: "fa-hat-wizard" },
                { name: "Science Fiction", key: "science_fiction", icon: "fa-robot" },
                { name: "Mystery & Thriller", key: "mystery", icon: "fa-search" },
            ];

            const genresContainer = document.getElementById("genresContainer");
            for (const genre of genres) {
                const books = await fetchBooks(genre.key);
                const genreSection = document.createElement("div");
                genreSection.classList.add("glass-card", "p-6");
                genreSection.innerHTML = `
                    <div class="flex items-center mb-6">
                        <div class="bg-[var(--primary)] text-white p-3 rounded-full mr-4">
                            <i class="fas ${genre.icon} text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-[var(--dark)] genre-title">${genre.name}</h2>
                    </div>
                    <div class="scroll-container overflow-x-auto pb-4">
                        <div class="flex gap-6">
                            ${books.map(book => `
                                <div class="book-card w-48 sm:w-52 md:w-56 flex-shrink-0 transition-opacity duration-700 ease-out" onclick="window.location.href='book_details.php?olid=${book.olid}'">
                                    <div class="relative h-64 overflow-hidden rounded-t-lg">
                                        <img src="${book.cover}" alt="${book.title}" class="book-cover w-full h-full object-cover">
                                    </div>
                                    <div class="p-4 bg-white rounded-b-lg">
                                        <h3 class="text-sm font-medium line-clamp-2 h-10 text-[var(--dark)]">${book.title}</h3>
                                        <p class="text-xs text-gray-500 mt-1">${book.author}</p>
                                        <div class="flex items-center justify-between mt-3">
                                            <div class="flex">
                                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                                <i class="far fa-star text-yellow-400 text-xs"></i>
                                            </div>
                                            <span class="text-xs px-2 py-1 bg-[var(--light-bg)] text-[var(--dark)] rounded-full">${book.firstPublishYear}</span>
                                        </div>
                                        <button class="mt-3 w-full btn-primary py-2 rounded-full text-xs font-medium">
                                            View Book
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                genresContainer.appendChild(genreSection);
                handleScroll();
            }
        }

        async function searchBooks() {
            const query = document.getElementById("searchInput").value;
            if (!query.trim()) return;

            const url = `https://openlibrary.org/search.json?q=${encodeURIComponent(query)}&limit=12`;
            try {
                searchResultsContainer.innerHTML = `
                    <div class="glass-card p-8 flex justify-center items-center">
                        <div class="animate-pulse flex flex-col items-center">
                            <div class="rounded-full h-12 w-12 border-4 border-t-[var(--primary)] border-r-[var(--accent)] border-b-[var(--light)] border-l-[var(--secondary)] animate-spin"></div>
                            <p class="mt-4 text-[var(--dark)]">Searching the library...</p>
                        </div>
                    </div>
                `;

                const response = await fetch(url);
                const data = await response.json();

                searchResultsContainer.innerHTML = `
                    <div class="glass-card p-6 mb-8">
                        <div class="flex items-center justify-between mb-6 flex-wrap">
                            <div class="flex items-center mb-2 sm:mb-0">
                                <div class="bg-[var(--primary)] text-white p-3 rounded-full mr-4">
                                    <i class="fas fa-search text-xl"></i>
                                </div>
                                <h2 class="text-xl sm:text-2xl font-bold text-[var(--dark)] genre-title">Results for "${query}"</h2>
                            </div>
                            <span class="text-sm bg-[var(--light-bg)] text-[var(--dark)] py-1 px-4 rounded-full">${data.docs.length} Results</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                            ${data.docs.slice(0, 12).map(book => {
                                const coverUrl = book.cover_i
                                    ? `https://covers.openlibrary.org/b/id/${book.cover_i}-M.jpg`
                                    : "https://via.placeholder.com/150x220";
                                return `
                                    <div class="book-card transition-opacity duration-700 ease-out" onclick="window.location.href='book_details.php?olid=${book.key.split('/').pop()}'">
                                        <div class="relative h-48 md:h-56 overflow-hidden rounded-t-lg">
                                            <img src="${coverUrl}" alt="${book.title}" class="book-cover w-full h-full object-cover">
                                        </div>
                                        <div class="p-3 bg-white rounded-b-lg">
                                            <h3 class="text-sm font-medium line-clamp-2 h-10 text-[var(--dark)]">${book.title}</h3>
                                            <p class="text-xs text-gray-500 mt-1">${book.author_name?.[0] || "Unknown Author"}</p>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-xs px-2 py-1 bg-[var(--light-bg)] text-[var(--dark)] rounded-full">${book.first_publish_year || "N/A"}</span>
                                                <div class="flex items-center">
                                                    <i class="fas fa-star text-yellow-400 text-xs mr-1"></i>
                                                    <span class="text-xs text-gray-700">${(Math.random() * 2 + 3).toFixed(1)}</span>
                                                </div>
                                            </div>
                                            <button class="mt-3 w-full btn-primary py-2 rounded-full text-xs font-medium">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                handleScroll();
                searchResultsContainer.scrollIntoView({ behavior: 'smooth' });
            } catch (error) {
                console.error("Failed to fetch search results:", error);
                searchResultsContainer.innerHTML = `
                    <div class="glass-card p-6 mb-8">
                        <div class="flex items-center text-[var(--primary)]">
                            <i class="fas fa-exclamation-circle text-3xl mr-4"></i>
                            <div>
                                <h3 class="font-bold">Error fetching results</h3>
                                <p>Try again or refine your search terms.</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        document.getElementById("searchInput").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                searchBooks();
            }
        });

        renderTrending();
        renderGenres();
    </script>
</body>
</html>
