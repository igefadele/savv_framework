<?php 

// Specific route
router()->get('/', function() {
    require ROOT_PATH . '/views/index.php';
});

// "Dynamic Discovery" logic as a fallback for web routes
router()->get('{slug}', function($slug) {
    $viewPath = ROOT_PATH . '/views/' . $slug . '.php';
    
    if (file_exists($viewPath)) {
        require $viewPath;
        return true; // Signal that we handled it
    }
    
    return false; // Let it fall through to WordPress
});