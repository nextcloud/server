<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events\Node;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * @since 20.0.0
 */
abstract class AbstractNodeEvent extends Event {
	/**
	 * @since 20.0.0
	 */
	public function __construct(
		private Node $node
	) {
	}

	/**
	 * @since 20.0.0
	 */
	public function getNode(): Node {
		return $this->node;
	}
}
