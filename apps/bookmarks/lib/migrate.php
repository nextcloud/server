<?php
class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	
	// Create the xml for the user supplied
	function export( $uid ){
		
		$options = array(
			'table'=>'bookmarks',
			'matchcol'=>'user_id',
			'matchval'=>$uid,
			'idcol'=>'id'
		);
		$ids = OC_Migrate::copyRows( $options );
		
		$options = array(
			'table'=>'bookmarks_tags',
			'matchcol'=>'id',
			'matchval'=>$ids
		);
		
		// Export tags
		OC_Migrate::copyRows( $options );
		
	}
	
	// Import function for bookmarks
	function import( $data, $uid ){
		
		// new id mapping
		$newids = array();
		
		// Import bookmarks
		foreach($data['bookmarks'] as $bookmark){
			$bookmark['user_id'] = $uid;
			// import to the db now
			$newids[$bookmark['id']] = OC_DB::insertid();
		}
		
		// Import tags
		foreach($data['bookmarks_tags'] as $tag){
			// Map the new ids
			$tag['id'] = $newids[$tag['id']];
			// Import to the db now using OC_DB
		}
	}
	
}

new OC_Migrate_Provider_Bookmarks( 'bookmarks' );