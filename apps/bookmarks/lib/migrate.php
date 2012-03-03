<?php
class OC_Migrate_Provider_Bookmarks extends OC_Migrate_Provider{
	
	// Create the xml for the user supplied
	function export($uid){
		
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$bookmarks = $doc->createElement('bookmarks');
		$bookmarks = $doc->appendChild($bookmarks);
		
		$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks WHERE  *PREFIX*bookmarks.user_id =  ?");
		$bookmarksdata =& $query->execute(array($uid));
		
		
		// Foreach bookmark
		while ($row = $bookmarksdata->fetchRow()) {
			$bookmark = $doc->createElement('bookmark');
			$bookmark = $bookmarks->appendChild($bookmark);
			
			$attr = $doc->createElement('title');
			$attr = $bookmark->appendChild($attr);
			$value = $doc->createTextNode($row['title']);
			$attr->appendChild($value);
			
			$attr = $doc->createElement('url');
			$attr = $bookmark->appendChild($attr);
			$value = $doc->createTextNode($row['url']);
			$attr->appendChild($value);
			
			$attr = $doc->createElement('added');
			$attr = $bookmark->appendChild($attr);
			$value = $doc->createTextNode($row['added']);
			$attr->appendChild($value);
			
			$attr = $doc->createElement('lastmodified');
			$attr = $bookmark->appendChild($attr);
			$value = $doc->createTextNode($row['lastmodified']);
			$attr->appendChild($value);
			
			$attr = $doc->createElement('public');
			$attr = $bookmark->appendChild($attr);
			$value = $doc->createTextNode($row['public']);
			$attr->appendChild($value);
			
			$attr = $doc->createElement('clickcount');
			$attr = $bookmark->appendChild($attr);
			$value = $doc->createTextNode($row['clickcount']);
			$attr->appendChild($value);
			
			$attr = $doc->createElement('tags');
			$tags = $bookmark->appendChild($attr);
			
			$query = OC_DB::prepare("SELECT * FROM  *PREFIX*bookmarks_tags WHERE  *PREFIX*bookmarks_tags.bookmark_id =  ?");
			$tagsdata =& $query->execute(array($row['id']));
		
			// Foreach tag
			while ($row = $tagsdata->fetchRow()) {
				$attr = $doc->createElement('tag');
				$attr = $tags->appendChild($attr);
				$value = $doc->createTextNode($row['tag']);
				$attr->appendChild($value);
			}	
		}
		
		return $bookmarks;
		
	}
	
}

new OC_Migrate_Provider_Bookmarks('bookmarks');