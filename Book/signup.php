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
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
      
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
           
            $profile_picture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png'];
                $max_size = 2 * 1024 * 1024; 
                $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
                $file_size = $_FILES['profile_picture']['size'];

                if (!in_array($file_type, $allowed_types)) {
                    $error = 'Only JPG and PNG files are allowed';
                } elseif ($file_size > $max_size) {
                    $error = 'Profile picture must be less than 2MB';
                } else {
                    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $ext;
                    $upload_dir = 'uploads/profile_pictures/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $destination = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                        $profile_picture = $filename;
                    } else {
                        $error = 'Failed to upload profile picture';
                    }
                }
            }

            if (empty($error)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password, $profile_picture])) {
                    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Novella</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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

        #profile-picture-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.8);
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .upload-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--accent);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 0.9rem;
        }

        .upload-button:hover {
            background-color: #8cc7b8;
            transform: translateY(-1px);
        }

        /* Media Queries for Better Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .glass-card {
                padding: 1.5rem !important;
            }
        }

        @media (max-width: 640px) {
            h1 {
                font-size: 2rem !important;
            }
            
            h2 {
                font-size: 1.5rem !important;
            }
            
            .form-input {
                font-size: 0.9rem;
            }
            
            .book-animation i {
                font-size: 3rem !important;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <div id="canvas-container"></div>

    <div class="flex items-center justify-center py-6 px-4 min-h-screen">
        <div class="container mx-auto py-4 md:py-8 flex flex-col md:flex-row items-center justify-center gap-6 md:gap-8 lg:gap-12">
            <!-- Left side - Branding -->
            <div class="w-full md:w-5/12 flex flex-col items-center md:items-start animate-fade-in-up mb-8 md:mb-0">
                <div class="book-animation mb-4 md:mb-6">
                    <i class="fas fa-book-open text-5xl sm:text-6xl text-[var(--primary)]"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-[var(--dark)] text-center md:text-left mb-2 md:mb-4">
                    Novella
                </h1>
                <p class="text-lg md:text-xl text-gray-600 text-center md:text-left mb-6 md:mb-8 max-w-md">
                    Start your reading adventure with Novella today.
                </p>
                <div class="hidden md:block w-full max-w-sm">
                    <div class="glass-card p-4 md:p-6">
                        <div class="flex items-center gap-3 md:gap-4 mb-4">
                            <div class="bg-[var(--accent)] rounded-full p-2 md:p-3">
                                <i class="fas fa-book text-white text-sm md:text-base"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm md:text-base">Personalize Your Library</h3>
                                <p class="text-xs md:text-sm text-gray-600">Add books and track your progress</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 md:gap-4">
                            <div class="bg-[var(--light)] rounded-full p-2 md:p-3">
                                <i class="fas fa-comment text-white text-sm md:text-base"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm md:text-base">Join the Community</h3>
                                <p class="text-xs md:text-sm text-gray-600">Share reviews and connect with readers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Signup Form -->
            <div class="w-full md:w-7/12 lg:w-5/12">
                <div class="glass-card p-4 sm:p-6 md:p-8 animate-fade-in-up animate-delay-200">
                    <h2 class="text-xl sm:text-2xl font-bold text-[var(--dark)] mb-4 md:mb-6">
                        Create Your Account
                    </h2>

                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 md:p-4 mb-4 md:mb-6 rounded-md animate-fade-in-up animate-delay-100">
                            <div class="flex">
                                <i class="fas fa-exclamation-circle mt-1 mr-2"></i>
                                <p class="text-sm md:text-base"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4 md:space-y-5" enctype="multipart/form-data">
                        <div class="animate-fade-in-up animate-delay-300">
                            <label for="profile_picture" class="block text-xs md:text-sm font-medium text-[var(--dark)] mb-1 md:mb-2">Profile Picture (Optional)</label>
                            <div class="flex flex-col items-center">
                                <img id="profile-picture-preview" src="https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.jpg?s=612x612&w=0&k=20&c=dhV2p1JwmloBTOaGAtaA3AW1KSnjsdMt7-U_3EZElZ0=" alt="Profile Picture Preview" class="mb-3" style="display: block;">
                                <label for="profile_picture" class="upload-button">
                                    <i class="fas fa-upload mr-2"></i>Upload Image
                                </label>
                                <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png" class="hidden">
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG (Max: 2MB)</p>
                            </div>
                        </div>

                        <div class="animate-fade-in-up animate-delay-300">
                            <label for="username" class="block text-xs md:text-sm font-medium text-[var(--dark)] mb-1 md:mb-2">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="text"
                                    name="username"
                                    id="username"
                                    class="form-input pl-10 py-2 md:py-3 w-full text-sm"
                                    placeholder="Choose a username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="animate-fade-in-up animate-delay-400">
                            <label for="email" class="block text-xs md:text-sm font-medium text-[var(--dark)] mb-1 md:mb-2">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="form-input pl-10 py-2 md:py-3 w-full text-sm"
                                    placeholder="you@example.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="animate-fade-in-up animate-delay-400">
                            <label for="password" class="block text-xs md:text-sm font-medium text-[var(--dark)] mb-1 md:mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-input pl-10 py-2 md:py-3 w-full text-sm"
                                    placeholder="Choose a secure password"
                                    required
                                >
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
                        </div>

                        <div class="animate-fade-in-up animate-delay-500">
                            <label for="confirm_password" class="block text-xs md:text-sm font-medium text-[var(--dark)] mb-1 md:mb-2">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm_password"
                                    class="form-input pl-10 py-2 md:py-3 w-full text-sm"
                                    placeholder="Confirm your password"
                                    required
                                >
                            </div>
                        </div>

                        <div class="flex items-start animate-fade-in-up animate-delay-500">
                            <div class="flex items-center h-5">
                               
                            </div>
                           
                        </div>

                        <button type="submit" class="btn-primary w-full py-2 md:py-3 text-sm md:text-base animate-fade-in-up animate-delay-500">
                            <i class="fas fa-user-plus mr-2"></i> Create Account
                        </button>
                    </form>

                    <div class="mt-6 md:mt-8 animate-fade-in-up animate-delay-500">
                        <p class="text-xs sm:text-sm md:text-base text-gray-600">
                            Already have an account?
                            <a href="login.php" class="font-medium text-[var(--primary)] hover:text-[var(--light)]">
                                Sign in
                            </a>
                        </p>

                        <div class="mt-4 md:mt-6">
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      
        document.addEventListener('DOMContentLoaded', function() {
        
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            document.getElementById('canvas-container').appendChild(renderer.domElement);
            
            
            const particles = new THREE.Group();
            scene.add(particles);
            
       
            const particleCount = window.innerWidth < 768 ? 30 : 50;
            const colors = [
                new THREE.Color('#FF8383'), 
                new THREE.Color('#A1D6CB'),  
                new THREE.Color('#A19AD3'),  
                new THREE.Color('#FFF574')   
            ];
            
            for (let i = 0; i < particleCount; i++) {
                const geometry = new THREE.SphereGeometry(0.1, 8, 8);
                const material = new THREE.MeshBasicMaterial({ 
                    color: colors[Math.floor(Math.random() * colors.length)],
                    transparent: true,
                    opacity: 0.5 + Math.random() * 0.3
                });
                
                const particle = new THREE.Mesh(geometry, material);
                
                particle.position.x = (Math.random() - 0.5) * 20;
                particle.position.y = (Math.random() - 0.5) * 20;
                particle.position.z = (Math.random() - 0.5) * 20;
                
                particle.userData = {
                    speed: 0.005 + Math.random() * 0.01,
                    rotationSpeed: 0.005 + Math.random() * 0.01,
                    direction: new THREE.Vector3(
                        (Math.random() - 0.5) * 0.05,
                        (Math.random() - 0.5) * 0.05,
                        (Math.random() - 0.5) * 0.05
                    )
                };
                
                particles.add(particle);
            }
            
            camera.position.z = 15;
            
            function animate() {
                requestAnimationFrame(animate);
                
                const rotationFactor = window.innerWidth < 768 ? 0.5 : 1;
                
                particles.rotation.y += 0.001 * rotationFactor;
                particles.rotation.x += 0.0005 * rotationFactor;
                
                particles.children.forEach(particle => {
                    particle.rotation.x += particle.userData.rotationSpeed * rotationFactor;
                    particle.rotation.y += particle.userData.rotationSpeed * rotationFactor;
                    
                    particle.position.add(particle.userData.direction);
                    
                    if (Math.abs(particle.position.x) > 10) particle.userData.direction.x *= -1;
                    if (Math.abs(particle.position.y) > 10) particle.userData.direction.y *= -1;
                    if (Math.abs(particle.position.z) > 10) particle.userData.direction.z *= -1;
                });
                
                renderer.render(scene, camera);
            }
            
            animate();
            
            function handleResize() {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
                
                const isMobile = window.innerWidth < 768;
                particles.children.forEach((particle, index) => {
                    if (isMobile && index > 30) {
                        particle.visible = false;
                    } else {
                        particle.visible = true;
                    }
                });
            }
            
            window.addEventListener('resize', handleResize);
            
            function handleInteraction(clientX, clientY) {
                const mouseX = (clientX / window.innerWidth) * 2 - 1;
                const mouseY = -(clientY / window.innerHeight) * 2 + 1;
                
                const effectFactor = window.innerWidth < 768 ? 0.05 : 0.1;
                
                particles.rotation.y = mouseX * effectFactor;
                particles.rotation.x = mouseY * effectFactor;
            }
            
            document.addEventListener('mousemove', function(event) {
                handleInteraction(event.clientX, event.clientY);
            });
            
            document.addEventListener('touchmove', function(event) {
                if (event.touches.length > 0) {
                    handleInteraction(event.touches[0].clientX, event.touches[0].clientY);
                }
            });

        
            const fileInput = document.getElementById('profile_picture');
            const profilePicturePreview = document.getElementById('profile-picture-preview');

           
            profilePicturePreview.src = 'https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.jpg?s=612x612&w=0&k=20&c=dhV2p1JwmloBTOaGAtaA3AW1KSnjsdMt7-U_3EZElZ0=';

            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileType = file.type;
                    const fileSize = file.size;
                    const allowedTypes = ['image/jpeg', 'image/png'];
                    const maxSize = 2 * 1024 * 1024; // 2MB

                    if (!allowedTypes.includes(fileType)) {
                        alert('Only JPG and PNG files are allowed.');
                        this.value = '';
                        profilePicturePreview.src = 'https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.jpg?s=612x612&w=0&k=20&c=dhV2p1JwmloBTOaGAtaA3AW1KSnjsdMt7-U_3EZElZ0=';
                        return;
                    }

                    if (fileSize > maxSize) {
                        alert('Profile picture must be less than 2MB.');
                        this.value = '';
                        profilePicturePreview.src = 'https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.jpg?s=612x612&w=0&k=20&c=dhV2p1JwmloBTOaGAtaA3AW1KSnjsdMt7-U_3EZElZ0=';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePicturePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    profilePicturePreview.src = 'https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.jpg?s=612x612&w=0&k=20&c=dhV2p1JwmloBTOaGAtaA3AW1KSnjsdMt7-U_3EZElZ0=';
                }
            });

            // Client-side form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long.');
                    return false;
                }

                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
