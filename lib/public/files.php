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
 * Public interface of ownCloud for apps to use.
 * Files Class
 *
 */

// use OCP namespace for all classes that are considered public. 
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides access to the internal filesystem abstraction layer. Use this class exlusively if you want to access files
 */
class Files {


	/**
	 * @brief Recusive deletion of folders
	 * @param string $dir path to the folder
	 *
	 */
	static function rmdirr( $dir ) {
		\OC_Helper::rmdirr( $dir );
	}


	/**
	 * get the mimetype form a local file
	 * @param string path
	 * @return string
	 * does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 */
	static function getMimeType( $path ){
		return(\OC_Helper::getMimeType( $path ));
	}


	/**
	 * copy the contents of one stream to another
	 * @param resource source
	 * @param resource target
	 * @return int the number of bytes copied
	 */
	public static function streamCopy( $source, $target ){
		return(\OC_Helper::streamCopy( $source, $target ));
	}


	/**
	 * create a temporary file with an unique filename
	 * @param string postfix
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFile( $postfix='' ){
		return(\OC_Helper::tmpFile( $postfix ));
	}


	/**
	 * create a temporary folder with an unique filename
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFolder(){
		return(\OC_Helper::tmpFolder());
	}


	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param $path
	 * @param $filename
	 * @return string
	 */
	public static function buildNotExistingFileName( $path, $filename ){
		return(\OC_Helper::buildNotExistingFileName( $path, $filename ));
	}

        /**
         * @param string appid
         * @param $app app
         * @return OC_FilesystemView
         */
        public static function getStorage( $app ){
		return \OC_App::getStorage( $app );
	}




}

?>
