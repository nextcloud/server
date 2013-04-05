<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * Provide read only support for the old filecache
 */
class Legacy {
	private $user;

	private $cacheHasItems = null;

	public function __construct($user) {
		$this->user = $user;
	}

	function getCount() {
		$query = \OC_DB::prepare('SELECT COUNT(`id`) AS `count` FROM `*PREFIX*fscache` WHERE `user` = ?');
		$result = $query->execute(array($this->user));
		if ($row = $result->fetchRow()) {
			return $row['count'];
		} else {
			return 0;
		}
	}

	/**
	 * check if a legacy cache is present and holds items
	 *
	 * @return bool
	 */
	function hasItems() {
		if (!is_null($this->cacheHasItems)) {
			return $this->cacheHasItems;
		}
		try {
			$query = \OC_DB::prepare('SELECT `id` FROM `*PREFIX*fscache` WHERE `user` = ? LIMIT 1');
		} catch (\Exception $e) {
			$this->cacheHasItems = false;
			return false;
		}
		try {
			$result = $query->execute(array($this->user));
		} catch (\Exception $e) {
			$this->cacheHasItems = false;
			return false;
		}

		if ($result === false || property_exists($result, 'error_message_prefix')) {
			$this->cacheHasItems = false;
			return false;
		}

		$this->cacheHasItems = (bool)$result->fetchRow();
		return $this->cacheHasItems;
	}

	/**
	 * @param string|int $path
	 * @return array
	 */
	function get($path) {
		if (is_numeric($path)) {
			$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*fscache` WHERE `id` = ?');
		} else {
			$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*fscache` WHERE `path` = ?');
		}
		$result = $query->execute(array($path));
		$data = $result->fetchRow();
		$data['etag'] = $this->getEtag($data['path'], $data['user']);
		return $data;
	}

	/**
	 * Get the ETag for the given path
	 *
	 * @param type $path
	 * @return string
	 */
	function getEtag($path, $user = null) {
		static $query = null;

		$pathDetails = explode('/', $path, 4);
		if((!$user) && !isset($pathDetails[1])) {
			//no user!? Too odd, return empty string.
			return '';
		} else if(!$user) {
			//guess user from path, if no user passed.
			$user = $pathDetails[1];
		}

		if(!isset($pathDetails[3]) || is_null($pathDetails[3])) {
			$relativePath = '';
		} else {
			$relativePath = $pathDetails[3];
		}

		if(is_null($query)){
			$query = \OC_DB::prepare('SELECT `propertyvalue` FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = \'{DAV:}getetag\'');
		}
		$result = $query->execute(array($user, '/' . $relativePath));
		if ($row = $result->fetchRow()) {
			return trim($row['propertyvalue'], '"');
		} else {
			return '';
		}
	}

	/**
	 * @param int $id
	 * @return array
	 */
	function getChildren($id) {
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*fscache` WHERE `parent` = ?');
		$result = $query->execute(array($id));
		$data = $result->fetchAll();
		foreach ($data as $i => $item) {
			$data[$i]['etag'] = $this->getEtag($item['path'], $item['user']);
		}
		return $data;
	}
}
