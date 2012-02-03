<?php
class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	// Create the xml for the user supplied
	function export($uid){
		$xml = 'test';
		$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks WHERE  *PREFIX*bookmarks.user_id =  ?");
		$bookmarks =& $query->execute(array($uid));
		while ($row = $bookmarks->fetchRow()) {
			$xml .= $row[0] . "\n";
		}
		return $xml;
	}
}
new OC_Migrate_Provider_Bookmarks('bookmarks');