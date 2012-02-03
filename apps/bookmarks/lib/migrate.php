<?php

class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	$this->appid = 'bookmarks';
	// Create the xml for the user supplied
	function export($uid){
		$xml = '';
		$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks WHERE  *PREFIX*bookmakrs.user_id =  ?");
		$bookmarks = $query->execute($uid);
		OC_Log::write('user_migrate',print_r($bookmarks));
		foreach($bookmarks as $bookmark){
			$xml .= '<bookmark>';
			$xml .='DATA WILL BE HERE';
			$xml .= '</bookmark>';		
		}
		return $xml;
	}
}
