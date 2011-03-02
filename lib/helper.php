<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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


/**
 * Class for utility functions
 *
 */
class OC_HELPER {
	/**
	 * Create an url
	 *
	 * @param string $application
	 * @param string $file
	 */
	public static function linkTo( $application, $file = null ){
		global $WEBROOT;
		if( is_null( $file )){
			$file = $application;
			$application = "";
		}
		return "$WEBROOT/$application/$file";
	}

	/**
	 * Create an image link
	 *
	 * @param string $application
	 * @param string $file
	 */
	public static function imagePath( $application, $file = null ){
		global $WEBROOT;
		if( is_null( $file )){
			$file = $application;
			$application = "";
		}
		return "$WEBROOT/$application/img/$file";
	}

	/**
	 * show an icon for a filetype
	 *
	 */
	public static function showIcon( $mimetype ){
		global $SERVERROOT;
		global $WEBROOT;
		// Replace slash with a minus
		$mimetype = str_replace( "/", "-", $mimetype );

		// Is it a dir?
		if( $mimetype == "dir" ){
			return "$WEBROOT/img/places/folder.png";
		}

		// Icon exists?
		if( file_exists( "$SERVERROOT/img/mimetypes/$mimetype.png" )){
			return "$WEBROOT/img/mimetypes/$mimetype.png";
		}
		else{
			return "$WEBROOT/img/mimetypes/application-octet-stream.png";
		}
	}
}

?>
