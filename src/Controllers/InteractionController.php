<?php

namespace InstagramClone\Controllers;

use InstagramClone\Models\Like;
use InstagramClone\Models\Comment;
use InstagramClone\Utils\Response;
use InstagramClone\Utils\Validator;

class InteractionController
{
    private $likeModel;
    private $commentModel;

    public function __construct()
    {
        $this->likeModel = new Like();
        $this->commentModel = new Comment();
    }

    public function likePost(int $postId): void
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

        if ($this->likeModel->likePost($userId, $postId)) {
            Response::success('Post liked successfully');
        } else {
            Response::error('Failed to like post or already liked', 400);
        }
    }

    public function unlikePost(int $postId): void
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

        if ($this->likeModel->unlikePost($userId, $postId)) {
            Response::success('Post unliked successfully');
        } else {
            Response::error('Failed to unlike post or not liked', 400);
        }
    }

    public function getPostLikes(int $postId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);

        $likes = $this->likeModel->getPostLikes($postId, $limit, $offset);

        Response::success('Post likes retrieved successfully', [
            'likes' => $likes
        ]);
    }

    public function createComment(int $postId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!$this->isAuthenticated()) {
            Response::error('Not authenticated', 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $validator = new Validator();
        $validator->required(['content'], $data);
        $validator->minLength('content', $data['content'] ?? '', 1);
        $validator->maxLength('content', $data['content'] ?? '', 500);

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        $commentData = [
            'user_id' => $_SESSION['user_id'],
            'post_id' => $postId,
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null
        ];

        $commentId = $this->commentModel->create($commentData);

        if ($commentId) {
            $comment = $this->commentModel->findById($commentId);
            Response::success('Comment created successfully', [
                'comment' => $this->formatCommentResponse($comment)
            ]);
        } else {
            Response::error('Failed to create comment', 500);
        }
    }

    public function getPostComments(int $postId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);

        $comments = $this->commentModel->getPostComments($postId, $limit, $offset);
        $formattedComments = array_map([$this, 'formatCommentResponse'], $comments);

        Response::success('Comments retrieved successfully', [
            'comments' => $formattedComments
        ]);
    }

    public function updateComment(int $commentId): void
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
        
        // Validate input
        $validator = new Validator();
        $validator->required(['content'], $data);
        $validator->minLength('content', $data['content'], 1);
        $validator->maxLength('content', $data['content'], 500);

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        $userId = $_SESSION['user_id'];

        if ($this->commentModel->update($commentId, $userId, $data['content'])) {
            $comment = $this->commentModel->findById($commentId);
            Response::success('Comment updated successfully', [
                'comment' => $this->formatCommentResponse($comment)
            ]);
        } else {
            Response::error('Failed to update comment or not authorized', 403);
        }
    }

    public function deleteComment(int $commentId): void
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

        if ($this->commentModel->delete($commentId, $userId)) {
            Response::success('Comment deleted successfully');
        } else {
            Response::error('Failed to delete comment or not authorized', 403);
        }
    }

    public function likeComment(int $commentId): void
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

        if ($this->commentModel->likeComment($userId, $commentId)) {
            Response::success('Comment liked successfully');
        } else {
            Response::error('Failed to like comment or already liked', 400);
        }
    }

    public function unlikeComment(int $commentId): void
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

        if ($this->commentModel->unlikeComment($userId, $commentId)) {
            Response::success('Comment unliked successfully');
        } else {
            Response::error('Failed to unlike comment or not liked', 400);
        }
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    private function formatCommentResponse(array $comment): array
    {
        // Add like status for current user
        if ($this->isAuthenticated()) {
            $comment['is_liked'] = $this->commentModel->isCommentLiked($_SESSION['user_id'], $comment['id']);
        } else {
            $comment['is_liked'] = false;
        }

        // Format replies if they exist
        if (isset($comment['replies'])) {
            $comment['replies'] = array_map([$this, 'formatCommentResponse'], $comment['replies']);
        }

        return $comment;
    }
}