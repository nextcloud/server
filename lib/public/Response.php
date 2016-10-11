<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
 * @since 4.0.0
 * @deprecated 8.1.0 - Use AppFramework controllers instead and modify the response object
 */
class Response {
	/**
	 * Enable response caching by sending correct HTTP headers
	 * @param int $cache_time time to cache the response
	 *  >0		cache time in seconds
	 *  0 and <0	enable default browser caching
	 *  null		cache indefinitely
	 * @since 4.0.0
	 */
	static public function enableCaching( $cache_time = null ) {
		\OC_Response::enableCaching( $cache_time );
	}

	/**
	 * Checks and set Last-Modified header, when the request matches sends a
	 * 'not modified' response
	 * @param string $lastModified time when the response was last modified
	 * @since 4.0.0
	 */
	static public function setLastModifiedHeader( $lastModified ) {
		\OC_Response::setLastModifiedHeader( $lastModified );
	}

	/**
	 * Sets the content disposition header (with possible workarounds)
	 * @param string $filename file name
	 * @param string $type disposition type, either 'attachment' or 'inline'
	 * @since 7.0.0
	 */
	static public function setContentDispositionHeader( $filename, $type = 'attachment' ) {
		\OC_Response::setContentDispositionHeader( $filename, $type );
	}

	/**
	 * Sets the content length header (with possible workarounds)
	 * @param string|int|float $length Length to be sent
	 * @since 8.1.0
	 */
	static public function setContentLengthHeader($length) {
		\OC_Response::setContentLengthHeader($length);
	}

	/**
	 * Disable browser caching
	 * @see enableCaching with cache_time = 0
	 * @since 4.0.0
	 */
	static public function disableCaching() {
		\OC_Response::disableCaching();
	}

	/**
	 * Checks and set ETag header, when the request matches sends a
	 * 'not modified' response
	 * @param string $etag token to use for modification check
	 * @since 4.0.0
	 */
	static public function setETagHeader( $etag ) {
		\OC_Response::setETagHeader( $etag );
	}

	/**
	 * Send file as response, checking and setting caching headers
	 * @param string $filepath of file to send
	 * @since 4.0.0
	 * @deprecated 8.1.0 - Use \OCP\AppFramework\Http\StreamResponse or another AppFramework controller instead
	 */
	static public function sendFile( $filepath ) {
		\OC_Response::sendFile( $filepath );
	}

	/**
	 * Set response expire time
	 * @param string|\DateTime $expires date-time when the response expires
	 *  string for DateInterval from now
	 *  DateTime object when to expire response
	 * @since 4.0.0
	 */
	static public function setExpiresHeader( $expires ) {
		\OC_Response::setExpiresHeader( $expires );
	}

	/**
	 * Send redirect response
	 * @param string $location to redirect to
	 * @since 4.0.0
	 */
	static public function redirect( $location ) {
		\OC_Response::redirect( $location );
	}
}
