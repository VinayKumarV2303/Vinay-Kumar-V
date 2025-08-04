<?php

namespace InstagramClone\Models;

use InstagramClone\Config\Database;
use PDO;

class Like
{
    private $db;
    private $table = 'likes';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function likePost(int $userId, int $postId): bool
    {
        // Start transaction
        $this->db->beginTransaction();

        try {
            // Check if already liked
            if ($this->isLiked($userId, $postId)) {
                $this->db->rollBack();
                return false;
            }

            // Insert like
            $query = "INSERT INTO {$this->table} (user_id, post_id) VALUES (:user_id, :post_id)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);

            // Update post likes count
            $postQuery = "UPDATE posts SET likes_count = likes_count + 1 WHERE id = :post_id";
            $postStmt = $this->db->prepare($postQuery);
            $postStmt->execute([':post_id' => $postId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function unlikePost(int $userId, int $postId): bool
    {
        // Start transaction
        $this->db->beginTransaction();

        try {
            // Check if liked
            if (!$this->isLiked($userId, $postId)) {
                $this->db->rollBack();
                return false;
            }

            // Remove like
            $query = "DELETE FROM {$this->table} WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);

            // Update post likes count
            $postQuery = "UPDATE posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = :post_id";
            $postStmt = $this->db->prepare($postQuery);
            $postStmt->execute([':post_id' => $postId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function isLiked(int $userId, int $postId): bool
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                  WHERE user_id = :user_id AND post_id = :post_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
        
        $result = $stmt->fetch();
        return (int) $result['count'] > 0;
    }

    public function getPostLikes(int $postId, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT l.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} l
                  JOIN users u ON l.user_id = u.id
                  WHERE l.post_id = :post_id
                  ORDER BY l.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}