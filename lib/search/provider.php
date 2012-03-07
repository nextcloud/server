<?php
/**
 * provides search functionalty
 */
interface OC_Search_Provider {
	/**
	 * search for $query
	 * @param string $query
	 * @return array An array of OC_Search_Result's
	 */
	static function search($query);
}
