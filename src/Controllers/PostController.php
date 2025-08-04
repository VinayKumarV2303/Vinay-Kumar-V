<?php

namespace InstagramClone\Controllers;

use InstagramClone\Models\Post;
use InstagramClone\Models\Like;
use InstagramClone\Utils\Response;
use InstagramClone\Utils\Validator;
use InstagramClone\Utils\FileUpload;

class PostController
{
    private $postModel;
    private $likeModel;

    public function __construct()
    {
        $this->postModel = new Post();
        $this->likeModel = new Like();
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        // Handle file upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Image is required', 400);
            return;
        }

        $fileUpload = new FileUpload();
        $uploadResult = $fileUpload->uploadImage($_FILES['image']);

        if (!$uploadResult['success']) {
            Response::error($uploadResult['message'], 400);
            return;
        }

        // Get form data
        $caption = $_POST['caption'] ?? '';
        $location = $_POST['location'] ?? '';

        // Validate input
        $validator = new Validator();
        $validator->maxLength('caption', $caption, 2200);
        $validator->maxLength('location', $location, 100);

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        // Create post
        $postData = [
            'user_id' => $_SESSION['user_id'],
            'image_url' => $uploadResult['filename'],
            'caption' => $caption,
            'location' => $location
        ];

        $postId = $this->postModel->create($postData);

        if ($postId) {
            // Extract and save hashtags
            $this->extractAndSaveHashtags($caption, $postId);
            
            $post = $this->postModel->findById($postId);
            Response::success('Post created successfully', [
                'post' => $this->formatPostResponse($post)
            ]);
        } else {
            Response::error('Failed to create post', 500);
        }
    }

    public function getPost(int $postId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $post = $this->postModel->findById($postId);

        if (!$post) {
            Response::error('Post not found', 404);
            return;
        }

        Response::success('Post retrieved successfully', [
            'post' => $this->formatPostResponse($post)
        ]);
    }

    public function getUserPosts(int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);

        $posts = $this->postModel->getUserPosts($userId, $limit, $offset);
        $formattedPosts = array_map([$this, 'formatPostResponse'], $posts);

        Response::success('User posts retrieved successfully', [
            'posts' => $formattedPosts
        ]);
    }

    public function getFeed(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);

        $posts = $this->postModel->getFeed($_SESSION['user_id'], $limit, $offset);
        $formattedPosts = array_map([$this, 'formatPostResponse'], $posts);

        Response::success('Feed retrieved successfully', [
            'posts' => $formattedPosts
        ]);
    }

    public function getExplorePosts(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 30);
        $posts = $this->postModel->getExplorePosts($_SESSION['user_id'], $limit);
        $formattedPosts = array_map([$this, 'formatPostResponse'], $posts);

        Response::success('Explore posts retrieved successfully', [
            'posts' => $formattedPosts
        ]);
    }

    public function updatePost(int $postId): void
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
        if (isset($data['caption'])) {
            $validator->maxLength('caption', $data['caption'], 2200);
        }

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        if (isset($data['caption'])) {
            if ($this->postModel->updateCaption($postId, $userId, $data['caption'])) {
                $post = $this->postModel->findById($postId);
                Response::success('Post updated successfully', [
                    'post' => $this->formatPostResponse($post)
                ]);
            } else {
                Response::error('Failed to update post', 500);
            }
        } else {
            Response::error('No valid fields to update', 400);
        }
    }

    public function deletePost(int $postId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $userId = $_SESSION['user_id'];

        if ($this->postModel->delete($postId, $userId)) {
            Response::success('Post deleted successfully');
        } else {
            Response::error('Failed to delete post or post not found', 404);
        }
    }

    public function toggleComments(int $postId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $userId = $_SESSION['user_id'];

        if ($this->postModel->toggleComments($postId, $userId)) {
            $post = $this->postModel->findById($postId);
            Response::success('Comments setting updated', [
                'post' => $this->formatPostResponse($post)
            ]);
        } else {
            Response::error('Failed to update comments setting', 500);
        }
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    private function formatPostResponse(array $post): array
    {
        // Add like status for current user
        if ($this->isAuthenticated()) {
            $post['is_liked'] = $this->likeModel->isLiked($_SESSION['user_id'], $post['id']);
        } else {
            $post['is_liked'] = false;
        }

        return $post;
    }

    private function extractAndSaveHashtags(string $caption, int $postId): void
    {
        preg_match_all('/#(\w+)/', $caption, $matches);
        
        if (!empty($matches[1])) {
            // This would require a Hashtag model to implement fully
            // For now, we'll skip the hashtag implementation
        }
    }
}