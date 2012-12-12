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
	 * sets the statuscode
	 * @param $code int
	 */
	public function setCode(int $code){
		$this->statuscode = $code;
	}
	
	/**
	 * optionally set the total number of items available
	 * @param $items int
	 */
	public function setItems(int $items){
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
	 * set a custom message for the response
	 * @param $message string the message
	 */
	public function setMessage(string $message){
		$this->message = $message;
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
		$return['data'] = $this->data;
		// Return the result data.
		return $return;
	}
	
	
}