<?php
session_start();
require_once 'php/db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Novella</title>
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

        .form-input {
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(161, 154, 211, 0.3);
            transition: all 0.3s ease;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 131, 131, 0.2);
            outline: none;
        }

        .btn-primary {
            border-radius: 12px;
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

        .book-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-10px) rotate(2deg); }
            50% { transform: translateY(0px) rotate(0deg); }
            75% { transform: translateY(10px) rotate(-2deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        /* Additional responsive adjustments */
        @media (max-width: 640px) {
            .glass-card {
                padding: 1.25rem;
            }
            
            .form-input, .btn-primary {
                font-size: 0.9rem;
                padding: 0.625rem 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto px-4 py-6 sm:py-12 flex flex-col lg:flex-row items-center justify-center gap-8 lg:gap-12">
            <!-- Left side - Branding -->
            <div class="w-full lg:w-5/12 flex flex-col items-center lg:items-start animate-fade-in-up mb-8 lg:mb-0">
                <div class="book-animation mb-4 sm:mb-6">
                    <i class="fas fa-book-open text-5xl sm:text-6xl text-[var(--primary)]"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[var(--dark)] text-center lg:text-left mb-3 sm:mb-4">
                    Novella
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 text-center lg:text-left mb-6 sm:mb-8 max-w-md">
                    Your personal journey through the world of books starts here.
                </p>
                <div class="hidden sm:block w-full max-w-sm">
                    <div class="glass-card p-4 sm:p-6">
                        <div class="flex items-center gap-3 sm:gap-4 mb-4">
                            <div class="bg-[var(--accent)] rounded-full p-2 sm:p-3">
                                <i class="fas fa-book text-white text-sm sm:text-base"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm sm:text-base">Track Your Reading</h3>
                                <p class="text-xs sm:text-sm text-gray-600">Save your progress and build your library</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="bg-[var(--light)] rounded-full p-2 sm:p-3">
                                <i class="fas fa-comment text-white text-sm sm:text-base"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm sm:text-base">Share Reviews</h3>
                                <p class="text-xs sm:text-sm text-gray-600">Connect with a community of readers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Login Form -->
            <div class="w-full sm:w-10/12 md:w-8/12 lg:w-5/12">
                <div class="glass-card p-6 sm:p-8 animate-fade-in-up animate-delay-200">
                    <h2 class="text-xl sm:text-2xl font-bold text-[var(--dark)] mb-4 sm:mb-6">
                        Sign in to continue
                    </h2>

                    <!-- PHP error message -->
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 mb-4 sm:mb-6 rounded-md animate-fade-in-up animate-delay-100">
                            <div class="flex">
                                <i class="fas fa-exclamation-circle mt-1 mr-2"></i>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4 sm:space-y-5">
                        <div class="animate-fade-in-up animate-delay-300">
                            <label for="username" class="block text-sm font-medium text-[var(--dark)] mb-1 sm:mb-2">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="text"
                                    name="username"
                                    id="username"
                                    class="form-input pl-10 py-2 sm:py-3 w-full"
                                    placeholder="Enter your username"
                                    required
                                >
                            </div>
                        </div>

                        <div class="animate-fade-in-up animate-delay-400">
                            <label for="password" class="block text-sm font-medium text-[var(--dark)] mb-1 sm:mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-input pl-10 py-2 sm:py-3 w-full"
                                    placeholder="Enter your password"
                                    required
                                >
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 animate-fade-in-up animate-delay-500">
                            <div class="flex items-center">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full py-2 sm:py-3 animate-fade-in-up animate-delay-500">
                            <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                        </button>
                    </form>

                    <div class="mt-6 sm:mt-8 animate-fade-in-up animate-delay-500">
                        <p class="text-sm sm:text-base text-gray-600">
                            Don't have an account?
                            <a href="signup.php" class="font-medium text-[var(--primary)] hover:text-[var(--light)]">
                                Sign up
                            </a>
                        </p>

                        <div class="mt-4 sm:mt-6">
                            <a href="index.php" class="text-xs sm:text-sm text-[var(--dark)] hover:text-[var(--primary)] flex items-center">
                                <i class="fas fa-home mr-2"></i> Continue as guest
                            </a>
                        </div>
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
        });
    </script>
</body>
</html>
