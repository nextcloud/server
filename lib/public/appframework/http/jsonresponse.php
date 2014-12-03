<?php
/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
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
 * AppFramework\HTTP\JSONResponse class
 */

namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A renderer for JSON calls
 */
class JSONResponse extends Response {

	/**
	 * response data
	 * @var array|object
	 */
	protected $data;


	/**
	 * constructor of JSONResponse
	 * @param array|object $data the object or array that should be transformed
	 * @param int $statusCode the Http status code, defaults to 200
	 */
	public function __construct($data=array(), $statusCode=Http::STATUS_OK) {
		$this->data = $data;
		$this->setStatus($statusCode);
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
	}


	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 */
	public function render(){
		return json_encode($this->data);
	}

	/**
	 * Sets values in the data json array
	 * @param array|object $data an array or object which will be transformed
	 *                             to JSON
	 * @return JSONResponse Reference to this object
	 */
	public function setData($data){
		$this->data = $data;

		return $this;
	}


	/**
	 * Used to get the set parameters
	 * @return array the data
	 */
	public function getData(){
		return $this->data;
	}

}
