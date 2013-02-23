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

	private $data, $message, $statusCode, $items, $perPage;

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
	 * returns the data associated with the api result
	 * @return array
	 */
	public function getResult() {
		$return = array();
		$return['meta'] = array();
		$return['meta']['status'] = ($this->statusCode === 100) ? 'ok' : 'failure';
		$return['meta']['statuscode'] = $this->statusCode;
		$return['meta']['message'] = $this->message;
		if(isset($this->items)) {
			$return['meta']['totalitems'] = $this->items;
		}
		if(isset($this->perPage)) {
			$return['meta']['itemsperpage'] = $this->perPage;
		}
		$return['data'] = $this->data;
		// Return the result data.
		return $return;
	}


}