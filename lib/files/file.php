<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files;

/**
 * representation of the location a file or folder is stored
 */

class File{
	/**
	 * @var Storage\Storage $storage
	 */
	private $storage;
	/**
	 * @var string internalPath
	 */
	private $internalPath;

	public function __construct(Storage\Storage $storage, $internalPath){
		$this->storage = $storage;
		$this->internalPath = $internalPath;
	}

	public static function resolve($fullPath){
		$storage = null;
		$internalPath = '';
		list($storage, $internalPath) = \OC_Filesystem::resolvePath($fullPath);
		return new File($storage, $internalPath);
	}

	/**
	 * get the internal path of the file inside the filestorage
	 * @return string
	 */
	public function getInternalPath(){
		return $this->internalPath;
	}

	/**
	 * get the storage the file is stored in
	 * @return  \OC\Files\Storage\Storage
	 */
	public function getStorage(){
		return $this->storage;
	}

	/**
	 * get the id of the storage the file is stored in
	 * @return string
	 */
	public function getStorageId(){
		return $this->storage->getId();
	}
	
}
