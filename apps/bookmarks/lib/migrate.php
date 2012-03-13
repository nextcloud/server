<?php
class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	
	// Create the xml for the user supplied
	function export( $uid ){
		OC_Log::write('migration','starting export for bookmarks',OC_Log::INFO);
		$options = array(
			'table'=>'bookmarks',
			'matchcol'=>'user_id',
			'matchval'=>$uid,
			'idcol'=>'id'
		);
		$ids = OC_Migrate::copyRows( $options );

		$options = array(
			'table'=>'bookmarks_tags',
			'matchcol'=>'bookmark_id',
			'matchval'=>$ids
		);
		
		// Export tags
		$ids2 = OC_Migrate::copyRows( $options );
		
		// If both returned some ids then they worked
		if( is_array( $ids ) && is_array( $ids2 ) )
		{
			return true;	
		} else {
			return false;
		}
		
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

// Load the provider
new OC_Migrate_Provider_Bookmarks( 'bookmarks' );