<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Node;

/**
 * Interface IShareHelper
 *
 * @since 12.0.0
 */
#[Consumable(since: '12.0.0')]
interface IShareHelper {
	/**
	 * @return array{users: array<string, string>, remotes: array<string, array{token: string, node_path: string}>} [ users => [Mapping $uid => $pathForUser], remotes => [Mapping $cloudId => $pathToMountRoot]]
	 * @since 12
	 */
	public function getPathsForAccessList(Node $node): array;
}
