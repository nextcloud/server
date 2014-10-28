<?php
/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt, Thomas Tanghus, Bart Visscher
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
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
 * AppFramework\HTTP\Response class
 */

namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Base class for responses. Also used to just send headers.
 *
 * It handles headers, HTTP status code, last modified and ETag.
 */
class Response {

	/**
	 * Headers - defaults to ['Cache-Control' => 'no-cache, must-revalidate']
	 * @var array
	 */
	private $headers = array(
		'Cache-Control' => 'no-cache, must-revalidate'
	);


	/**
	 * HTTP status code - defaults to STATUS OK
	 * @var string
	 */
	private $status = Http::STATUS_OK;


	/**
	 * Last modified date
	 * @var \DateTime
	 */
	private $lastModified;


	/**
	 * ETag
	 * @var string
	 */
	private $ETag;


	/**
	 * Caches the response
	 * @param int $cacheSeconds the amount of seconds that should be cached
	 * if 0 then caching will be disabled
	 */
	public function cacheFor($cacheSeconds) {

		if($cacheSeconds > 0) {
			$this->addHeader('Cache-Control', 'max-age=' . $cacheSeconds .
				', must-revalidate');
		} else {
			$this->addHeader('Cache-Control', 'no-cache, must-revalidate');
		}

		return $this;
	}


	/**
	 * Adds a new header to the response that will be called before the render
	 * function
	 * @param string $name The name of the HTTP header
	 * @param string $value The value, null will delete it
	 * @return Response Reference to this object
	 */
	public function addHeader($name, $value) {
		$name = trim($name);  // always remove leading and trailing whitespace
		                      // to be able to reliably check for security
		                      // headers

		if(is_null($value)) {
			unset($this->headers[$name]);
		} else {
			$this->headers[$name] = $value;
		}

		return $this;
	}


	/**
	 * Set the headers
	 * @param array key value header pairs
	 * @return Response Reference to this object
	 */
	public function setHeaders($headers) {
		$this->headers = $headers;

		return $this;
	}


	/**
	 * Returns the set headers
	 * @return array the headers
	 */
	public function getHeaders() {
		$mergeWith = array();

		if($this->lastModified) {
			$mergeWith['Last-Modified'] =
				$this->lastModified->format(\DateTime::RFC2822);
		}

		if($this->ETag) {
			$mergeWith['ETag'] = '"' . $this->ETag . '"';
		}

		return array_merge($mergeWith, $this->headers);
	}


	/**
	 * By default renders no output
	 * @return null
	 */
	public function render() {
		return null;
	}


	/**
	* Set response status
	* @param int $status a HTTP status code, see also the STATUS constants
	* @return Response Reference to this object
	*/
	public function setStatus($status) {
		$this->status = $status;

		return $this;
	}


	/**
	 * Get response status
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * Get the ETag
	 * @return string the etag
	 */
	public function getETag() {
		return $this->ETag;
	}


	/**
	 * Get "last modified" date
	 * @return \DateTime RFC2822 formatted last modified date
	 */
	public function getLastModified() {
		return $this->lastModified;
	}


	/**
	 * Set the ETag
	 * @param string $ETag
	 * @return Response Reference to this object
	 */
	public function setETag($ETag) {
		$this->ETag = $ETag;

		return $this;
	}


	/**
	 * Set "last modified" date
	 * @param \DateTime $lastModified
	 * @return Response Reference to this object
	 */
	public function setLastModified($lastModified) {
		$this->lastModified = $lastModified;

		return $this;
	}


}
