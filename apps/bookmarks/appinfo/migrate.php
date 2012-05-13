<?php
class OC_Migration_Provider_Bookmarks extends OC_Migration_Provider{
	
	// Create the xml for the user supplied
	function export( ){
		$options = array(
			'table'=>'bookmarks',
			'matchcol'=>'user_id',
			'matchval'=>$this->uid,
			'idcol'=>'id'
		);
		$ids = $this->content->copyRows( $options );

		$options = array(
			'table'=>'bookmarks_tags',
			'matchcol'=>'bookmark_id',
			'matchval'=>$ids
		);
		
		// Export tags
		$ids2 = $this->content->copyRows( $options );
		
		// If both returned some ids then they worked
		if( is_array( $ids ) && is_array( $ids2 ) )
		{
			return true;	
		} else {
			return false;
		}
		
	}
	
	// Import function for bookmarks
	function import( ){
		switch( $this->appinfo->version ){
			default:
				// All versions of the app have had the same db structure, so all can use the same import function
				$query = $this->content->prepare( "SELECT * FROM bookmarks WHERE user_id LIKE ?" );
				$results = $query->execute( array( $this->olduid ) );
				$idmap = array();
				while( $row = $results->fetchRow() ){
					// Import each bookmark, saving its id into the map	
					$query = OCP\DB::prepare( "INSERT INTO *PREFIX*bookmarks(url, title, user_id, public, added, lastmodified) VALUES (?, ?, ?, ?, ?, ?)" );
					$query->execute( array( $row['url'], $row['title'], $this->uid, $row['public'], $row['added'], $row['lastmodified'] ) );
					// Map the id
					$idmap[$row['id']] = OCP\DB::insertid();
				}
				// Now tags
				foreach($idmap as $oldid => $newid){
					$query = $this->content->prepare( "SELECT * FROM bookmarks_tags WHERE bookmark_id LIKE ?" );
					$results = $query->execute( array( $oldid ) );
					while( $row = $results->fetchRow() ){
						// Import the tags for this bookmark, using the new bookmark id
						$query = OCP\DB::prepare( "INSERT INTO *PREFIX*bookmarks_tags(bookmark_id, tag) VALUES (?, ?)" );
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
new OC_Migration_Provider_Bookmarks( 'bookmarks' );