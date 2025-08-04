<?php

namespace InstagramClone\Models;

use InstagramClone\Config\Database;
use PDO;

class Post
{
    private $db;
    private $table = 'posts';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create(array $data): ?int
    {
        $query = "INSERT INTO {$this->table} (user_id, image_url, caption, location) 
                  VALUES (:user_id, :image_url, :caption, :location)";
        
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([
            ':user_id' => $data['user_id'],
            ':image_url' => $data['image_url'],
            ':caption' => $data['caption'] ?? null,
            ':location' => $data['location'] ?? null
        ])) {
            return (int) $this->db->lastInsertId();
        }
        
        return null;
    }

    public function findById(int $postId): ?array
    {
        $query = "SELECT p.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id = :post_id AND p.is_archived = FALSE
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':post_id' => $postId]);
        
        $post = $stmt->fetch();
        return $post ?: null;
    }

    public function getUserPosts(int $userId, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT p.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = :user_id AND p.is_archived = FALSE
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getFeed(int $userId, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT p.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} p
                  JOIN users u ON p.user_id = u.id
                  WHERE (p.user_id = :user_id 
                         OR p.user_id IN (
                             SELECT following_id 
                             FROM followers 
                             WHERE follower_id = :user_id
                         ))
                  AND p.is_archived = FALSE
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getExplorePosts(int $userId, int $limit = 30): array
    {
        $query = "SELECT p.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id != :user_id 
                  AND p.user_id NOT IN (
                      SELECT following_id 
                      FROM followers 
                      WHERE follower_id = :user_id
                  )
                  AND p.is_archived = FALSE
                  ORDER BY p.likes_count DESC, p.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function delete(int $postId, int $userId): bool
    {
        $query = "DELETE FROM {$this->table} WHERE id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId
        ]);
    }

    public function archive(int $postId, int $userId): bool
    {
        $query = "UPDATE {$this->table} SET is_archived = TRUE WHERE id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId
        ]);
    }

    public function updateCaption(int $postId, int $userId, string $caption): bool
    {
        $query = "UPDATE {$this->table} SET caption = :caption WHERE id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':caption' => $caption,
            ':post_id' => $postId,
            ':user_id' => $userId
        ]);
    }

    public function toggleComments(int $postId, int $userId): bool
    {
        $query = "UPDATE {$this->table} SET comments_enabled = NOT comments_enabled 
                  WHERE id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId
        ]);
    }

    public function incrementLikesCount(int $postId): bool
    {
        $query = "UPDATE {$this->table} SET likes_count = likes_count + 1 WHERE id = :post_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([':post_id' => $postId]);
    }

    public function decrementLikesCount(int $postId): bool
    {
        $query = "UPDATE {$this->table} SET likes_count = GREATEST(0, likes_count - 1) WHERE id = :post_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([':post_id' => $postId]);
    }

    public function incrementCommentsCount(int $postId): bool
    {
        $query = "UPDATE {$this->table} SET comments_count = comments_count + 1 WHERE id = :post_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([':post_id' => $postId]);
    }

    public function decrementCommentsCount(int $postId): bool
    {
        $query = "UPDATE {$this->table} SET comments_count = GREATEST(0, comments_count - 1) WHERE id = :post_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([':post_id' => $postId]);
    }

    public function searchPosts(string $query, int $limit = 20): array
    {
        $searchQuery = "SELECT p.*, u.username, u.full_name, u.profile_picture, u.is_verified
                       FROM {$this->table} p
                       JOIN users u ON p.user_id = u.id
                       WHERE (p.caption LIKE :query OR p.location LIKE :query)
                       AND p.is_archived = FALSE
                       ORDER BY p.likes_count DESC, p.created_at DESC
                       LIMIT :limit";
        
        $stmt = $this->db->prepare($searchQuery);
        $stmt->bindValue(':query', "%{$query}%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getPostsByHashtag(string $hashtag, int $limit = 20): array
    {
        $query = "SELECT p.*, u.username, u.full_name, u.profile_picture, u.is_verified
                  FROM {$this->table} p
                  JOIN users u ON p.user_id = u.id
                  JOIN post_hashtags ph ON p.id = ph.post_id
                  JOIN hashtags h ON ph.hashtag_id = h.id
                  WHERE h.name = :hashtag AND p.is_archived = FALSE
                  ORDER BY p.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':hashtag', $hashtag, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}