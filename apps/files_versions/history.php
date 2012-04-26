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
require_once( '../../lib/base.php' );

OC_Util::checkLoggedIn( );
OC_Util::addStyle('files_versions','versions');

if ( isset( $_GET['path'] ) ) {

	$path = $_GET['path'];
	$path = strip_tags( $path );

	// roll back to old version if button clicked
        if( isset( $_GET['revert'] ) ) {
        	\OCA_Versions\Storage::rollback( $path, $_GET['revert'] );
	}

	// show the history only if there is something to show
        if( OCA_Versions\Storage::isversioned( $path ) ) {

		$count=5; //show the newest revisions
	        $versions=OCA_Versions\Storage::getversions( $path, $count);

		$tmpl = new OC_Template( 'files_versions', 'history', 'user' );
		$tmpl->assign( 'path', $path);
		$tmpl->assign( 'versions', array_reverse( $versions) );
		$tmpl->printPage( );
	}else{
		$tmpl = new OC_Template( 'files_versions', 'history', 'user' );
		$tmpl->assign( 'path', $path);
		$tmpl->assign( 'message', 'No old versions available' );
		$tmpl->printPage( );
	}
}else{
	$tmpl = new OC_Template( 'files_versions', 'history', 'user' );
	$tmpl->assign( 'message', 'No path specified' );
	$tmpl->printPage( );
}


?>
