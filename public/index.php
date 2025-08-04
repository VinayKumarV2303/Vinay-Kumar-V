<?php

require_once __DIR__ . '/../vendor/autoload.php';

use InstagramClone\Config\Config;

// Initialize configuration
Config::init();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Config::get('APP_NAME', 'Instagram Clone') ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="app">
        <!-- Loading spinner -->
        <div id="loading-screen" class="loading-screen">
            <div class="spinner"></div>
        </div>

        <!-- Navigation -->
        <nav id="navbar" class="navbar" style="display: none;">
            <div class="nav-container">
                <div class="nav-logo">
                    <i class="fab fa-instagram"></i>
                    <span>Instagram</span>
                </div>
                <div class="nav-search">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search" id="searchInput">
                    </div>
                </div>
                <div class="nav-icons">
                    <button class="nav-icon" id="homeBtn">
                        <i class="fas fa-home"></i>
                    </button>
                    <button class="nav-icon" id="exploreBtn">
                        <i class="fas fa-compass"></i>
                    </button>
                    <button class="nav-icon" id="addPostBtn">
                        <i class="fas fa-plus-square"></i>
                    </button>
                    <button class="nav-icon" id="profileBtn">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <button class="nav-icon" id="logoutBtn">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Auth Forms -->
        <div id="auth-container" class="auth-container">
            <!-- Login Form -->
            <div id="login-form" class="auth-form">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="fab fa-instagram"></i>
                        <h1>Instagram</h1>
                    </div>
                    <form id="loginForm">
                        <div class="form-group">
                            <input type="text" id="loginUsername" placeholder="Phone number, username, or email" required>
                        </div>
                        <div class="form-group">
                            <input type="password" id="loginPassword" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn-primary">Log In</button>
                    </form>
                    <div class="auth-divider">
                        <span>OR</span>
                    </div>
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="#" id="showRegister">Sign up</a></p>
                    </div>
                </div>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="auth-form" style="display: none;">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="fab fa-instagram"></i>
                        <h1>Instagram</h1>
                        <p>Sign up to see photos and videos from your friends.</p>
                    </div>
                    <form id="registerForm">
                        <div class="form-group">
                            <input type="email" id="registerEmail" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" id="registerFullName" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <input type="text" id="registerUsername" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" id="registerPassword" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn-primary">Sign Up</button>
                    </form>
                    <div class="auth-footer">
                        <p>Have an account? <a href="#" id="showLogin">Log in</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main id="main-content" class="main-content" style="display: none;">
            <!-- Feed View -->
            <div id="feed-view" class="view">
                <div class="feed-container">
                    <div class="posts-feed" id="postsContainer">
                        <!-- Posts will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Explore View -->
            <div id="explore-view" class="view" style="display: none;">
                <div class="explore-container">
                    <div class="explore-grid" id="exploreGrid">
                        <!-- Explore posts will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Profile View -->
            <div id="profile-view" class="view" style="display: none;">
                <div class="profile-container">
                    <div class="profile-header" id="profileHeader">
                        <!-- Profile info will be loaded here -->
                    </div>
                    <div class="profile-posts" id="profilePosts">
                        <!-- User posts will be loaded here -->
                    </div>
                </div>
            </div>
        </main>

        <!-- Post Upload Modal -->
        <div id="uploadModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Create new post</h3>
                    <button class="close-btn" id="closeUploadModal">&times;</button>
                </div>
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="upload-area">
                        <input type="file" id="postImage" accept="image/*" required>
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag photos here or click to select</p>
                        </div>
                        <img id="imagePreview" style="display: none;">
                    </div>
                    <div class="form-group">
                        <textarea id="postCaption" placeholder="Write a caption..." rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="text" id="postLocation" placeholder="Add location">
                    </div>
                    <button type="submit" class="btn-primary">Share</button>
                </form>
            </div>
        </div>

        <!-- Post Modal -->
        <div id="postModal" class="modal">
            <div class="modal-content post-modal-content">
                <button class="close-btn" id="closePostModal">&times;</button>
                <div id="postModalContent">
                    <!-- Individual post will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="js/api.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/feed.js"></script>
    <script src="js/upload.js"></script>
    <script src="js/profile.js"></script>
    <script src="js/app.js"></script>
</body>
</html>