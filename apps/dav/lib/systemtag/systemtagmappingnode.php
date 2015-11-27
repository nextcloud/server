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

use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\MethodNotAllowed;

use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

/**
 * Mapping node for system tag to object id
 */
class SystemTagMappingNode extends SystemTagNode {

	/**
	 * @var ISystemTagObjectMapper
	 */
	private $tagMapper;

	/**
	 * @var string
	 */
	private $objectId;

	/**
	 * @var string
	 */
	private $objectType;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param ISystemTag $tag system tag
	 */
	public function __construct(
		ISystemTag $tag,
		$objectId,
		$objectType,
		ISystemTagManager $tagManager,
		ISystemTagObjectMapper $tagMapper
	) {
		$this->objectId = $objectId;
		$this->objectType = $objectType;
		$this->tagMapper = $tagMapper;
		parent::__construct($tag, $tagManager);
	}

	/**
	 * Delete tag to object association
	 */
	public function delete() {
		try {
			$this->tagMapper->unassignTags($this->objectId, $this->objectType, $this->tag->getId());
		} catch (TagNotFoundException $e) {
			// can happen if concurrent deletion occurred
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found', 0, $e);
		}
	}
}
