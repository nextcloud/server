<?php

// only need filesystem apps
$RUNTIME_APPTYPES = array('filesystem');

OCP\JSON::checkLoggedIn();
\OC::$session->close();

// Load the files
$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$mimetypes = isset($_GET['mimetypes']) ? json_decode($_GET['mimetypes'], true) : '';

// Clean up duplicates from array and deal with non-array requests
if (is_array($mimetypes)) {
	$mimetypes = array_unique($mimetypes);
} elseif (is_null($mimetypes)) {
	$mimetypes = array($_GET['mimetypes']);
}

// make filelist
$files = array();
/**
 * @var \OCP\Files\FileInfo[] $files
 */
// If a type other than directory is requested first load them.
if ($mimetypes && !in_array('httpd/unix-directory', $mimetypes)) {
	$files = array_merge($files, \OC\Files\Filesystem::getDirectoryContent($dir, 'httpd/unix-directory'));
}

if (is_array($mimetypes) && count($mimetypes)) {
	foreach ($mimetypes as $mimetype) {
		$files = array_merge($files, \OC\Files\Filesystem::getDirectoryContent($dir, $mimetype));
	}
} else {
	$files = array_merge($files, \OC\Files\Filesystem::getDirectoryContent($dir));
}
// Sort by name
usort($files, array('\OCA\Files\Helper', 'fileCmp'));

$result = array();
foreach ($files as $file) {
	$fileData = array();
	$fileData['directory'] = $dir;
	$fileData['name'] = $file->getName();
	$fileData['type'] = $file->getType();
	$fileData['path'] = $file['path'];
	$fileData['id'] = $file->getId();
	$fileData['size'] = $file->getSize();
	$fileData['mtime'] = $file->getMtime();
	$fileData['mimetype'] = $file->getMimetype();
	$fileData['isPreviewAvailable'] = \OC::$server->getPreviewManager()->isMimeSupported($file->getMimetype());
	$fileData["date"] = OCP\Util::formatDate($file->getMtime());
	$fileData['mimetype_icon'] = \OCA\Files\Helper::determineIcon($file);
	$result[] = $fileData;
}

OC_JSON::success(array('data' => $result));
