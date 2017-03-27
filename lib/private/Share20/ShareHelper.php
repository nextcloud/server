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

use OCP\Files\Node;
use OCP\Share\IManager;
use OCP\Share\IShareHelper;

class ShareHelper implements IShareHelper {

	/** @var IManager */
	private $shareManager;

	public function __construct(IManager $shareManager) {
		$this->shareManager = $shareManager;
	}

	/**
	 * @param Node $node
	 * @return array [ users => [Mapping $uid => $path], remotes => [Mapping $cloudId => $path]]
	 */
	public function getPathsForAccessList(Node $node) {
		$result = [
			'users' => [],
			'remotes' => [],
		];

		$accessList = $this->shareManager->getAccessList($node, true, true);
		if (!empty($accessList['users'])) {
			$result['users'] = $this->getPathsForUsers($node, $accessList['users']);
		}
		if (!empty($accessList['remote'])) {
			$result['remotes'] = $this->getPathsForRemotes($node, $accessList['remote']);
		}

		return $result;
	}

	protected function getPathsForUsers(Node $node, array $users) {
		$byId = $results = [];
		foreach ($users as $uid => $info) {
			if (!isset($byId[$info['node_id']])) {
				$byId[$info['node_id']] = [];
			}
			$byId[$info['node_id']][$uid] = $info['node_path'];
		}

		if (isset($byId[$node->getId()])) {
			foreach ($byId[$node->getId()] as $uid => $path) {
				$results[$uid] = $path;
			}
			unset($byId[$node->getId()]);
		}

		if (empty($byId)) {
			return $results;
		}

		$item = $node;
		$appendix = '/' . $node->getName();
		while (!empty($byId)) {
			$item = $item->getParent();

			if (!empty($byId[$item->getId()])) {
				foreach ($byId[$item->getId()] as $uid => $path) {
					$results[$uid] = $path . $appendix;
				}
				unset($byId[$item->getId()]);
			}

			$appendix = '/' . $item->getName() . $appendix;
		}

		return $results;
	}

	protected function getPathsForRemotes(Node $node, array $remotes) {
		$byId = $results = [];
		foreach ($remotes as $cloudId => $info) {
			if (!isset($byId[$info['node_id']])) {
				$byId[$info['node_id']] = [];
			}
			$byId[$info['node_id']][$cloudId] = $info['token'];
		}

		if (isset($byId[$node->getId()])) {
			foreach ($byId[$node->getId()] as $cloudId => $token) {
				$results[$cloudId] = [
					'node_path' => '/' . $node->getName(),
					'token' => $token,
				];
			}
			unset($byId[$node->getId()]);
		}

		if (empty($byId)) {
			return $results;
		}

		$item = $node;
		$path = '/' . $node->getName();
		while (!empty($byId)) {
			$item = $item->getParent();

			if (!empty($byId[$item->getId()])) {
				foreach ($byId[$item->getId()] as $uid => $token) {
					$results[$uid] = [
						'node_path' => $path,
						'token' => $token,
					];
				}
				unset($byId[$item->getId()]);
			}

			$path = '/' . $item->getName() . $path;
		}

		return $results;
	}
}
