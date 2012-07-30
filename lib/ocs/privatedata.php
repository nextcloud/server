<?php

class OC_OCS_Privatedata {

	public static function privatedataGet($parameters){
		$user = OC_OCS::checkpassword();
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
	
	public static function privatedataSet($parameters){
		$user = OC_OCS::checkpassword();
		if(OC_OCS::setData($user,$app,$key,$value)){
			return 100;
		}
	}
	
	public static function privatedataDelete($parameteres){
		$user = OC_OCS::checkpassword();
		if($key=="" or $app==""){
			return; //key and app are NOT optional here
		}
		if(OC_OCS::deleteData($user,$app,$key)){
			return 100;
		}
	}
	
}

?>