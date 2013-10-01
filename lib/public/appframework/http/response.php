<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt, Thomas Tanghus, Bart Visscher
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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


namespace OCP\AppFramework\Http;


/**
 * Base class for responses. Also used to just send headers
 */
class Response {

	/**
	 * @var array default headers
	 */
	private $headers = array(
		'Cache-Control' => 'no-cache, must-revalidate'
	);


	/**
	 * @var string
	 */
	private $status = Http::STATUS_OK;


	/**
	 * @var \DateTime
	 */
	private $lastModified;


	/**
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

	}


	/**
	 * Adds a new header to the response that will be called before the render
	 * function
	 * @param string $name The name of the HTTP header
	 * @param string $value The value, null will delete it
	 */
	public function addHeader($name, $value) {
		if(is_null($value)) {
			unset($this->headers[$name]);
		} else {
			$this->headers[$name] = $value;
		}
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
	*/
	public function setStatus($status) {
		$this->status = $status;
	}


	/**
	 * Get response status
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @return string the etag
	 */
	public function getETag() {
		return $this->ETag;
	}


	/**
	 * @return string RFC2822 formatted last modified date
	 */
	public function getLastModified() {
		return $this->lastModified;
	}


	/**
	 * @param string $ETag
	 */
	public function setETag($ETag) {
		$this->ETag = $ETag;
	}


	/**
	 * @param \DateTime $lastModified
	 */
	public function setLastModified($lastModified) {
		$this->lastModified = $lastModified;
	}


}
