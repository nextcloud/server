<?php

/**
 * ownCloud - user_migrate
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
OCP\App::checkAppEnabled('user_migrate');
if (isset($_POST['user_import'])) {
	$root = OC::$SERVERROOT . "/";
	$importname = "owncloud_import_" . date("y-m-d_H-i-s");
	
	// Save data dir for later
	$datadir = OCP\Config::getSystemValue( 'datadirectory' );
	
	// Copy the uploaded file
	$from = $_FILES['owncloud_import']['tmp_name'];
	$to = get_temp_dir().'/'.$importname.'.zip';
	if( !move_uploaded_file( $from, $to ) ){

		$error = array('error'=>'Failed to move the uploaded file','hint'=>'Try checking the permissions of the '.get_temp_dir().' dir.');
		OCP\Util::writeLog( 'user_migrate', "Failed to copy the uploaded file", OCP\Util::ERROR );
		$tmpl = new OCP\Template('user_migrate', 'settings');
		$tmpl->assign('error',$error);
    	//return $tmpl->fetchPage();
	}
		

	$response = json_decode( OC_Migrate::import( $to, 'user' ) );
	if( !$response->success ){
		$error = array('error'=>'There was an error while importing the user!','hint'=>'Please check the logs for a more detailed explaination');
		$tmpl = new OCP\Template('user_migrate', 'settings');
		$tmpl->assign('error',$error);
    	//return $tmpl->fetchPage();	
	} else {
		// Check import status
		foreach( $response->data as $app => $status ){
			if( $status != 'true' ){
				// It failed for some reason
				if( $status == 'notsupported' ){
					$notsupported[] = $app;	
				} else if( !$status ){
					$failed[] = $app;
				}
			}	
		}
		// Any problems?
		if( isset( $notsupported ) || isset( $failed ) ){
			if( count( $failed ) > 0 ){
				$error = array('error'=>'Some app data failed to import','hint'=>'App data for: '.implode(', ', $failed).' failed to import.');
				$tmpl = new OCP\Template('user_migrate', 'settings');
				$tmpl->assign('error',$error);
    			//return $tmpl->fetchPage();	
			} else if( count( $notsupported ) > 0 ){
				$error = array('error'=>'Some app data could not be imported, as the apps are not installed on this instance','hint'=>'App data for: '.implode(', ', $notsupported).' failed to import as they were not found. Please install the apps and try again');
				$tmpl = new OCP\Template('user_migrate', 'settings');
				$tmpl->assign('error',$error);
    			//return $tmpl->fetchPage();	
			}
		} else {
			// Went swimmingly!
			$tmpl = new OCP\Template('user_migrate', 'settings');
    		//return $tmpl->fetchPage();	
		}

	}
		
} else {
	// fill template
	$tmpl = new OCP\Template('user_migrate', 'settings');
	return $tmpl->fetchPage();
}