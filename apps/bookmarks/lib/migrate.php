<?php
class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	
	// Create the xml for the user supplied
	function export($uid){
		
		$bookmarks = array();

		$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks WHERE  *PREFIX*bookmarks.user_id =  ?");
		$bookmarksdata =& $query->execute(array($uid));
		// Foreach bookmark
		while ($row = $bookmarksdata->fetchRow()) {
			
			// Get the tags
			$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks_tags WHERE  *PREFIX*bookmarks_tags.bookmark_id =  ?");
			$tagsdata =& $query->execute(array($row['id']));
		
			$tags = array();
			// Foreach tag
			while ($row = $tagsdata->fetchRow()) {
				$tags[] = $row['tag'];
			}
			
			$bookmarks[] = array(
								'url' => $row['url'],
								'title' => $row['title'],
								'public' => $row['public'],
								'added' => $row['added'],
								'lastmodified' => $row['lastmodified'],
								'clickcount' => $row['clickcount'],
								'tags' => $tags
								);
				
		}
		
		return array('bookmarks' => $bookmarks);
		
	}
	
	// Import function for bookmarks
	function import($data,$uid){
		
		// Different import code for different versions of the app
		switch($data['info']['version']){
			default:
				// Foreach bookmark
				foreach($data['data']['bookmarks'] as $bookmark){
				
					$query = OC_DB::prepare( "INSERT INTO `*PREFIX*bookmarks` ( `url`, `title`, `user_id`, `public`, `added`, `lastmodified`, `clickcount` ) VALUES( ?, ?, ?, ?, ?, ?, ? )" );
					$result = $query->execute( array( 
													$bookmark['url'], 
													$bookmark['title'], 
													$uid, 
													$bookmark['public'], 
													$bookmark['added'], 
													$bookmark['lastmodified'],
													$bookmark['clickcount']
													) );
					// Now add the tags
					$id = OC_DB::insertid();
					foreach($bookmark['tags'] as $tag){
						$query = OC_DB::prepare( "INSERT INTO `*PREFIX*bookmarks_tags` ( `id`, `tag` ) VALUES( ?, ? )" );
						$result = $query->execute( array( $id, $tag));
					}
					
				}			
			break;	
		}
	// Finished import	
	}
	
}

new OC_Migrate_Provider_Bookmarks('bookmarks');