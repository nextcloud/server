<?php
/**
* ownCloud
*
* @author Tom Needham
* @copyright 2012 Tom Needham tom@owncloud.com
*
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

class OC_OCS_Result{

	protected $data, $message, $statusCode, $items, $perPage;

	/**
	 * create the OCS_Result object
	 * @param mixed $data the data to return
	 * @param int $code
	 * @param null|string $message
	 */
	public function __construct($data=null, $code=100, $message=null) {
		if ($data === null) {
			$this->data = array();
		} elseif (!is_array($data)) {
			$this->data = array($this->data);
		} else {
			$this->data = $data;
		}
		$this->statusCode = $code;
		$this->message = $message;
	}

	/**
	 * optionally set the total number of items available
	 * @param int $items
	 */
	public function setTotalItems($items) {
		$this->items = $items;
	}

	/**
	 * optionally set the the number of items per page
	 * @param int $items
	 */
	public function setItemsPerPage($items) {
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
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * return bool Whether the method succeeded
	 * @return bool
	 */
	public function succeeded() {
		return ($this->statusCode == 100);
	}


}
