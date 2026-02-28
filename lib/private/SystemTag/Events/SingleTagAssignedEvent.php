<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\SystemTag\Events;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

/**
 * Event triggered when tags are added to one object.
 *
 * Prefer listening to TagAssignedEvent as this is more efficient. Only used by the workflowengine.
 */
class SingleTagAssignedEvent extends Event implements IWebhookCompatibleEvent {
	/**
	 * @param list<int> $tags
	 */
	public function __construct(
		private readonly string $objectType,
		private readonly string $objectId,
		private readonly array $tags,
	) {
		parent::__construct();
	}

	public function getObjectType(): string {
		return $this->objectType;
	}

	public function getObjectId(): string {
		return $this->objectId;
	}

	/**
	 * @return list<int>
	 */
	public function getTags(): array {
		return $this->tags;
	}

	/**
	 * @return array{objectType: string, objectId: string, tagIds: list<int>}
	 */
	public function getWebhookSerializable(): array {
		return [
			'objectType' => $this->getObjectType(),
			'objectId' => $this->getObjectId(),
			'tagIds' => $this->getTags(),
		];
	}
}
