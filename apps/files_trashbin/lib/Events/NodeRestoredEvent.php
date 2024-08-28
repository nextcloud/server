<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Events;

use OCP\Files\Events\Node\AbstractNodesEvent;
use OCP\Files\Node;

/**
 * @since 28.0.0
 */
class NodeRestoredEvent extends AbstractNodesEvent {
	public function __construct(Node $source, Node $target) {
		parent::__construct($source, $target);
	}
}
