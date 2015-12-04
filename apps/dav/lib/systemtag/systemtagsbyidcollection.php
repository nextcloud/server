<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\SystemTag;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\ICollection;

use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\TagNotFoundException;

class SystemTagsByIdCollection implements ICollection {

	/**
	 * @var ISystemTagManager
	 */
	private $tagManager;

	/**
	 * SystemTagsByIdCollection constructor.
	 *
	 * @param ISystemTagManager $tagManager
	 */
	public function __construct($tagManager) {
		$this->tagManager = $tagManager;
	}

	function createFile($name, $data = null) {
		throw new Forbidden('Cannot create tags by id');
	}

	function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
	}

	function getChild($name) {
		try {
			$tags = $this->tagManager->getTagsByIds([$name]);
			return $this->makeNode(current($tags));
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			throw new NotFound('Tag with id ' . $name . ' not found', 0, $e);
		}
	}

	function getChildren() {
		$tags = $this->tagManager->getAllTags(true);
		return array_map(function($tag) {
			return $this->makeNode($tag);
		}, $tags);
	}

	function childExists($name) {
		try {
			$this->tagManager->getTagsByIds([$name]);
			return true;
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			return false;
		}
	}

	function delete() {
		throw new Forbidden('Permission denied to delete this collection');
	}

	function getName() {
		return 'systemtags';
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	function getLastModified() {
		return null;
	}

	/**
	 * Create a sabre node for the given system tag
	 *
	 * @param ISystemTag $tag
	 *
	 * @return SystemTagNode
	 */
	private function makeNode(ISystemTag $tag) {
		return new SystemTagNode($tag, $this->tagManager);
	}
}
