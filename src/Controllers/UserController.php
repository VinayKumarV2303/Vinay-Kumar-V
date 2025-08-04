<?php

namespace InstagramClone\Controllers;

use InstagramClone\Models\User;
use InstagramClone\Models\Post;
use InstagramClone\Utils\Response;
use InstagramClone\Utils\FileUpload;

class UserController
{
    private $userModel;
    private $postModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->postModel = new Post();
    }

    public function getUser(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $user = $this->userModel->findById($userId);

        if (!$user) {
            Response::error('User not found', 404);
            return;
        }

        // Add additional user data
        $userData = $this->formatUserResponse($user);

        // Check if current user is following this user
        if ($this->isAuthenticated()) {
            $currentUserId = $_SESSION['user_id'];
            $userData['is_following'] = $this->userModel->isFollowing($currentUserId, $userId);
            $userData['is_own_profile'] = $currentUserId === $userId;
        } else {
            $userData['is_following'] = false;
            $userData['is_own_profile'] = false;
        }

        Response::success('User retrieved successfully', [
            'user' => $userData
        ]);
    }

    public function getUserPosts(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 12);
        $offset = (int) ($_GET['offset'] ?? 0);

        $posts = $this->postModel->getUserPosts($userId, $limit, $offset);

        Response::success('User posts retrieved successfully', [
            'posts' => $posts
        ]);
    }

    public function followUser(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $currentUserId = $_SESSION['user_id'];

        if ($currentUserId === $userId) {
            Response::error('Cannot follow yourself', 400);
            return;
        }

        // Check if user exists
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Response::error('User not found', 404);
            return;
        }

        if ($this->userModel->follow($currentUserId, $userId)) {
            Response::success('User followed successfully');
        } else {
            Response::error('Failed to follow user or already following', 400);
        }
    }

    public function unfollowUser(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $currentUserId = $_SESSION['user_id'];

        if ($currentUserId === $userId) {
            Response::error('Cannot unfollow yourself', 400);
            return;
        }

        if ($this->userModel->unfollow($currentUserId, $userId)) {
            Response::success('User unfollowed successfully');
        } else {
            Response::error('Failed to unfollow user or not following', 400);
        }
    }

    public function getFollowers(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);

        $followers = $this->getFollowersList($userId, $limit, $offset);

        Response::success('Followers retrieved successfully', [
            'followers' => $followers
        ]);
    }

    public function getFollowing(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);

        $following = $this->getFollowingList($userId, $limit, $offset);

        Response::success('Following retrieved successfully', [
            'following' => $following
        ]);
    }

    public function uploadAvatar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Avatar image is required', 400);
            return;
        }

        $fileUpload = new FileUpload();
        $uploadResult = $fileUpload->uploadProfilePicture($_FILES['avatar']);

        if (!$uploadResult['success']) {
            Response::error($uploadResult['message'], 400);
            return;
        }

        $userId = $_SESSION['user_id'];
        
        if ($this->userModel->updateProfilePicture($userId, $uploadResult['filename'])) {
            $user = $this->userModel->findById($userId);
            Response::success('Avatar updated successfully', [
                'user' => $this->formatUserResponse($user),
                'avatar_url' => $uploadResult['url']
            ]);
        } else {
            Response::error('Failed to update avatar', 500);
        }
    }

    public function searchUsers(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $query = $_GET['q'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 20);

        if (empty($query)) {
            Response::error('Search query is required', 400);
            return;
        }

        $users = $this->userModel->searchUsers($query, $limit);

        // Add following status for each user if authenticated
        if ($this->isAuthenticated()) {
            $currentUserId = $_SESSION['user_id'];
            foreach ($users as &$user) {
                $user['is_following'] = $this->userModel->isFollowing($currentUserId, $user['id']);
            }
        }

        Response::success('Users retrieved successfully', [
            'users' => $users
        ]);
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

    private function getFollowersList(int $userId, int $limit, int $offset): array
    {
        // This would require additional database queries
        // Simplified implementation for now
        return [];
    }

    private function getFollowingList(int $userId, int $limit, int $offset): array
    {
        // This would require additional database queries
        // Simplified implementation for now
        return [];
    }
}