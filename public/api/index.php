<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use InstagramClone\Config\Config;
use InstagramClone\Router\Router;

// Initialize configuration
Config::init();

// Set CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Initialize router with API prefix
    $router = new Router('/api');
    $router->setupRoutes();
    
    // Dispatch the request
    $router->dispatch();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => Config::get('APP_DEBUG') ? $e->getMessage() : 'Something went wrong'
    ]);
}