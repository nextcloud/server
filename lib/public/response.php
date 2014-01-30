<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 * This class provides convenient functions to send the correct http response headers
 */
class Response {
	/**
	 * Enable response caching by sending correct HTTP headers
	 * @param int $cache_time time to cache the response
	 *  >0		cache time in seconds
	 *  0 and <0	enable default browser caching
	 *  null		cache indefinitly
	 */
	static public function enableCaching( $cache_time = null ) {
		\OC_Response::enableCaching( $cache_time );
	}

	/**
	 * Checks and set Last-Modified header, when the request matches sends a
	 * 'not modified' response
	 * @param string $lastModified time when the reponse was last modified
	 */
	static public function setLastModifiedHeader( $lastModified ) {
		\OC_Response::setLastModifiedHeader( $lastModified );
	}

	/**
	 * Sets the content disposition header (with possible workarounds)
	 * @param string $filename file name
	 * @param string $type disposition type, either 'attachment' or 'inline'
	 */
	static public function setContentDispositionHeader( $filename, $type = 'attachment' ) {
		\OC_Response::setContentDispositionHeader( $filename, $type );
	}

	/**
	 * Disable browser caching
	 * @see enableCaching with cache_time = 0
	 */
	static public function disableCaching() {
		\OC_Response::disableCaching();
	}

	/**
	 * Checks and set ETag header, when the request matches sends a
	 * 'not modified' response
	 * @param string $etag token to use for modification check
	 */
	static public function setETagHeader( $etag ) {
		\OC_Response::setETagHeader( $etag );
	}

	/**
	 * Send file as response, checking and setting caching headers
	 * @param string $filepath of file to send
	 */
	static public function sendFile( $filepath ) {
		\OC_Response::sendFile( $filepath );
	}

	/**
	 * Set response expire time
	 * @param string|\DateTime $expires date-time when the response expires
	 *  string for DateInterval from now
	 *  DateTime object when to expire response
	 */
	static public function setExpiresHeader( $expires ) {
		\OC_Response::setExpiresHeader( $expires );
	}

	/**
	 * Send redirect response
	 * @param string $location to redirect to
	 */
	static public function redirect( $location ) {
		\OC_Response::redirect( $location );
	}
}
