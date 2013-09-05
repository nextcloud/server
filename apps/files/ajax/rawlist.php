<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

// Init owncloud

require_once 'lib/template.php';

OCP\JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$mimetype = isset($_GET['mimetype']) ? $_GET['mimetype'] : '';
$mimetypeList = isset($_GET['mimetype_list']) ? json_decode($_GET['mimetype_list'], true) : '';

// make filelist
$files = array();
// If a type other than directory is requested first load them.
if( ($mimetype || $mimetypeList) && strpos($mimetype, 'httpd/unix-directory') === false) {
	foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, 'httpd/unix-directory' ) as $i ) {
		$i["date"] = OCP\Util::formatDate($i["mtime"] );
		$i['mimetype_icon'] = $i['type'] == 'dir' ? \mimetype_icon('dir'): \mimetype_icon($i['mimetype']);
		$files[] = $i;
	}
}

if (is_array($mimetypeList)) {
	foreach ($mimetypeList as $mimetype) {
		foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, $mimetype ) as $i ) {
			$i["date"] = OCP\Util::formatDate($i["mtime"]);
			$i['mimetype_icon'] = $i['type'] == 'dir' ? \mimetype_icon('dir'): \mimetype_icon($i['mimetype']);
			$files[] = $i;
		}
	}
} else {
	foreach( \OC\Files\Filesystem::getDirectoryContent( $dir, $mimetype ) as $i ) {
		$i["date"] = OCP\Util::formatDate($i["mtime"] );
		$i['mimetype_icon'] = $i['type'] == 'dir' ? \mimetype_icon('dir'): \mimetype_icon($i['mimetype']);
		$files[] = $i;
	}
}

OCP\JSON::success(array('data' => $files));
