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
	function import( $info ){
		
		switch( $info['appversion'] ){
			default:
				// All versions of the app have had the same db structure, so all can use the same import function
				$query = OC_Migrate::prepare( "SELECT * FROM bookmarks WHERE user_id LIKE ?" );
				$results = $query->execute( array( $info['olduid'] ) );
				$idmap = array();
				while( $row = $data->fetchRow() ){
					// Import each bookmark, saving its id into the map	
					$query = OC_DB::prepare( "INSERT INTO *PREFIX*bookmarks(url, title, user_id, public, added, lastmodified) VALUES (?, ?, ?, ?, ?, ?)" );
					$query->execute( array( $row['url'], $row['title'], $info['newuid'], $row['public'], $row['added'], $row['lastmodified'] ) );
					// Map the id
					$idmap[$row['id']] = OC_DB::insertid();
				}
				// Now tags
				foreach($idmap as $oldid => $newid){
					$query = OC_Migrate::prepare( "SELECT * FROM bookmarks_tags WHERE user_id LIKE ?" );
					$results = $query->execute( array( $oldid ) );
					while( $row = $data->fetchRow() ){
						// Import the tags for this bookmark, using the new bookmark id
						$query = OC_DB::prepare( "INSERT INTO *PREFIX*bookmarks_tags(bookmark_id, tag) VALUES (?, ?)" );
						$query->execute( array( $newid, $row['tag'] ) );	
					}		
				}
				// All done!
			break;
		}
		
		return true;
	}
	
}

// Load the provider
new OC_Migrate_Provider_Bookmarks( 'bookmarks' );