<?php

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class OC_Connector_Sabre_Directory extends OC_Connector_Sabre_Node
	implements \Sabre\DAV\ICollection, \Sabre\DAV\IQuota {

	/**
	 * Cached directory content
	 *
	 * @var \OCP\Files\FileInfo[]
	 */
	private $dirContent;

	/**
	 * Creates a new file in the directory
	 *
	 * Data will either be supplied as a stream resource, or in certain cases
	 * as a string. Keep in mind that you may have to support either.
	 *
	 * After successful creation of the file, you may choose to return the ETag
	 * of the new file here.
	 *
	 * The returned ETag must be surrounded by double-quotes (The quotes should
	 * be part of the actual string).
	 *
	 * If you cannot accurately determine the ETag, you should not return it.
	 * If you don't store the file exactly as-is (you're transforming it
	 * somehow) you should also not return an ETag.
	 *
	 * This means that if a subsequent GET to this new file does not exactly
	 * return the same contents of what was submitted here, you are strongly
	 * recommended to omit the ETag.
	 *
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @return null|string
	 */
	public function createFile($name, $data = null) {

		try {
			// for chunked upload also updating a existing file is a "createFile"
			// because we create all the chunks before re-assemble them to the existing file.
			if (isset($_SERVER['HTTP_OC_CHUNKED'])) {

				// exit if we can't create a new file and we don't updatable existing file
				$info = OC_FileChunking::decodeName($name);
				if (!$this->fileView->isCreatable($this->path) &&
						!$this->fileView->isUpdatable($this->path . '/' . $info['name'])) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}

			} else {
				// For non-chunked upload it is enough to check if we can create a new file
				if (!$this->fileView->isCreatable($this->path)) {
					throw new \Sabre\DAV\Exception\Forbidden();
				}
			}

			$path = $this->fileView->getAbsolutePath($this->path) . '/' . $name;
			// using a dummy FileInfo is acceptable here since it will be refreshed after the put is complete
			$info = new \OC\Files\FileInfo($path, null, null, array(), null);
			$node = new OC_Connector_Sabre_File($this->fileView, $info);
			return $node->put($data);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @return void
	 */
	public function createDirectory($name) {
		try {
			if (!$this->info->isCreatable()) {
				throw new \Sabre\DAV\Exception\Forbidden();
			}

			$newPath = $this->path . '/' . $name;
			if(!$this->fileView->mkdir($newPath)) {
				throw new \Sabre\DAV\Exception\Forbidden('Could not create directory '.$newPath);
			}
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}
	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * @param string $name
	 * @param \OCP\Files\FileInfo $info
	 * @throws \Sabre\DAV\Exception\FileNotFound
	 * @return \Sabre\DAV\INode
	 */
	public function getChild($name, $info = null) {
		$path = $this->path . '/' . $name;
		if (is_null($info)) {
			try {
				$info = $this->fileView->getFileInfo($path);
			} catch (\OCP\Files\StorageNotAvailableException $e) {
				throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
			}
		}

		if (!$info) {
			throw new \Sabre\DAV\Exception\NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info['mimetype'] == 'httpd/unix-directory') {
			$node = new OC_Connector_Sabre_Directory($this->fileView, $info);
		} else {
			$node = new OC_Connector_Sabre_File($this->fileView, $info);
		}
		return $node;
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 */
	public function getChildren() {
		if (!is_null($this->dirContent)) {
			return $this->dirContent;
		}
		$folderContent = $this->fileView->getDirectoryContent($this->path);

		$properties = array();
		$paths = array();
		foreach($folderContent as $info) {
			$name = $info->getName();
			$paths[] = $this->path . '/' . $name;
			$properties[$this->path.'/' . $name][self::GETETAG_PROPERTYNAME] = '"' . $info->getEtag() . '"';
		}
		// TODO: move this to a beforeGetPropertiesForPath event to pre-cache properties
		// TODO: only fetch the requested properties
		if(count($paths)>0) {
			//
			// the number of arguments within IN conditions are limited in most databases
			// we chunk $paths into arrays of 200 items each to meet this criteria
			//
			$chunks = array_chunk($paths, 200, false);
			foreach ($chunks as $pack) {
				$placeholders = join(',', array_fill(0, count($pack), '?'));
				$query = OC_DB::prepare( 'SELECT * FROM `*PREFIX*properties`'
					.' WHERE `userid` = ?' . ' AND `propertypath` IN ('.$placeholders.')' );
				array_unshift($pack, OC_User::getUser()); // prepend userid
				$result = $query->execute( $pack );
				while($row = $result->fetchRow()) {
					$propertypath = $row['propertypath'];
					$propertyname = $row['propertyname'];
					$propertyvalue = $row['propertyvalue'];
					if($propertyname !== self::GETETAG_PROPERTYNAME) {
						$properties[$propertypath][$propertyname] = $propertyvalue;
					}
				}
			}
		}

		$nodes = array();
		foreach($folderContent as $info) {
			$node = $this->getChild($info->getName(), $info);
			$node->setPropertyCache($properties[$this->path . '/' . $info->getName()]);
			$nodes[] = $node;
		}
		$this->dirContent = $nodes;
		return $this->dirContent;
	}

	/**
	 * Checks if a child exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {

		$path = $this->path . '/' . $name;
		return $this->fileView->file_exists($path);

	}

	/**
	 * Deletes all files in this directory, and then itself
	 *
	 * @return void
	 * @throws \Sabre\DAV\Exception\Forbidden
	 */
	public function delete() {

		if (!$this->info->isDeletable()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}

		if (!$this->fileView->rmdir($this->path)) {
			// assume it wasn't possible to remove due to permission issue
			throw new \Sabre\DAV\Exception\Forbidden();
		}

	}

	/**
	 * Returns available diskspace information
	 *
	 * @return array
	 */
	public function getQuotaInfo() {
		try {
			$path = \OC\Files\Filesystem::getView()->getRelativePath($this->info->getPath());
			$storageInfo = OC_Helper::getStorageInfo($path);
			return array(
				$storageInfo['used'],
				$storageInfo['free']
			);
		}
		catch (\OCP\Files\StorageNotAvailableException $e) {
			return array(0, 0);
		}
	}

	/**
	 * Returns a list of properties for this nodes.;
	 *
	 * The properties list is a list of propertynames the client requested,
	 * encoded as xmlnamespace#tagName, for example:
	 * http://www.example.org/namespace#author
	 * If the array is empty, all properties should be returned
	 *
	 * @param array $properties
	 * @return array
	 */
	public function getProperties($properties) {
		$props = parent::getProperties($properties);
		if (in_array(self::GETETAG_PROPERTYNAME, $properties) && !isset($props[self::GETETAG_PROPERTYNAME])) {
			$props[self::GETETAG_PROPERTYNAME] = $this->info->getEtag();
		}
		return $props;
	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @return int
	 */
	public function getSize() {
		return $this->info->getSize();
	}

}
