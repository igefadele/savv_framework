<?php
use Savv\Controllers\{SystemController, BlogController, PageController};

// Route for manifest.json
router()->get('manifest.json', [SystemController::class, 'getManifestFile']);

// sw.js Route (With your specific origin and navigate logic)
router()->get('sw.js', [SystemController::class, 'getServiceWorkerFile']);

// A catch-all route to serve any of the user's asset which serve does not catch
router()->get('/assets/{$path}', [SystemController::class, 'serveAsset']);

// For any internal framework asset
router()->get('/savv-assets/{path}', [SystemController::class, 'getLocalAsset']);

// Sync all posts
router()->get('/sync-post/{slug}', [BlogController::class, 'syncPost']);

// Sync all posts
router()->get('/sync-posts', [BlogController::class, 'syncAllPosts']);

// generate a post cache html file
router()->get('/cache-post/{slug}', [BlogController::class, 'cachePost']);

// generate cache for all posts
router()->get('/cache-posts', [BlogController::class, 'cacheAllPosts']);

// Cache routes for faster performance
router()->get('/cache-routes', [PageController::class, 'cacheRoutes']);

// Cache a page using its uri
router()->get('/cache-page/{uri}', [PageController::class, 'cachePage']);

// Cache pages
router()->get('/cache-pages', [PageController::class, 'cacheAllPages']);
