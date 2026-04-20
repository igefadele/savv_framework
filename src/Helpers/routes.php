<?php
use Savv\Controllers\SystemController;

// Route for manifest.json
router()->get('manifest.json', [SystemController::class, 'getManifestFile']);

// sw.js Route (With your specific origin and navigate logic)
router()->get('sw.js', [SystemController::class, 'getServiceWorkerFile']);

// A catch-all route to serve any of the user's asset which serve does not catch
router()->get('/assets/(.*)', [SystemController::class, 'serveAsset']);

// For Any local asset
router()->get('savv-asset/{$path}', [SystemController::class, 'getLocalAsset']);