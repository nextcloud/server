<?php
/**
* ownCloud - News app
*
* @author Alessandro Cosentino
* Copyright (c) 2012 - Alessandro Cosentino <cosenal@gmail.com>
* 
* This file is licensed under the Affero General Public License version 3 or later.
* See the COPYING-README file
* 
*/

/**
 * This class maps a feed to an entry in the feeds table of the database.
 */
class OC_News_FeedMapper {

	const tableName = '*PREFIX*news_feeds';
	
	/**
	 * @brief Retrieve a feed from the database
	 * @param id The id of the feed in the database table.
	 * @returns  
	 */
	public function findById($id){
		$stmt = OCP\DB::prepare('SELECT * FROM ' . self::tableName . ' WHERE id = ?');
		$result = $stmt->execute(array($id));
		$row = $result->fetchRow();
		$url = $row['url'];
		$title = $row['title'];
		$feed = new OC_News_Feed($url, $title, null, $id);
		return $feed;
	}

	/**
	 * @brief Retrieve a feed from the database
	 * @param id The id of the feed in the database table.
	 * @returns  
	 */
	public function findByFolderId($folderid){
		$stmt = OCP\DB::prepare('SELECT * FROM ' . self::tableName . ' WHERE folder_id = ?');
		$result = $stmt->execute(array($folderid));
		$feeds = array();
		while ($row = $result->fetchRow()) {
			$url = $row['url'];
			$title = $row['title'];
			$id = $row['id'];
			$feed = new OC_News_Feed($url, $title, null, $id);
			$feeds[] = $feed;
		}
		return $feeds;
	}

	
	/**
	 * @brief Retrieve a feed and all its items from the database
	 * @param id The id of the feed in the database table.
	 * @returns 
	 */
	public function findWithItems($id){
		$stmt = OCP\DB::prepare('SELECT * FROM ' . self::tableName . ' WHERE id = ?');
		$result = $stmt->execute(array($id));
		$row = $result->fetchRow();
		$url = $row['url'];
		$title = $row['title'];
		$feed = new OC_News_Feed($url, $title, null,$id);

		$itemMapper = new OC_News_ItemMapper();
		$items = $itemMapper->findAll($id);
		$feed->setItems($items);
		
		return $feed;
	}

	/**
	 * @brief Find the id of a feed and all its items from the database
	 * @param url url of the feed
	 * @return id of the feed corresponding to the url passed as parameters 
	 *	null - if there is no such feed 
	 */
	public function findIdFromUrl($url){
		$stmt = OCP\DB::prepare('SELECT * FROM ' . self::tableName . ' WHERE url = ?');
		$result = $stmt->execute(array($url));
		$row = $result->fetchRow();
		$id = null;
		if ($row != null){
			$id = $row['id'];
		}
		return $id;
	}

	/**
	 * @brief Save the feed and all its items into the database
	 * @param feed the feed to be saved
	 * @returns The id of the feed in the database table.
	 */
	 //TODO: handle error case
	public function save(OC_News_Feed $feed, $folderid){
		$CONFIG_DBTYPE = OCP\Config::getSystemValue( "dbtype", "sqlite" );
		if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
			$_ut = "strftime('%s','now')";
		} elseif($CONFIG_DBTYPE == 'pgsql') {
			$_ut = 'date_part(\'epoch\',now())::integer';
		} else {
			$_ut = "UNIX_TIMESTAMP()";
		}

		$title = $feed->getTitle();
		$url = htmlspecialchars_decode($feed->getUrl());

		if(empty($title)) {
			$l = OC_L10N::get('news');
			$title = $l->t('no title');
		}

		//FIXME: Detect when feed contains already a database id
		$feedid =  $this->findIdFromUrl($url);
		if ($feedid == null){
			$query = OCP\DB::prepare('
				INSERT INTO ' . self::tableName .
				'(url, title, folder_id, added, lastmodified)
				VALUES (?, ?, ?, ?, ?)
				');

			$params=array(
				$url,
				htmlspecialchars_decode($title),
				$folderid,
				$_ut, 
				$_ut
			);
			$query->execute($params);
			
			$feedid = OCP\DB::insertid(self::tableName);
		}
		$feed->setId($feedid);

		$itemMapper = new OC_News_ItemMapper();
		
		$items = $feed->getItems();
		foreach($items as $item){
			$itemMapper->save($item, $feedid);
		}
		
		return $feedid;
	}
	
	public function deleteById($id){
		if ($id == null) {
			return false;
		}
		$stmt = OCP\DB::prepare('DELETE FROM ' . self::tableName .' WHERE id = ?');

		$result = $stmt->execute(array($id));
		
		$itemMapper = new OC_News_ItemMapper();
		//TODO: handle the value that the execute returns
		$itemMapper->deleteAll($id);
		
		return true;
	}
	public function delete(OC_News_Feed $feed){
		$id = $feed->getId();
		return deleteById($id);
	}
	
	//it's more complicated tan this...recursive delete, or delete with a join
	public function deleteAll($folderdid){	
		if ($folderid == null) {
			return false;
		}
		$stmt = OCP\DB::prepare('DELETE FROM ' . self::tableName .' WHERE folder_id = ?');

		$result = $stmt->execute(array($folderid));
		
		$itemMapper = new OC_News_ItemMapper();
		//TODO: handle the value that the execute returns
		$itemMapper->deleteAll($id);
		
		return true;
	}
}