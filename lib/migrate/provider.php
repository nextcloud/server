<?php
/**
 * provides search functionalty
 */
abstract class OC_Migrate_Provider{
	public function __construct(){
		OC_Migrate::registerProvider($this);
	}
	//public static $appid;
	/**
	 * exports data for apps
	 * @param string $uid
	 * @return string xml data for that app
	 */
	abstract function export($uid);
	
	/**
	 * imports data for the app
	 * @param string $query
	 * @return array An array of OC_Search_Result's
	 */
	//abstract function import($data);
}
