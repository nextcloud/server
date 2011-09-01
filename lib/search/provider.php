<?php
/**
 * provides search functionalty
 */
abstract class OC_Search_Provider{
	public function __construct(){
		OC_Search::registerProvider($this);
	}

	/**
	 * search for $query
	 * @param string $query
	 * @return array An array of OC_Search_Result's
	 */
	abstract function search($query);
}
