<?php

/**
 * ownCloud - History page of the Versions App
 *
 * @author Frank Karlitschek
 * @copyright 2011 Frank Karlitschek karlitschek@kde.org
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
 * You should have received a copy of the GNU Lesser General Public 
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

OCP\User::checkLoggedIn( );
OCP\Util::addStyle('files_versions','versions');
$tmpl = new OCP\Template( 'files_versions', 'history', 'user' );

if ( isset( $_GET['path'] ) ) {

	$path = $_GET['path'];
	$path = strip_tags( $path );
	$tmpl->assign( 'path', $path );

	// roll back to old version if button clicked
        if( isset( $_GET['revert'] ) ) {
        	
        	if( \OCA_Versions\Storage::rollback( $path, $_GET['revert'] ) ) {
			
			$tmpl->assign( 'outcome_stat', 'success' );
			
			$tmpl->assign( 'outcome_msg', "File {$_GET['path']} was reverted to version ".OCP\Util::formatDate( $_GET['revert'] ) );
			
		} else {
		
			$tmpl->assign( 'outcome_stat', 'failure' );
		
			$tmpl->assign( 'outcome_msg', "File {$_GET['path']} could not be reverted to version ".OCP\Util::formatDate( $_GET['revert'] ) );
			
		}
		
	}

	// show the history only if there is something to show
        if( OCA_Versions\Storage::isversioned( $path ) ) {
	
		$count = 999; //show the newest revisions
	        $versions = OCA_Versions\Storage::getversions( $path, $count );

		$tmpl->assign( 'versions', array_reverse( $versions ) );
		
	}else{
		
		$tmpl->assign( 'message', 'No old versions available' );
		
	}
}else{
	
	$tmpl->assign( 'message', 'No path specified' );
	
}

$tmpl->printPage( );

?>
