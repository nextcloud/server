<?php

class OC_OCS_Result{
	
	private $data, $message, $statuscode, $items, $perpage;
	
	/**
	 * create the OCS_Result object
	 * @param $data mixed the data to return
	 */
	public function __construct($data=null, $code=100, $message=null){
		$this->data = $data;
		$this->statuscode = $code;
		$this->message = $message;
	}
	
	/**
	 * optionally set the total number of items available
	 * @param $items int
	 */
	public function setTotalItems(int $items){
		$this->items = $items;
	}
	
	/**
	 * optionally set the the number of items per page
	 * @param $items int
	 */
	public function setItemsPerPage(int $items){
		$this->perpage = $items;
	}
	
	/**
	 * returns the data associated with the api result
	 * @return array
	 */
	public function getResult(){
		$return = array();
		$return['meta'] = array();
		$return['meta']['status'] = ($this->statuscode === 100) ? 'ok' : 'failure';
		$return['meta']['statuscode'] = $this->statuscode;
		$return['meta']['message'] = $this->message;
		if(isset($this->items)){
			$return['meta']['totalitems'] = $this->items;
		}
		if(isset($this->perpage)){
			$return['meta']['itemsperpage'] = $this->perpage;
		}
		$return['data'] = $this->data;
		// Return the result data.
		return $return;
	}
	
	
}