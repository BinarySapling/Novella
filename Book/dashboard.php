<?php
session_start();
require_once 'php/db_connect.php';

$base_url = "http://localhost/book_explorer/";

// Default profile picture
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    error_log("User ID: " . $user_id);
    try {
        $stmt = $pdo->prepare("SELECT username, email, created_at, profile_picture FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            error_log("User found: " . json_encode($user));
            $profile_picture = "https://via.placeholder.com/40";
            if (!empty($user['profile_picture'])) {
                $file_path = "Uploads/profile_pictures/" . $user['profile_picture'];
                error_log("Checking file: " . $file_path);
                if (file_exists($file_path)) {
                    $profile_picture = $base_url . $file_path . "?v=" . time();
                    error_log("Profile picture set: " . $profile_picture);
                } else {
                    error_log("File does not exist: " . $file_path);
                }
            } else {
                error_log("Profile picture empty for user ID: " . $user_id);
            }
        } else {
            error_log("No user found for ID: " . $user_id);
            header("Location: logout.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error in index.php: " . $e->getMessage());
        $user = ['username' => 'User', 'email' => '', 'created_at' => ''];
        $profile_picture = "https://via.placeholder.com/40";
    }
} else {
    header("Location: login.php");
    exit;
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
        .scroll-container {
            overflow-x: auto;
            scroll-behavior: smooth; /* Smooth horizontal scrolling */
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on mobile */
            scrollbar-width: thin; /* Firefox: thinner scrollbar */
            overscroll-behavior-x: contain; /* Prevent scroll chaining */
            position: relative; /* For scroll trigger */
        }

        /* Hide scrollbar for a cleaner look */
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

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffeffd, #f0f4ff);
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
        }

        .hero-content {
            position: relative;
            z-index: 10;
            backdrop-filter: blur(5px);
            border-radius: 16px;
            background-color: rgba(255, 255, 255, 0); /* Fully transparent background */
            box-shadow: none; /* Remove shadow for more transparency */
            border: 1px solid rgba(255, 255, 255, 0.1); /* More subtle border */
            padding: 2rem;
        }

        /* Enhance text legibility */
        .hero-content h1, .hero-content p {
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2); /* Stronger shadow for better contrast */
            color: var(--dark);
            font-weight: bold;
        }

        .hero-content .btn-primary, .hero-content .btn-secondary, .hero-content .btn-outline {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
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
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .category-pill::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.8s ease;
        }
        
        .category-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(161, 154, 211, 0.4);
        }
        
        .category-pill:hover::before {
            left: 100%;
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

        /* Mobile navigation menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 300px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            transition: right 0.3s ease;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }

        .mobile-menu.active {
            right: 0;
        }

        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Animation effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* Dynamic book card hover */
        .book-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 0;
            background: linear-gradient(to top, var(--primary), transparent);
            opacity: 0;
            transition: all 0.4s ease;
            z-index: -1;
            border-radius: 16px;
        }

        .book-card:hover::after {
            opacity: 0.15;
            height: 100%;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* 滚动汉堡菜单样式 */
        #scrollHamburger {
            transition: all 0.3s ease;
        }
        
        #scrollMenuBtn {
            position: relative;
            z-index: 52;
            transition: all 0.3s ease;
        }
        
        #scrollMenuBtn:hover {
            transform: scale(1.1);
            background-color: var(--light-bg);
        }
        
        #scrollMenu {
            border-radius: 12px;
            transform-origin: top right;
            transform: scale(0.95);
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s ease-out;
            z-index: 51;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        #scrollMenu.active {
            transform: scale(1);
            opacity: 1;
            pointer-events: auto;
        }
        
        #scrollMenu a {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(10px);
        }
        
        #scrollMenu.active a {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* 添加级联动画效果 */
        #scrollMenu.active a:nth-child(2) {
            transition-delay: 0.05s;
        }
        
        #scrollMenu.active a:nth-child(3) {
            transition-delay: 0.1s;
        }
        
        #scrollMenu.active a:nth-child(5) {
            transition-delay: 0.15s;
        }
        
        #scrollMenu a:hover {
            background-color: var(--light-bg);
            padding-left: 1.5rem;
        }
        
        #scrollMenu a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }
        
        #scrollMenu a:hover::after {
            width: 100%;
        }
        
        /* 汉堡菜单图标动画 */
        .hamburger-icon {
            width: 24px;
            height: 18px;
            position: relative;
            margin: 0 auto;
            transform: rotate(0deg);
            transition: .5s ease-in-out;
            cursor: pointer;
        }
        
        .hamburger-icon span {
            display: block;
            position: absolute;
            height: 2px;
            width: 100%;
            background: var(--dark);
            border-radius: 9px;
            opacity: 1;
            left: 0;
            transform: rotate(0deg);
            transition: .25s ease-in-out;
        }
        
        .hamburger-icon span:nth-child(1) {
            top: 0px;
        }
        
        .hamburger-icon span:nth-child(2), .hamburger-icon span:nth-child(3) {
            top: 8px;
        }
        
        .hamburger-icon span:nth-child(4) {
            top: 16px;
        }
        
        .hamburger-icon.open span:nth-child(1) {
            top: 8px;
            width: 0%;
            left: 50%;
        }
        
        .hamburger-icon.open span:nth-child(2) {
            transform: rotate(45deg);
        }
        
        .hamburger-icon.open span:nth-child(3) {
            transform: rotate(-45deg);
        }
        
        .hamburger-icon.open span:nth-child(4) {
            top: 8px;
            width: 0%;
            left: 50%;
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
                    <i class="fas fa-book-open text-2xl text-[var(--primary)] mr-3"></i>
                    <span class="font-bold text-xl text-[var(--dark)]">Novella</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="profile.php" class="flex items-center text-[var(--dark)] hover:text-[var(--primary)] transition-colors">
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </a>
                </div>
                <div class="md:hidden">
                    <button id="menuBtn" class="text-[var(--dark)] focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
                <div id="scrollHamburger" class="hidden items-center relative">
                    <button id="scrollMenuBtn" class="text-[var(--dark)] focus:outline-none p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <div class="hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </button>
                    <div id="scrollMenu" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 hidden">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center">
                                <img src="<?php echo $profile_picture; ?>" alt="Profile" class="w-10 h-10 rounded-full mr-3 profile-avatar object-cover">
                                <div>
                                    <div class="font-medium text-[var(--dark)]"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                        </div>
                        <a href="read_books.php" class="block px-4 py-3 text-sm text-[var(--dark)] hover:text-[var(--primary)]">
                            <i class="fas fa-book mr-3"></i> My Collection
                        </a>
                        <a href="profile.php" class="block px-4 py-3 text-sm text-[var(--dark)] hover:text-[var(--primary)]">
                            <i class="fas fa-user mr-3"></i> My Profile
                        </a>
                        <div class="border-t border-gray-100 my-2"></div>
                        <a href="logout.php" class="block px-4 py-3 text-sm text-red-500 hover:text-red-700">
                            <i class="fas fa-sign-out-alt mr-3"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div id="menuOverlay" class="menu-overlay"></div>
    <div id="mobileMenu" class="mobile-menu">
        <div class="flex justify-end mb-6">
            <button id="closeMenuBtn" class="text-[var(--dark)] focus:outline-none">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="flex flex-col space-y-6">
            <a href="profile.php" class="flex items-center text-[var(--dark)] hover:text-[var(--primary)] transition-colors">
                <img src="<?php echo $profile_picture; ?>" alt="Profile" class="w-10 h-10 rounded-full mr-2 profile-avatar object-cover">
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </a>
            <a href="read_books.php" class="text-[var(--dark)] hover:text-[var(--primary)] transition-colors py-2 border-b border-gray-200">
                <i class="fas fa-book mr-3"></i> My Collection
            </a>
            <a href="profile.php" class="text-[var(--dark)] hover:text-[var(--primary)] transition-colors py-2 border-b border-gray-200">
                <i class="fas fa-user mr-3"></i> Profile
            </a>
            <a href="logout.php" class="text-[var(--dark)] hover:text-[var(--primary)] transition-colors py-2 border-b border-gray-200">
                <i class="fas fa-sign-out-alt mr-3"></i> Logout
            </a>
        </div>
    </div>

    <!-- Category Pills -->
    <div class="container mx-auto px-6 mt-6">
        <div class="glass-card p-4 mb-8 overflow-x-auto hide-scrollbar">
            <div class="flex space-x-3 min-w-max">
                <div class="category-pill active-pill" onclick="filterByGenre('All')">All Books</div>
                <div class="category-pill" onclick="filterByGenre('Romance')">Romance</div>
                <div class="category-pill" onclick="filterByGenre('Fantasy')">Fantasy</div>
                <div class="category-pill" onclick="filterByGenre('SciFi')">Science Fiction</div>
                <div class="category-pill" onclick="filterByGenre('Mystery')">Mystery</div>
                <div class="category-pill" onclick="filterByGenre('Horror')">Horror</div>
                <div class="category-pill" onclick="filterByGenre('Manga')">Manga</div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6">
        <div id="categoryResultsContainer" class="mb-12"></div>
        <div id="searchResultsContainer" class="mb-12"></div>

        <div class="glass-card p-6 mb-12">
            <h2 class="text-2xl font-bold mb-6 text-[var(--dark)] genre-title">Trending This Week</h2>
            <div id="trendingContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6"></div>
        </div>

        <div id="genresContainer" class="space-y-12"></div>
    </div>

    <footer class="glass-card mt-16 py-10 px-6">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">About Novella</h3>
                    <p class="text-gray-600">Discover your next favorite book with our curated collection of stories from around the world.</p>
                    <div class="mt-4 flex space-x-4">
                        <a href="#" class="text-[var(--primary)] hover:text-[var(--light)]">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-[var(--primary)] hover:text-[var(--light)]">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-[var(--primary)] hover:text-[var(--light)]">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Quick Links</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li><a href="#" class="hover:text-[var(--primary)]">About Us</a></li>
                        <li><a href="#" class="hover:text-[var(--primary)]">Contact</a></li>
                        <li><a href="#" class="hover:text-[var(--primary)]">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-[var(--primary)]">Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Visit Us</h3>
                    <address class="not-italic text-gray-600">
                        <p>123 Book Avenue</p>
                        <p>Literary District</p>
                        <p>Storyland, ST 12345</p>
                        <p class="mt-2"><a href="tel:+11234567890" class="hover:text-[var(--primary)]">(123) 456-7890</a></p>
                        <p><a href="mailto:hello@novella.com" class="hover:text-[var(--primary)]">hello@novella.com</a></p>
                    </address>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-200 text-center text-gray-600">
                <p>Made with <i class="fas fa-heart text-[var(--primary)]"></i> for book lovers • Powered by Open Library API</p>
            </div>
        </div>
    </footer>

    <script>
        // Particles.js configuration
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                "particles": {
                    "number": {
                        "value": 50,
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
                        "value": 0.4,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 1,
                            "opacity_min": 0.2,
                            "sync": false
                        }
                    },
                    "size": {
                        "value": 8,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 1,
                            "size_min": 3,
                            "sync": false
                        }
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#A19AD3",
                        "opacity": 0.3,
                        "width": 1.5
                    },
                    "move": {
                        "enable": true,
                        "speed": 0.7,
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
                            "size": 10,
                            "duration": 2,
                            "opacity": 0.8,
                            "speed": 3
                        },
                        "push": {
                            "particles_nb": 4
                        }
                    }
                },
                "retina_detect": true
            });

            // Mobile menu functionality
            const menuBtn = document.getElementById('menuBtn');
            const closeMenuBtn = document.getElementById('closeMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const menuOverlay = document.getElementById('menuOverlay');

            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.add('active');
                menuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            function closeMenu() {
                mobileMenu.classList.remove('active');
                menuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            closeMenuBtn.addEventListener('click', closeMenu);
            menuOverlay.addEventListener('click', closeMenu);

            // 滚动时汉堡菜单功能
            const scrollHamburger = document.getElementById('scrollHamburger');
            const scrollMenuBtn = document.getElementById('scrollMenuBtn');
            const scrollMenu = document.getElementById('scrollMenu');
            const hamburgerIcon = document.querySelector('#scrollMenuBtn .hamburger-icon');
            const header = document.querySelector('header');
            const nav = document.querySelector('nav');
            
            // 在视图宽度超过768px时控制汉堡菜单的显示
            function toggleScrollHamburger() {
                if (window.innerWidth >= 768) { // md断点
                    const headerBottom = header.getBoundingClientRect().bottom;
                    if (headerBottom <= 0) {
                        scrollHamburger.classList.remove('hidden');
                        scrollHamburger.classList.add('flex');
                        
                        // 添加平滑淡入效果
                        scrollHamburger.style.opacity = '1';
                    } else {
                        // 平滑淡出
                        scrollHamburger.style.opacity = '0';
                        setTimeout(() => {
                            if (header.getBoundingClientRect().bottom > 0) {
                                scrollHamburger.classList.add('hidden');
                                scrollHamburger.classList.remove('flex');
                                // 确保菜单关闭
                                scrollMenu.classList.remove('active');
                                scrollMenu.classList.add('hidden');
                                hamburgerIcon.classList.remove('open');
                            }
                        }, 300);
                    }
                } else {
                    scrollHamburger.classList.add('hidden');
                    scrollHamburger.classList.remove('flex');
                }
            }

            // 点击滚动汉堡菜单按钮时的行为
            scrollMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // 切换汉堡图标动画
                hamburgerIcon.classList.toggle('open');
                
                // 使用active类实现平滑过渡，而不是直接使用hidden
                if (scrollMenu.classList.contains('active')) {
                    scrollMenu.classList.remove('active');
                    setTimeout(() => {
                        scrollMenu.classList.add('hidden');
                    }, 200); // 等待过渡完成
                } else {
                    scrollMenu.classList.remove('hidden');
                    // 强制重绘以触发动画
                    scrollMenu.offsetHeight;
                    scrollMenu.classList.add('active');
                }
            });

            // 点击文档其他位置关闭菜单
            document.addEventListener('click', (e) => {
                if (!scrollMenu.contains(e.target) && e.target !== scrollMenuBtn && !scrollMenuBtn.contains(e.target)) {
                    hamburgerIcon.classList.remove('open');
                    scrollMenu.classList.remove('active');
                    setTimeout(() => {
                        scrollMenu.classList.add('hidden');
                    }, 200);
                }
            });

            // 监听滚动和调整大小事件
            window.addEventListener('scroll', toggleScrollHamburger);
            window.addEventListener('resize', toggleScrollHamburger);
            
            // 初始设置
            scrollHamburger.style.opacity = '0';
            toggleScrollHamburger();

            // Add data-scroll-trigger to all scroll containers
            document.querySelectorAll('.scroll-container').forEach(container => {
                container.setAttribute('data-scroll-trigger', 'true');
            });

            // Apply fade-in animation classes to hero elements
            const heroElements = document.querySelectorAll('.hero-content > *');
            heroElements.forEach((el, index) => {
                el.classList.add('fade-in', `delay-${(index % 3) + 1}`);
            });
        });

        function handleScroll() {
            const cards = document.querySelectorAll('.book-card');
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const windowHeight = window.innerHeight || document.documentElement.clientHeight;
                if (rect.top <= windowHeight * 0.9) {
                    card.classList.add('visible');
                }
            });

            const scrollButton = document.getElementById('scrollToTop');
            if (window.scrollY > 300) {
                scrollButton.classList.add('visible');
            } else {
                scrollButton.classList.remove('visible');
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
                        : "https://via.placeholder.com/150x220",
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
                genreSection.classList.add("glass-card", "p-6", "transform", "transition-all", "duration-500");
                genreSection.dataset.aos = "fade-up";
                
                genreSection.innerHTML = `
                    <div class="flex items-center justify-between mb-6 flex-wrap">
                        <div class="flex items-center mb-2 sm:mb-0">
                            <div class="bg-[var(--primary)] text-white p-3 rounded-full mr-4 transform transition-transform hover:scale-110 duration-300">
                                <i class="fas ${genre.icon} text-xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-[var(--dark)] genre-title">${genre.name}</h2>
                        </div>
                      
                    </div>
                    <div class="scroll-container overflow-x-auto pb-4" data-scroll-trigger="true">
                        <div class="flex gap-6">
                            ${books.map((book, index) => `
                                <div class="book-card w-48 sm:w-52 md:w-56 flex-shrink-0 transition-all duration-700 ease-out" 
                                    style="transition-delay: ${index * 50}ms" 
                                    onclick="window.location.href='book_details.php?olid=${book.olid}'">
                                    <div class="relative h-64 overflow-hidden rounded-t-lg">
                                        <img src="${book.cover}" alt="${book.title}" class="book-cover w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity duration-300 flex items-end p-3 hover:opacity-100">
                                            <span class="text-white text-xs font-medium">${book.title}</span>
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
                                        <button class="mt-3 w-full btn-primary py-2 rounded-full text-xs font-medium group">
                                            <span class="group-hover:mr-2 transition-all">View Book</span>
                                            <i class="fas fa-arrow-right opacity-0 group-hover:opacity-100 transition-all"></i>
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="scroll-indicator mt-4 flex justify-center items-center space-x-1">
                        <div class="h-1 w-12 bg-[var(--primary)] rounded-full"></div>
                        <div class="h-1 w-12 bg-gray-200 rounded-full"></div>
                        <div class="h-1 w-12 bg-gray-200 rounded-full"></div>
                    </div>
                `;
                genresContainer.appendChild(genreSection);

                // Monitor scroll position for scroll indicator
                const scrollContainer = genreSection.querySelector('.scroll-container');
                scrollContainer.addEventListener('scroll', () => {
                    const scrollPosition = scrollContainer.scrollLeft;
                    const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
                    const progress = Math.min(Math.max(scrollPosition / maxScroll, 0), 1);
                    
                    const indicators = genreSection.querySelectorAll('.scroll-indicator > div');
                    if (progress < 0.33) {
                        indicators[0].classList.add('bg-[var(--primary)]');
                        indicators[1].classList.remove('bg-[var(--primary)]');
                        indicators[2].classList.remove('bg-[var(--primary)]');
                    } else if (progress < 0.66) {
                        indicators[0].classList.remove('bg-[var(--primary)]');
                        indicators[1].classList.add('bg-[var(--primary)]');
                        indicators[2].classList.remove('bg-[var(--primary)]');
                    } else {
                        indicators[0].classList.remove('bg-[var(--primary)]');
                        indicators[1].classList.remove('bg-[var(--primary)]');
                        indicators[2].classList.add('bg-[var(--primary)]');
                    }
                });
                
                handleScroll();
            }

            // Add scroll observers for animation
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const genreSections = document.querySelectorAll('.glass-card[data-aos]');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('translate-y-0', 'opacity-100');
                        entry.target.classList.remove('translate-y-10', 'opacity-0');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            genreSections.forEach(section => {
                section.classList.add('translate-y-10', 'opacity-0');
                observer.observe(section);
            });
        }

        // Scroll Trigger for Horizontal Scroll
        function setupScrollTrigger() {
            const containers = document.querySelectorAll('.scroll-container[data-scroll-trigger]');

            containers.forEach(container => {
                const observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                // Enable horizontal scroll on wheel event
                                container.addEventListener('wheel', handleWheelScroll, { passive: false });
                            } else {
                                // Remove event listener when out of view
                                container.removeEventListener('wheel', handleWheelScroll);
                            }
                        });
                    },
                    { threshold: 0.2 } // Trigger when 20% of the container is visible
                );

                observer.observe(container);
            });

            function handleWheelScroll(event) {
                event.preventDefault(); // Prevent default vertical scroll
                const container = event.currentTarget;
                const scrollAmount = event.deltaY * 0.5; // Adjust scroll speed (0.5 for smoother effect)
                container.scrollLeft += scrollAmount;

                // Check if at scroll boundaries to allow vertical scroll
                const isAtStart = container.scrollLeft <= 0 && event.deltaY < 0;
                const isAtEnd = container.scrollLeft >= (container.scrollWidth - container.clientWidth - 1) && event.deltaY > 0;

                if (isAtStart || isAtEnd) {
                    // Allow vertical scroll at boundaries
                    window.scrollBy(0, event.deltaY);
                }
            }
        }

        // Call setupScrollTrigger after DOM is loaded
        document.addEventListener('DOMContentLoaded', setupScrollTrigger);

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