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
	 * data is a readable stream resource
	 *
	 * @param string $name Name of the file
	 * @param resource $data Initial payload
	 * @return void
	 */
	public function createFile($name, $data = null) {

		$newPath = $this->path . '/' . $name;
		OC_Filesystem::file_put_contents($newPath,$data);

	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @return void
	 */
	public function createDirectory($name) {

		$newPath = $this->path . '/' . $name;
		OC_Filesystem::mkdir($newPath);

	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * @param string $name
	 * @throws Sabre_DAV_Exception_FileNotFound
	 * @return Sabre_DAV_INode
	 */
	public function getChild($name) {

		$path = $this->path . '/' . $name;

		if (!OC_Filesystem::file_exists($path)) throw new Sabre_DAV_Exception_NotFound('File with name ' . $path . ' could not be located');

		if (OC_Filesystem::is_dir($path)) {

			return new OC_Connector_Sabre_Directory($path);

		} else {

			return new OC_Connector_Sabre_File($path);

		}

	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return Sabre_DAV_INode[]
	 */
	public function getChildren() {

		$nodes = array();
		// foreach(scandir($this->path) as $node) if($node!='.' && $node!='..') $nodes[] = $this->getChild($node);
		if( OC_Filesystem::is_dir($this->path . '/')){
			$dh = OC_Filesystem::opendir($this->path . '/');
			while(( $node = readdir($dh)) !== false ){
				if($node!='.' && $node!='..'){
					$nodes[] = $this->getChild($node);
				}
			}
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
		return OC_Filesystem::file_exists($path);

	}

	/**
	 * Deletes all files in this directory, and then itself
	 *
	 * @return void
	 */
	public function delete() {

		if ($this->path != "/Shared") {
			foreach($this->getChildren() as $child) $child->delete();
			OC_Filesystem::rmdir($this->path);
		}

	}

	/**
	 * Returns available diskspace information
	 *
	 * @return array
	 */
	public function getQuotaInfo() {
		$rootInfo=OC_FileCache::get('');
		return array(
			$rootInfo['size'],
			OC_Filesystem::free_space()
		);

	}

}

