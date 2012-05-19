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
 * Response Class.
 *
 */

// use OCP namespace for all classes that are considered public. 
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides convinient functions to send the correct http response headers
 */
class Response {


	/**
	* @brief Enable response caching by sending correct HTTP headers
	* @param $cache_time time to cache the response
	*  >0		cache time in seconds
	*  0 and <0	enable default browser caching
	*  null		cache indefinitly
	*/
	static public function enableCaching( $cache_time = null ) {
		return(\OC_Response::enableCaching( $cache_time ));
	}


	/**
	* Checks and set Last-Modified header, when the request matches sends a
	* 'not modified' response
	* @param $lastModified time when the reponse was last modified
	*/
	static public function setLastModifiedHeader( $lastModified ) {
		return(\OC_Response::setLastModifiedHeader( $lastModified ));
	}


	/**
	* @brief disable browser caching
	* @see enableCaching with cache_time = 0
	*/
	static public function disableCaching() {
		return(\OC_Response::disableCaching());
	}


	/**
	* Checks and set ETag header, when the request matches sends a
	* 'not modified' response
	* @param $etag token to use for modification check
	*/
	static public function setETagHeader( $etag ) {
		return(\OC_Response::setETagHeader( $etag ));
	}


	/**
	* @brief Send file as response, checking and setting caching headers
	* @param $filepath of file to send
	*/
	static public function sendFile( $filepath ) {
		return(\OC_Response::sendFile( $filepath ));
	}

	/**
	* @brief Set reponse expire time
	* @param $expires date-time when the response expires
	*  string for DateInterval from now
	*  DateTime object when to expire response
	*/
	static public function setExpiresHeader( $expires ) {
		return(\OC_Response::setExpiresHeader( $expires ));
	}

	/**
	* @brief Send redirect response
	* @param $location to redirect to
	*/
	static public function redirect( $location ) {
		return(\OC_Response::redirect( $location ));
	}


}

?>
