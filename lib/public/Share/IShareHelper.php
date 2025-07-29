<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

use OCP\Files\Node;

/**
 * Interface IShareHelper
 *
 * @since 12
 */
interface IShareHelper {
	/**
	 * @param Node $node
	 * @return array [ users => [Mapping $uid => $pathForUser], remotes => [Mapping $cloudId => $pathToMountRoot]]
	 * @since 12
	 */
	public function getPathsForAccessList(Node $node);
}
