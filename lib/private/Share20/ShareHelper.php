<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Share20;

use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;

class ShareHelper {
	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(IRootFolder $rootFolder) {
		$this->rootFolder = $rootFolder;
	}

	/**
	 * If a user has access to a file
	 *
	 * @param Node $node
	 * @param array $users Array of userIds
	 * @return array Mapping $uid to an array of nodes
	 */
	public function getPathsForAccessList(Node $node, $users) {
		$result = [];

		foreach ($users as $user) {
			try {
				$userFolder = $this->rootFolder->getUserFolder($user);
			} catch (NotFoundException $e) {
				continue;
			}

			$nodes = $userFolder->getById($node->getId());
			if ($nodes === []) {
				continue;
			}

			$result[$user] = $nodes;
		}

		return $result;
	}
}
