<?php

namespace InstagramClone\Router;

use InstagramClone\Controllers\AuthController;
use InstagramClone\Controllers\PostController;
use InstagramClone\Controllers\UserController;
use InstagramClone\Controllers\InteractionController;
use InstagramClone\Utils\Response;

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->basePath . $path,
            'handler' => $handler
        ];
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if it exists
        if ($this->basePath && strpos($requestPath, $this->basePath) === 0) {
            $requestPath = substr($requestPath, strlen($this->basePath));
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                $params = $this->extractParams($route['path'], $requestPath);
                call_user_func_array($route['handler'], $params);
                return;
            }
        }

        Response::error('Route not found', 404);
    }

    private function matchPath(string $routePath, string $requestPath): bool
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';
        
        return preg_match($routePattern, $requestPath);
    }

    private function extractParams(string $routePath, string $requestPath): array
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';
        
        preg_match($routePattern, $requestPath, $matches);
        array_shift($matches); // Remove full match
        
        return array_map('intval', $matches); // Convert to integers for IDs
    }

    public function setupRoutes(): void
    {
        // Authentication routes
        $authController = new AuthController();
        $this->post('/auth/register', [$authController, 'register']);
        $this->post('/auth/login', [$authController, 'login']);
        $this->post('/auth/logout', [$authController, 'logout']);
        $this->get('/auth/me', [$authController, 'me']);
        $this->put('/auth/profile', [$authController, 'updateProfile']);

        // User routes
        $userController = new UserController();
        $this->get('/users/{id}', [$userController, 'getUser']);
        $this->get('/users/{id}/posts', [$userController, 'getUserPosts']);
        $this->post('/users/{id}/follow', [$userController, 'followUser']);
        $this->delete('/users/{id}/follow', [$userController, 'unfollowUser']);
        $this->get('/users/{id}/followers', [$userController, 'getFollowers']);
        $this->get('/users/{id}/following', [$userController, 'getFollowing']);
        $this->post('/users/upload-avatar', [$userController, 'uploadAvatar']);
        $this->get('/users/search', [$userController, 'searchUsers']);

        // Post routes
        $postController = new PostController();
        $this->post('/posts', [$postController, 'create']);
        $this->get('/posts/{id}', [$postController, 'getPost']);
        $this->put('/posts/{id}', [$postController, 'updatePost']);
        $this->delete('/posts/{id}', [$postController, 'deletePost']);
        $this->get('/posts', [$postController, 'getFeed']);
        $this->get('/explore', [$postController, 'getExplorePosts']);
        $this->post('/posts/{id}/toggle-comments', [$postController, 'toggleComments']);

        // Interaction routes (likes, comments)
        $interactionController = new InteractionController();
        $this->post('/posts/{id}/like', [$interactionController, 'likePost']);
        $this->delete('/posts/{id}/like', [$interactionController, 'unlikePost']);
        $this->get('/posts/{id}/likes', [$interactionController, 'getPostLikes']);
        
        $this->post('/posts/{id}/comments', [$interactionController, 'createComment']);
        $this->get('/posts/{id}/comments', [$interactionController, 'getPostComments']);
        $this->put('/comments/{id}', [$interactionController, 'updateComment']);
        $this->delete('/comments/{id}', [$interactionController, 'deleteComment']);
        $this->post('/comments/{id}/like', [$interactionController, 'likeComment']);
        $this->delete('/comments/{id}/like', [$interactionController, 'unlikeComment']);
    }
}