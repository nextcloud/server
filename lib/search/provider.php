<?php
/**
 * provides search functionalty
 */
class OC_Search_Provider {
	public function __construct($options){}
	
	/**
	 * search for $query
	 * @param string $query
	 * @return array An array of OC_Search_Result's
	 */
	public function search($query){}
}
