<?php
use Savv\Controllers\SystemController;

// Route for manifest.json
router()->get('manifest.json', [SystemController::class, 'getManifestFile']);

// sw.js Route (With your specific origin and navigate logic)
router()->get('sw.js', [SystemController::class, 'getServiceWorkerFile']);