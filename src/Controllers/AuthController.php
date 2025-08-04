<?php

namespace InstagramClone\Controllers;

use InstagramClone\Models\User;
use InstagramClone\Utils\Response;
use InstagramClone\Utils\Validator;

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $validator = new Validator();
        $validator->required(['username', 'email', 'password', 'full_name'], $data);
        $validator->email('email', $data['email'] ?? '');
        $validator->minLength('password', $data['password'] ?? '', 6);
        $validator->minLength('username', $data['username'] ?? '', 3);
        $validator->maxLength('username', $data['username'] ?? '', 30);

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        // Check if user already exists
        if ($this->userModel->findByEmail($data['email'])) {
            Response::error('Email already exists', 400);
            return;
        }

        if ($this->userModel->findByUsername($data['username'])) {
            Response::error('Username already exists', 400);
            return;
        }

        // Create user
        if ($this->userModel->create($data)) {
            $user = $this->userModel->findByEmail($data['email']);
            $this->createSession($user);
            
            Response::success('User registered successfully', [
                'user' => $this->formatUserResponse($user)
            ]);
        } else {
            Response::error('Failed to create user', 500);
        }
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $validator = new Validator();
        $validator->required(['login', 'password'], $data);

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        $login = $data['login']; // Can be email or username
        $password = $data['password'];

        // Find user by email or username
        $user = $this->userModel->findByEmail($login) ?? $this->userModel->findByUsername($login);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
            return;
        }

        $this->createSession($user);
        
        Response::success('Login successful', [
            'user' => $this->formatUserResponse($user)
        ]);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        session_destroy();
        Response::success('Logged out successfully');
    }

    public function me(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);

        if (!$user) {
            Response::error('User not found', 404);
            return;
        }

        Response::success('User data retrieved', [
            'user' => $this->formatUserResponse($user)
        ]);
    }

    public function updateProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user_id'];

        // Validate input
        $validator = new Validator();
        if (isset($data['full_name'])) {
            $validator->minLength('full_name', $data['full_name'], 1);
        }
        if (isset($data['bio'])) {
            $validator->maxLength('bio', $data['bio'], 500);
        }
        if (isset($data['website'])) {
            $validator->url('website', $data['website']);
        }

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        if ($this->userModel->updateProfile($userId, $data)) {
            $user = $this->userModel->findById($userId);
            Response::success('Profile updated successfully', [
                'user' => $this->formatUserResponse($user)
            ]);
        } else {
            Response::error('Failed to update profile', 500);
        }
    }

    private function createSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    private function formatUserResponse(array $user): array
    {
        unset($user['password_hash']);
        
        // Add counts
        $user['followers_count'] = $this->userModel->getFollowersCount($user['id']);
        $user['following_count'] = $this->userModel->getFollowingCount($user['id']);
        $user['posts_count'] = $this->userModel->getPostsCount($user['id']);
        
        return $user;
    }
}