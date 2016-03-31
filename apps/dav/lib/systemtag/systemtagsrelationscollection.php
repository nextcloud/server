<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\SimpleCollection;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\Files\IRootFolder;

class SystemTagsRelationsCollection extends SimpleCollection {

	/**
	 * SystemTagsRelationsCollection constructor.
	 *
	 * @param ISystemTagManager $tagManager
	 * @param ISystemTagObjectMapper $tagMapper
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 * @param IRootFolder $fileRoot
	 */
	public function __construct(
		ISystemTagManager $tagManager,
		ISystemTagObjectMapper $tagMapper,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IRootFolder $fileRoot
	) {
		$children = [
			new SystemTagsObjectTypeCollection(
				'files',
				$tagManager,
				$tagMapper,
				$userSession,
				$groupManager,
				$fileRoot
			),
		];

		parent::__construct('root', $children);
	}

	function getName() {
		return 'systemtags-relations';
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

}
