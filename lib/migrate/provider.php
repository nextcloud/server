<?php
/**
 * provides search functionalty
 */
abstract class OC_Migrate_Provider{
	public $appid;
	
	public function __construct($appid){
		$this->appid = $appid;
		OC_Migrate::registerProvider($this);
	}
	//public static $appid;
	/**
	 * exports data for apps
	 * @param string $uid
	 * @return array appdata to be exported
	 */
	abstract function export($uid);
	
	/**
	 * imports data for the app
	 * @param $data array of data. eg: array('info'=> APPINFO, 'data'=>APPDATA ARRAY)
	 * @param $info array of info of the source install
	 * @return void
	 */
	abstract function import($data,$uid);
}
