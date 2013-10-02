<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

OCP\JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$mimetypes = isset($_GET['mimetypes']) ? json_decode($_GET['mimetypes'], true) : '';

// Clean up duplicates from array and deal with non-array requests
if (is_array($mimetypes)) {
	$mimetypes = array_unique($mimetypes);
} elseif (is_null($mimetypes)) {
	$mimetypes = array($_GET['mimetypes']);
}

// make filelist
$files = array();
// If a type other than directory is requested first load them.
if($mimetypes && !in_array('httpd/unix-directory', $mimetypes)) {
	foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, 'httpd/unix-directory' ) as $file ) {
		$file['directory'] = $dir;
		$file['isPreviewAvailable'] = \OC::$server->getPreviewManager()->isMimeSupported($file['mimetype']);
		$file["date"] = OCP\Util::formatDate($file["mtime"]);
		$file['mimetype_icon'] = \OCA\Files\Helper::determineIcon($file);
		$files[] = $file;
	}
}

if (is_array($mimetypes) && count($mimetypes)) {
	foreach ($mimetypes as $mimetype) {
		foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, $mimetype ) as $file ) {
			$file['directory'] = $dir;
			$file['isPreviewAvailable'] = \OC::$server->getPreviewManager()->isMimeSupported($file['mimetype']);
			$file["date"] = OCP\Util::formatDate($file["mtime"]);
			$file['mimetype_icon'] = \OCA\Files\Helper::determineIcon($file);
			$files[] = $file;
		}
	}
} else {
	foreach( \OC\Files\Filesystem::getDirectoryContent( $dir ) as $file ) {
		$file['directory'] = $dir;
		$file['isPreviewAvailable'] = \OC::$server->getPreviewManager()->isMimeSupported($file['mimetype']);
		$file["date"] = OCP\Util::formatDate($file["mtime"]);
		$file['mimetype_icon'] = \OCA\Files\Helper::determineIcon($file);
		$files[] = $file;
	}
}

// Sort by name
usort($files, function ($a, $b) {
	if ($a['name'] === $b['name']) {
		 return 0;
	}
	return ($a['name'] < $b['name']) ? -1 : 1;
});

OC_JSON::success(array('data' => $files));
