<?php

/**
 * ownCloud - admin_migrate
 *
 * @author Thomas Schmidt
 * @copyright 2011 Thomas Schmidt tom@opensuse.org
 * @author Tom Needham
 * @copyright 2012 Tom Needham tom@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
OCP\User::checkAdminUser();
OCP\App::checkAppEnabled('admin_migrate');

// Export?
if (isset($_POST['admin_export'])) {
	// Create the export zip
	$response = json_decode( OC_Migrate::export( null, $_POST['export_type'] ) );
	if( !$response->success ){
		// Error
		die('error');	
	} else {
		$path = $response->data;
		// Download it
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=" . basename($path));
		header("Content-Length: " . filesize($path));
		@ob_end_clean();
		readfile( $path );
		unlink( $path );
	}
// Import?
} else if( isset($_POST['admin_import']) ){
	$from = $_FILES['owncloud_import']['tmp_name'];

	if( !OC_Migrate::import( $from, 'instance' ) ){
		die('failed');	
	}
		
} else {
// fill template
    $tmpl = new OCP\Template('admin_migrate', 'settings');
    return $tmpl->fetchPage();
}