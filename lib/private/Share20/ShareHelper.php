<?php

declare(strict_types=1);

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
use Override;

class ShareHelper implements IShareHelper {
	public function __construct(
		private readonly IManager $shareManager,
	) {
	}

	#[Override]
	public function getPathsForAccessList(Node $node): array {
		$result = [
			'users' => [],
			'remotes' => [],
		];

		$accessList = $this->shareManager->getAccessList($node, true, true);
		if (isset($accessList['users']) && $accessList['users'] !== []) {
			$result['users'] = $this->getPathsForUsers($node, $accessList['users']);
		}

		if (isset($accessList['remote']) && $accessList['remote'] !== []) {
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
	 * @param non-empty-array<string, array{node_id: int, node_path: string}> $users
	 * @return array<string, string>
	 */
	protected function getPathsForUsers(Node $node, array $users): array {
		/** @var array<int, array<string, string>> $byId */
		$byId = [];
		/** @var array<string, string> $results */
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
		} catch (NotFoundException|InvalidPathException) {
			return $results;
		}

		if ($byId === []) {
			return $results;
		}

		$item = $node;
		$appendix = '/' . $node->getName();
		while (!empty($byId)) {
			try {
				$item = $item->getParent();

				if ($byId[$item->getId()] !== []) {
					foreach ($byId[$item->getId()] as $uid => $path) {
						$results[$uid] = $path . $appendix;
					}

					unset($byId[$item->getId()]);
				}

				$appendix = '/' . $item->getName() . $appendix;
			} catch (NotFoundException|InvalidPathException|NotPermittedException) {
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
	 * @param non-empty-array<string, array{node_id: int, token: string}> $remotes
	 * @return array<string, array{token: string, node_path: string}>
	 */
	protected function getPathsForRemotes(Node $node, array $remotes): array {
		/** @var array<int, array<string, string>> $byId */
		$byId = [];
		/** @var array<string, array{token: string, node_path: string}> $results */
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
				if ($byId[$item->getId()] !== []) {
					$path = $this->getMountedPath($item);
					foreach ($byId[$item->getId()] as $uid => $token) {
						$results[$uid] = [
							'node_path' => $path,
							'token' => $token,
						];
					}

					unset($byId[$item->getId()]);
				}

				$item = $item->getParent();
			} catch (NotFoundException|InvalidPathException|NotPermittedException) {
				return $results;
			}
		}

		return $results;
	}

	protected function getMountedPath(Node $node): string {
		$path = $node->getPath();
		$sections = explode('/', $path, 4);
		return '/' . $sections[3];
	}
}
