<?php

namespace InstagramClone\Models;

use InstagramClone\Config\Database;
use PDO;

class Comment
{
    private $db;
    private $table = 'comments';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create(array $data): ?int
    {
        // Start transaction
        $this->db->beginTransaction();

        try {
            $query = "INSERT INTO {$this->table} (user_id, post_id, parent_id, content) 
                      VALUES (:user_id, :post_id, :parent_id, :content)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':post_id' => $data['post_id'],
                ':parent_id' => $data['parent_id'] ?? null,
                ':content' => $data['content']
            ]);

            $commentId = (int) $this->db->lastInsertId();

            // Update post comments count
            $postQuery = "UPDATE posts SET comments_count = comments_count + 1 WHERE id = :post_id";
            $postStmt = $this->db->prepare($postQuery);
            $postStmt->execute([':post_id' => $data['post_id']]);

            $this->db->commit();
            return $commentId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    public function findById(int $commentId): ?array
    {
        $query = "SELECT c.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.id = :comment_id
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':comment_id' => $commentId]);
        
        $comment = $stmt->fetch();
        return $comment ?: null;
    }

    public function getPostComments(int $postId, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT c.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.post_id = :post_id AND c.parent_id IS NULL
                  ORDER BY c.created_at ASC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $comments = $stmt->fetchAll();

        // Get replies for each comment
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->getCommentReplies($comment['id']);
        }

        return $comments;
    }

    public function getCommentReplies(int $commentId, int $limit = 10): array
    {
        $query = "SELECT c.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.parent_id = :parent_id
                  ORDER BY c.created_at ASC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':parent_id', $commentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function update(int $commentId, int $userId, string $content): bool
    {
        $query = "UPDATE {$this->table} SET content = :content 
                  WHERE id = :comment_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':content' => $content,
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);
    }

    public function delete(int $commentId, int $userId): bool
    {
        // Start transaction
        $this->db->beginTransaction();

        try {
            // Get comment to check ownership and get post_id
            $comment = $this->findById($commentId);
            if (!$comment || $comment['user_id'] != $userId) {
                $this->db->rollBack();
                return false;
            }

            // Delete comment and all its replies
            $query = "DELETE FROM {$this->table} WHERE id = :comment_id OR parent_id = :comment_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':comment_id' => $commentId]);

            // Update post comments count
            $postQuery = "UPDATE posts SET comments_count = GREATEST(0, comments_count - 1) 
                         WHERE id = :post_id";
            $postStmt = $this->db->prepare($postQuery);
            $postStmt->execute([':post_id' => $comment['post_id']]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function likeComment(int $userId, int $commentId): bool
    {
        // Start transaction
        $this->db->beginTransaction();

        try {
            // Check if already liked
            if ($this->isCommentLiked($userId, $commentId)) {
                $this->db->rollBack();
                return false;
            }

            // Insert like
            $query = "INSERT INTO comment_likes (user_id, comment_id) VALUES (:user_id, :comment_id)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId]);

            // Update comment likes count
            $commentQuery = "UPDATE {$this->table} SET likes_count = likes_count + 1 WHERE id = :comment_id";
            $commentStmt = $this->db->prepare($commentQuery);
            $commentStmt->execute([':comment_id' => $commentId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function unlikeComment(int $userId, int $commentId): bool
    {
        // Start transaction
        $this->db->beginTransaction();

        try {
            // Check if liked
            if (!$this->isCommentLiked($userId, $commentId)) {
                $this->db->rollBack();
                return false;
            }

            // Remove like
            $query = "DELETE FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId]);

            // Update comment likes count
            $commentQuery = "UPDATE {$this->table} SET likes_count = GREATEST(0, likes_count - 1) 
                           WHERE id = :comment_id";
            $commentStmt = $this->db->prepare($commentQuery);
            $commentStmt->execute([':comment_id' => $commentId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function isCommentLiked(int $userId, int $commentId): bool
    {
        $query = "SELECT COUNT(*) as count FROM comment_likes 
                  WHERE user_id = :user_id AND comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId]);
        
        $result = $stmt->fetch();
        return (int) $result['count'] > 0;
    }
}