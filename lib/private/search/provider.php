<?php
/**
 * provides search functionalty
 */
abstract class OC_Search_Provider {
	private $options;

	public function __construct($options) {
		$this->options=$options;
	}

	/**
	 * search for $query
	 * @param string $query
	 * @return array An array of OC_Search_Result's
	 */
	abstract public function search($query);
}
