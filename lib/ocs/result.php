<?php
/**
* ownCloud
*
* @author Tom Needham
* @copyright 2012 Tom Needham tom@owncloud.com
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

class OC_OCS_Result{

	protected $data, $message, $statusCode, $items, $perPage;

	/**
	 * create the OCS_Result object
	 * @param $data mixed the data to return
	 */
	public function __construct($data=null, $code=100, $message=null) {
		$this->data = $data;
		$this->statusCode = $code;
		$this->message = $message;
	}

	/**
	 * optionally set the total number of items available
	 * @param $items int
	 */
	public function setTotalItems(int $items) {
		$this->items = $items;
	}

	/**
	 * optionally set the the number of items per page
	 * @param $items int
	 */
	public function setItemsPerPage(int $items) {
		$this->perPage = $items;
	}
	
	/**
	 * get the status code
	 * @return int
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}
	
	/**
	 * get the meta data for the result
	 * @return array
	 */
	public function getMeta() {
		$meta = array();
		$meta['status'] = ($this->statusCode === 100) ? 'ok' : 'failure';
		$meta['statuscode'] = $this->statusCode;
		$meta['message'] = $this->message;
		if(isset($this->items)) {
			$meta['totalitems'] = $this->items;
		}
		if(isset($this->perPage)) {
			$meta['itemsperpage'] = $this->perPage;
		}
		return $meta;

	}
	
	/**
	 * get the result data
	 * @return array|string|int 
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * return bool if the method succedded
	 * @return bool
	 */
	public function succeeded() {
		return (substr($this->statusCode, 0, 1) === '1');
	}


}
