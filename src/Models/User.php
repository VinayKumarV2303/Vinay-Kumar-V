<?php

namespace InstagramClone\Models;

use InstagramClone\Config\Database;
use PDO;

class User
{
    private $db;
    private $table = 'users';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create(array $data): bool
    {
        $query = "INSERT INTO {$this->table} 
                  (username, email, password_hash, full_name) 
                  VALUES (:username, :email, :password_hash, :full_name)";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':full_name' => $data['full_name']
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':username' => $username]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['full_name', 'bio', 'website', 'phone', 'gender', 'is_private'];
        $updateFields = [];
        $params = [':id' => $userId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($params);
    }

    public function updateProfilePicture(int $userId, string $filename): bool
    {
        $query = "UPDATE {$this->table} SET profile_picture = :profile_picture WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':profile_picture' => $filename,
            ':id' => $userId
        ]);
    }

    public function searchUsers(string $query, int $limit = 20): array
    {
        $searchQuery = "SELECT id, username, full_name, profile_picture, is_verified 
                       FROM {$this->table} 
                       WHERE username LIKE :query OR full_name LIKE :query 
                       ORDER BY username ASC 
                       LIMIT :limit";
        
        $stmt = $this->db->prepare($searchQuery);
        $stmt->bindValue(':query', "%{$query}%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getFollowersCount(int $userId): int
    {
        $query = "SELECT COUNT(*) as count FROM followers WHERE following_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function getFollowingCount(int $userId): int
    {
        $query = "SELECT COUNT(*) as count FROM followers WHERE follower_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function getPostsCount(int $userId): int
    {
        $query = "SELECT COUNT(*) as count FROM posts WHERE user_id = :user_id AND is_archived = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        $query = "SELECT COUNT(*) as count FROM followers 
                  WHERE follower_id = :follower_id AND following_id = :following_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':follower_id' => $followerId,
            ':following_id' => $followingId
        ]);
        
        $result = $stmt->fetch();
        return (int) $result['count'] > 0;
    }

    public function follow(int $followerId, int $followingId): bool
    {
        $query = "INSERT INTO followers (follower_id, following_id) VALUES (:follower_id, :following_id)";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':follower_id' => $followerId,
            ':following_id' => $followingId
        ]);
    }

    public function unfollow(int $followerId, int $followingId): bool
    {
        $query = "DELETE FROM followers WHERE follower_id = :follower_id AND following_id = :following_id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':follower_id' => $followerId,
            ':following_id' => $followingId
        ]);
    }
}