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

class OC_Connector_Sabre_Directory extends OC_Connector_Sabre_Node implements Sabre_DAV_ICollection, Sabre_DAV_IQuota {

	/**
	 * Creates a new file in the directory
	 *
	 * Data will either be supplied as a stream resource, or in certain cases
	 * as a string. Keep in mind that you may have to support either.
	 *
	 * After succesful creation of the file, you may choose to return the ETag
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
	 * @return null|string
	 */
	public function createFile($name, $data = null) {
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			$info = OC_FileChunking::decodeName($name);
			if (empty($info)) {
				throw new Sabre_DAV_Exception_NotImplemented();
			}
			$chunk_handler = new OC_FileChunking($info);
			$chunk_handler->store($info['index'], $data);
			if ($chunk_handler->isComplete()) {
				$newPath = $this->path . '/' . $info['name'];
				$chunk_handler->file_assemble($newPath);
				return OC_Connector_Sabre_Node::getETagPropertyForPath($newPath);
			}
		} else {
			$newPath = $this->path . '/' . $name;

			// mark file as partial while uploading (ignored by the scanner)
			$partpath = $newPath . '.part';

			\OC\Files\Filesystem::file_put_contents($partpath, $data);

			//detect aborted upload
			if (isset ($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PUT' ) {
				if (isset($_SERVER['CONTENT_LENGTH'])) {
					$expected = $_SERVER['CONTENT_LENGTH'];
					$actual = \OC\Files\Filesystem::filesize($partpath);
					if ($actual != $expected) {
						\OC\Files\Filesystem::unlink($partpath);
						throw new Sabre_DAV_Exception_BadRequest(
								'expected filesize ' . $expected . ' got ' . $actual);
					}
				}
			}

			// rename to correct path
			\OC\Files\Filesystem::rename($partpath, $newPath);

			// allow sync clients to send the mtime along in a header
			$mtime = OC_Request::hasModificationTime();
			if ($mtime !== false) {
				if(\OC\Files\Filesystem::touch($newPath, $mtime)) {
					header('X-OC-MTime: accepted');
				}
			}

			return OC_Connector_Sabre_Node::getETagPropertyForPath($newPath);
		}

		return null;
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @return void
	 */
	public function createDirectory($name) {

		$newPath = $this->path . '/' . $name;
		if(!\OC\Files\Filesystem::mkdir($newPath)) {
			throw new Sabre_DAV_Exception_Forbidden('Could not create directory '.$newPath);
		}

	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * @param string $name
	 * @throws Sabre_DAV_Exception_FileNotFound
	 * @return Sabre_DAV_INode
	 */
	public function getChild($name, $info = null) {

		$path = $this->path . '/' . $name;
		if (is_null($info)) {
			$info = \OC\Files\Filesystem::getFileInfo($path);
		}

		if (!$info) {
			throw new Sabre_DAV_Exception_NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info['mimetype'] == 'httpd/unix-directory') {
			$node = new OC_Connector_Sabre_Directory($path);
		} else {
			$node = new OC_Connector_Sabre_File($path);
		}

		$node->setFileinfoCache($info);
		return $node;
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return Sabre_DAV_INode[]
	 */
	public function getChildren() {

		$folder_content = \OC\Files\Filesystem::getDirectoryContent($this->path);
		$paths = array();
		foreach($folder_content as $info) {
			$paths[] = $this->path.'/'.$info['name'];
			$properties[$this->path.'/'.$info['name']][self::GETETAG_PROPERTYNAME] = '"' . $info['etag'] . '"';
		}
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
		foreach($folder_content as $info) {
			$node = $this->getChild($info['name'], $info);
			$node->setPropertyCache($properties[$this->path.'/'.$info['name']]);
			$nodes[] = $node;
		}
		return $nodes;
	}

	/**
	 * Checks if a child exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {

		$path = $this->path . '/' . $name;
		return \OC\Files\Filesystem::file_exists($path);

	}

	/**
	 * Deletes all files in this directory, and then itself
	 *
	 * @return void
	 */
	public function delete() {

		if ($this->path != "/Shared") {
			foreach($this->getChildren() as $child) $child->delete();
			\OC\Files\Filesystem::rmdir($this->path);
		}

	}

	/**
	 * Returns available diskspace information
	 *
	 * @return array
	 */
	public function getQuotaInfo() {
		$rootInfo=\OC\Files\Filesystem::getFileInfo('');
		return array(
			$rootInfo['size'],
			\OC\Files\Filesystem::free_space()
		);

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
	 * @return void
	 */
	public function getProperties($properties) {
		$props = parent::getProperties($properties);
		if (in_array(self::GETETAG_PROPERTYNAME, $properties) && !isset($props[self::GETETAG_PROPERTYNAME])) {
			$props[self::GETETAG_PROPERTYNAME]
				= OC_Connector_Sabre_Node::getETagPropertyForPath($this->path);
		}
		return $props;
	}
}
