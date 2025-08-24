<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events\Node;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use OCP\EventDispatcher\JsonSerializer;
use OCP\Files\Node;

/**
 * @since 20.0.0
 */
abstract class AbstractNodesEvent extends Event implements IWebhookCompatibleEvent {
	/**
	 * @since 20.0.0
	 */
	public function __construct(
		private Node $source,
		private Node $target,
	) {
	}

	/**
	 * @since 20.0.0
	 */
	public function getSource(): Node {
		return $this->source;
	}

	/**
	 * @since 20.0.0
	 */
	public function getTarget(): Node {
		return $this->target;
	}

	/**
	 * @since 30.0.0
	 */
	public function getWebhookSerializable(): array {
		return [
			'source' => JsonSerializer::serializeFileInfo($this->source),
			'target' => JsonSerializer::serializeFileInfo($this->target),
		];
	}
}
