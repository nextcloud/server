<?php
class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	// Create the xml for the user supplied
	function export($uid){
		$xml = 'debugfrombookmarks';
		//$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks WHERE  *PREFIX*bookmakrs.user_id =  ?");
		//$bookmarks = $query->execute($uid);
		//foreach($bookmarks as $bookmark){
		//	$xml .= '<bookmark>';
		//	$xml .='DATA WILL BE HERE';
		//	$xml .= '</bookmark>';		
		//}
		return $xml;
	}
}
new OC_Migrate_Provider_Bookmarks('bookmarks');