<?php
session_start();
require_once 'php/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$upload_message = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $upload_dir = "uploads/profile_pictures/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB
    $file = $_FILES['profile_picture'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_message = "Error: Failed to upload file.";
    } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed_types)) {
        $upload_message = "Error: Only JPG and PNG files are allowed.";
    } elseif ($file['size'] > $max_size) {
        $upload_message = "Error: File is too large. Maximum size is 2MB.";
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $ext;
        $target_file = $upload_dir . $new_file_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Update profile picture path in database
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$new_file_name, $user_id]);
            $upload_message = "Profile picture uploaded successfully!";
        } else {
            $upload_message = "Error: Failed to upload file.";
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email, created_at, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Set profile picture path
$profile_picture = $user['profile_picture'] ? "uploads/profile_pictures/" . $user['profile_picture'] : "https://via.placeholder.com/150";

// Fetch reading stats
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM read_books WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reading_stats = [
    'want_to_read' => 0,
    'currently_reading' => 0,
    'read' => 0
];

foreach ($stats as $stat) {
    $reading_stats[$stat['status']] = $stat['count'];
}

// Fetch recently added books (limit to 5)
$stmt = $pdo->prepare("SELECT book_olid, status, added_at FROM read_books WHERE user_id = ? ORDER BY added_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Novella</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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
            margin: 0;
            overflow-x: hidden;
        }

        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .glass-card {
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
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

        .profile-card {
            transition: all 0.3s ease;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.85);
            box-shadow: 0 10px 20px rgba(45, 42, 64, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .profile-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(45, 42, 64, 0.15);
        }

        .avatar {
            border-radius: 50%;
            border: 4px solid var(--accent);
            box-shadow: 0 4px 15px rgba(161, 214, 203, 0.3);
        }

        .stat-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(45, 42, 64, 0.15);
        }

        .file-input-label {
            cursor: pointer;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 50px;
            background-color: var(--accent);
            color: white;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            background-color: #8cc4b8;
            transform: translateY(-2px);
        }

        .upload-message {
            color: <?php echo strpos($upload_message, 'Error') === false ? 'var(--accent)' : '#ef4444'; ?>;
            font-size: 0.875rem;
            margin-top: 8px;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
        }

        .animate-delay-100 { animation-delay: 0.1s; }
        .animate-delay-200 { animation-delay: 0.2s; }
        .animate-delay-300 { animation-delay: 0.3s; }
        .animate-delay-400 { animation-delay: 0.4s; }
        .animate-delay-500 { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #profile-picture-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            margin-top: 8px;
            display: none;
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <div class="container mx-auto px-4 sm:px-6 py-12">
        <nav class="flex justify-between items-center mb-8 animate-fade-in-up">
            <a href="dashboard.php" class="flex items-center text-[var(--dark)] hover:text-[var(--primary)] transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                <span class="font-medium">Back to Explore</span>
            </a>
            <a href="dashboard.php" class="flex items-center">
                <i class="fas fa-book-open text-2xl text-[var(--primary)] mr-3"></i>
                <span class="font-bold text-xl text-[var(--dark)]">Novella</span>
            </a>
        </nav>

        <div class="glass-card p-6 md:p-10 mb-8 animate-fade-in-up animate-delay-200">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Profile Info -->
                <div class="md:w-1/3">
                    <div class="profile-card p-6 text-center animate-fade-in-up animate-delay-300">
                        <div class="relative inline-block">
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="User Avatar" class="avatar w-32 h-32 mx-auto mb-4">
                        </div>
                        <form method="POST" enctype="multipart/form-data" id="profile-picture-form">
                            <label for="profile_picture" class="file-input-label">
                                <i class="fas fa-camera mr-2"></i>Change Picture
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png" class="hidden">
                            <img id="profile-picture-preview" src="#" alt="Profile Picture Preview">
                            <p class="text-xs text-gray-500 mt-1">Supported: JPG, PNG (Max: 2MB)</p>
                        </form>
                        <?php if ($upload_message): ?>
                            <p class="upload-message animate-fade-in-up animate-delay-400"><?php echo htmlspecialchars($upload_message); ?></p>
                        <?php endif; ?>
                        <h2 class="text-2xl font-bold text-[var(--dark)] mt-4 mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-sm text-gray-500 mb-6">Joined: <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        <div class="flex flex-col gap-3">
                            <a href="read_books.php" class="btn-primary py-3">View My Collection</a>
                            <a href="logout.php" class="btn-secondary py-3">Logout</a>
                        </div>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="md:w-2/3">
                    <h1 class="text-3xl font-bold text-[var(--dark)] mb-6 animate-fade-in-up animate-delay-300">My Reading Journey</h1>

                    <!-- Reading Stats -->
                    <div class="mb-8 animate-fade-in-up animate-delay-400">
                        <h2 class="text-xl font-semibold text-[var(--dark)] mb-4">Reading Stats</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="stat-card p-4">
                                <h3 class="text-gray-600 font-medium">Want to Read</h3>
                                <p class="text-2xl font-bold text-[var(--primary)]"><?php echo $reading_stats['want_to_read']; ?></p>
                                <div class="mt-2 h-1 bg-gray-200 rounded-full">
                                    <div class="bg-[var(--primary)] h-full rounded-full" style="width: <?php echo ($reading_stats['want_to_read'] / max(array_sum($reading_stats), 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-card p-4">
                                <h3 class="text-gray-600 font-medium">Currently Reading</h3>
                                <p class="text-2xl font-bold text-[var(--light)]"><?php echo $reading_stats['currently_reading']; ?></p>
                                <div class="mt-2 h-1 bg-gray-200 rounded-full">
                                    <div class="bg-[var(--light)] h-full rounded-full" style="width: <?php echo ($reading_stats['currently_reading'] / max(array_sum($reading_stats), 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-card p-4">
                                <h3 class="text-gray-600 font-medium">Completed</h3>
                                <p class="text-2xl font-bold text-[var(--accent)]"><?php echo $reading_stats['read']; ?></p>
                                <div class="mt-2 h-1 bg-gray-200 rounded-full">
                                    <div class="bg-[var(--accent)] h-full rounded-full" style="width: <?php echo ($reading_stats['read'] / max(array_sum($reading_stats), 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recently Added Books -->
                    <div class="animate-fade-in-up animate-delay-500">
                        <h2 class="text-xl font-semibold text-[var(--dark)] mb-4">Recently Added</h2>
                        <?php if (empty($recent_books)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No books added yet. Start your collection!</p>
                                <a href="dashboard.php" class="inline-block btn-primary mt-4 py-2 px-6">Discover Books</a>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <?php
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                                foreach ($recent_books as $book):
                                    $olid = $book['book_olid'];
                                    $api_url = "https://openlibrary.org/works/$olid.json";
                                    curl_setopt($ch, CURLOPT_URL, $api_url);
                                    $response = curl_exec($ch);
                                    $book_data = $response ? json_decode($response, true) : null;

                                    $cover_url = isset($book_data['covers'][0]) ? "https://covers.openlibrary.org/b/id/{$book_data['covers'][0]}-M.jpg" : "https://via.placeholder.com/150x220";
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
                                    <a href="book_details.php?olid=<?php echo htmlspecialchars($olid); ?>" class="profile-card p-4">
                                        <div class="flex items-center gap-4">
                                            <img src="<?php echo htmlspecialchars($cover_url); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="w-20 h-28 object-cover rounded">
                                            <div>
                                                <h3 class="text-sm font-medium text-[var(--dark)] line-clamp-2"><?php echo htmlspecialchars($title); ?></h3>
                                                <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($author); ?></p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <?php
                                                    $status_text = str_replace('_', ' ', $book['status']);
                                                    echo ucwords($status_text);
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <?php curl_close($ch); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Interactive background animation with Three.js
        document.addEventListener('DOMContentLoaded', function() {
            // Set up scene
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            document.getElementById('canvas-container').appendChild(renderer.domElement);
            
            // Add floating particles
            const particles = new THREE.Group();
            scene.add(particles);
            
            const particleCount = 50;
            const colors = [
                new THREE.Color('#FF8383'),  // Coral Pink
                new THREE.Color('#A1D6CB'),  // Mint Green
                new THREE.Color('#A19AD3'),  // Lavender
                new THREE.Color('#FFF574')   // Yellow
            ];
            
            for (let i = 0; i < particleCount; i++) {
                const geometry = new THREE.SphereGeometry(0.1, 8, 8); // Reduced particle size
                const material = new THREE.MeshBasicMaterial({ 
                    color: colors[Math.floor(Math.random() * colors.length)],
                    transparent: true,
                    opacity: 0.5 + Math.random() * 0.3
                });
                
                const particle = new THREE.Mesh(geometry, material);
                
                // Random position
                particle.position.x = (Math.random() - 0.5) * 20;
                particle.position.y = (Math.random() - 0.5) * 20;
                particle.position.z = (Math.random() - 0.5) * 20;
                
                // Custom properties for animation
                particle.userData = {
                    speed: 0.005 + Math.random() * 0.01, // Reduced rotation speed
                    rotationSpeed: 0.005 + Math.random() * 0.01,
                    direction: new THREE.Vector3(
                        (Math.random() - 0.5) * 0.05, // Reduced movement speed
                        (Math.random() - 0.5) * 0.05,
                        (Math.random() - 0.5) * 0.05
                    )
                };
                
                particles.add(particle);
            }
            
            // Position camera
            camera.position.z = 15;
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                
                // Rotate the entire particle system
                particles.rotation.y += 0.001; // Reduced rotation speed
                particles.rotation.x += 0.0005;
                
                // Animate each particle
                particles.children.forEach(particle => {
                    particle.rotation.x += particle.userData.rotationSpeed;
                    particle.rotation.y += particle.userData.rotationSpeed;
                    
                    // Move particle
                    particle.position.add(particle.userData.direction);
                    
                    // Boundary check - wrap around if out of bounds
                    if (Math.abs(particle.position.x) > 10) particle.userData.direction.x *= -1;
                    if (Math.abs(particle.position.y) > 10) particle.userData.direction.y *= -1;
                    if (Math.abs(particle.position.z) > 10) particle.userData.direction.z *= -1;
                });
                
                renderer.render(scene, camera);
            }
            
            animate();
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
            
            // Interactive effect - move particles slightly based on mouse position
            document.addEventListener('mousemove', function(event) {
                const mouseX = (event.clientX / window.innerWidth) * 2 - 1;
                const mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
                
                particles.rotation.y = mouseX * 0.1;
                particles.rotation.x = mouseY * 0.1;
            });

            // Profile picture preview
            const profilePictureInput = document.getElementById('profile_picture');
            const profilePicturePreview = document.getElementById('profile-picture-preview');
            const form = document.getElementById('profile-picture-form');
            
            profilePictureInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileType = file.type;
                    const fileSize = file.size;
                    const allowedTypes = ['image/jpeg', 'image/png'];
                    const maxSize = 2 * 1024 * 1024; // 2MB

                    if (!allowedTypes.includes(fileType)) {
                        alert('Only JPG and PNG files are allowed.');
                        this.value = '';
                        profilePicturePreview.style.display = 'none';
                        return;
                    }

                    if (fileSize > maxSize) {
                        alert('Profile picture must be less than 2MB.');
                        this.value = '';
                        profilePicturePreview.style.display = 'none';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePicturePreview.src = e.target.result;
                        profilePicturePreview.style.display = 'block';
                        setTimeout(() => form.submit(), 500); // Delay submit to show preview
                    };
                    reader.readAsDataURL(file);
                } else {
                    profilePicturePreview.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>