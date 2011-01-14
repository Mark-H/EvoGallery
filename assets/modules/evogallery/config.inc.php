<?php
$params['imageSize'] = isset($imageSize) ? $imageSize : 940;
	// Max dimension, in pixels, for the full-size images

$params['imageQuality'] = isset($imageQuality) ? $imageQuality : 85;
	// Quality for generated images (1-100)

$params['thumbSize'] = isset($thumbSize) ? $thumbSize : 175;
	// Max dimension, in pixels, for the generated thumbnail images

$params['thumbQuality'] = isset($thumbQuality) ? $thumbQuality : 75;
	// Quality for generated thumbnails (1-100)

$params['docId'] = isset($docId) ? $docId : 0;
	// Id of the document root to begin listing galleries for (0 for all documents)

$params['savePath'] = $modx->config['base_path'] . 'assets/galleries';
	// Full system path to location of product images

$params['modulePath'] = $modx->config['base_path'] . 'assets/modules/evogallery/';
	// Path to the module directory
?>