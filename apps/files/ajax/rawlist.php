<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

// Init owncloud

require_once 'lib/template.php';

OCP\JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$mimetypes = isset($_GET['mimetypes']) ? array_unique(json_decode($_GET['mimetypes'], true)) : '';

// make filelist
$files = array();
// If a type other than directory is requested first load them.
if($mimetypes && !in_array('httpd/unix-directory', $mimetypes)) {
	foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, 'httpd/unix-directory' ) as $i ) {
		$i["date"] = OCP\Util::formatDate($i["mtime"] );
		$i['mimetype_icon'] = ($i['type'] == 'dir')
			? \mimetype_icon('dir')
			: \mimetype_icon($i['mimetype']);
		$files[] = $i;
	}
}

if (is_array($mimetypes) && count($mimetypes)) {
	foreach ($mimetypes as $mimetype) {
		foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, $mimetype ) as $i ) {
			$i["date"] = OCP\Util::formatDate($i["mtime"]);
			$i['mimetype_icon'] = $i['type'] == 'dir' ? \mimetype_icon('dir'): \mimetype_icon($i['mimetype']);
			$files[] = $i;
		}
	}
} else {
	foreach( \OC\Files\Filesystem::getDirectoryContent( $dir ) as $i ) {
		$i["date"] = OCP\Util::formatDate($i["mtime"]);
		$i['mimetype_icon'] = $i['type'] == 'dir' ? \mimetype_icon('dir'): \mimetype_icon($i['mimetype']);
		$files[] = $i;
	}
}

// Sort by name
function cmp($a, $b) {
	if ($a['name'] === $b['name']) {
		 return 0;
	}
	return ($a['name'] < $b['name']) ? -1 : 1;
}
uasort($files, 'cmp');

OC_JSON::success(array('data' => $files));
