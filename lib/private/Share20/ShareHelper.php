<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Share20;

use OCP\Files\InvalidPathException;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
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
	 * @return array [ users => [Mapping $uid => $pathForUser], remotes => [Mapping $cloudId => $pathToMountRoot]]
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

	/**
	 * Sample:
	 * $users = [
	 *   'test1' => ['node_id' => 16, 'node_path' => '/foo'],
	 *   'test2' => ['node_id' => 23, 'node_path' => '/bar'],
	 *   'test3' => ['node_id' => 42, 'node_path' => '/cat'],
	 *   'test4' => ['node_id' => 48, 'node_path' => '/dog'],
	 * ];
	 *
	 * Node tree:
	 * - SixTeen is the parent of TwentyThree
	 * - TwentyThree is the parent of FortyTwo
	 * - FortyEight does not exist
	 *
	 * $return = [
	 *   'test1' => '/foo/TwentyThree/FortyTwo',
	 *   'test2' => '/bar/FortyTwo',
	 *   'test3' => '/cat',
	 * ],
	 *
	 * @param Node $node
	 * @param array[] $users
	 * @return array
	 */
	protected function getPathsForUsers(Node $node, array $users) {
		/** @var array[] $byId */
		$byId = [];
		/** @var array[] $results */
		$results = [];

		foreach ($users as $uid => $info) {
			if (!isset($byId[$info['node_id']])) {
				$byId[$info['node_id']] = [];
			}
			$byId[$info['node_id']][$uid] = $info['node_path'];
		}

		try {
			if (isset($byId[$node->getId()])) {
				foreach ($byId[$node->getId()] as $uid => $path) {
					$results[$uid] = $path;
				}
				unset($byId[$node->getId()]);
			}
		} catch (NotFoundException $e) {
			return $results;
		} catch (InvalidPathException $e) {
			return $results;
		}

		if (empty($byId)) {
			return $results;
		}

		$item = $node;
		$appendix = '/' . $node->getName();
		while (!empty($byId)) {
			try {
				/** @var Node $item */
				$item = $item->getParent();

				if (!empty($byId[$item->getId()])) {
					foreach ($byId[$item->getId()] as $uid => $path) {
						$results[$uid] = $path . $appendix;
					}
					unset($byId[$item->getId()]);
				}

				$appendix = '/' . $item->getName() . $appendix;
			} catch (NotFoundException $e) {
				return $results;
			} catch (InvalidPathException $e) {
				return $results;
			} catch (NotPermittedException $e) {
				return $results;
			}
		}

		return $results;
	}

	/**
	 * Sample:
	 * $remotes = [
	 *   'test1' => ['node_id' => 16, 'token' => 't1'],
	 *   'test2' => ['node_id' => 23, 'token' => 't2'],
	 *   'test3' => ['node_id' => 42, 'token' => 't3'],
	 *   'test4' => ['node_id' => 48, 'token' => 't4'],
	 * ];
	 *
	 * Node tree:
	 * - SixTeen is the parent of TwentyThree
	 * - TwentyThree is the parent of FortyTwo
	 * - FortyEight does not exist
	 *
	 * $return = [
	 *   'test1' => ['token' => 't1', 'node_path' => '/SixTeen'],
	 *   'test2' => ['token' => 't2', 'node_path' => '/SixTeen/TwentyThree'],
	 *   'test3' => ['token' => 't3', 'node_path' => '/SixTeen/TwentyThree/FortyTwo'],
	 * ],
	 *
	 * @param Node $node
	 * @param array[] $remotes
	 * @return array
	 */
	protected function getPathsForRemotes(Node $node, array $remotes) {
		/** @var array[] $byId */
		$byId = [];
		/** @var array[] $results */
		$results = [];

		foreach ($remotes as $cloudId => $info) {
			if (!isset($byId[$info['node_id']])) {
				$byId[$info['node_id']] = [];
			}
			$byId[$info['node_id']][$cloudId] = $info['token'];
		}

		$item = $node;
		while (!empty($byId)) {
			try {
				if (!empty($byId[$item->getId()])) {
					$path = $this->getMountedPath($item);
					foreach ($byId[$item->getId()] as $uid => $token) {
						$results[$uid] = [
							'node_path' => $path,
							'token' => $token,
						];
					}
					unset($byId[$item->getId()]);
				}

				/** @var Node $item */
				$item = $item->getParent();
			} catch (NotFoundException $e) {
				return $results;
			} catch (InvalidPathException $e) {
				return $results;
			} catch (NotPermittedException $e) {
				return $results;
			}
		}

		return $results;
	}

	/**
	 * @param Node $node
	 * @return string
	 */
	protected function getMountedPath(Node $node) {
		$path = $node->getPath();
		$sections = explode('/', $path, 4);
		return '/' . $sections[3];
	}
}
