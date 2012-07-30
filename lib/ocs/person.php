<?php

class OC_OCS_Person {

	public static function check($parameters){
	
		if($parameters['login']<>''){
			if(OC_User::login($parameters['login'],$parameters['password'])){
				$xml['person']['personid'] = $parameters['login'];
				return $xml;
			}else{
				return 102;
			}
		}else{
			return 101;
		}
		
	}
	
}

?>