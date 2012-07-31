<?php

class OC_OCS_Privatedata {

	public static function get($parameters){
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		$result = OC_OCS::getData($user,$app,$key);
		$xml=  array();
		foreach($result as $i=>$log) {
			$xml[$i]['key']=$log['key'];
			$xml[$i]['app']=$log['app'];
			$xml[$i]['value']=$log['value'];
		}
		return $xml;
		//TODO: replace 'privatedata' with 'attribute' once a new libattice has been released that works with it
	}
	
	public static function set($parameters){
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		$value = OC_OCS::readData('post', 'value', 'text');
		if(OC_OCS::setData($user,$app,$key,$value)){
			return 100;
		}
	}
	
	public static function delete($parameters){
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		if($key=="" or $app==""){
			return; //key and app are NOT optional here
		}
		if(OC_OCS::deleteData($user,$app,$key)){
			return 100;
		}
	}
}
