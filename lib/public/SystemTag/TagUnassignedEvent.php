<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\SystemTag;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

/**
 * Event class for when a system tag is unassigned from an object
 *
 * @since 32.0.0
 */
class TagUnassignedEvent extends Event implements IWebhookCompatibleEvent {
	protected string $objectType;
	/** @var list<string> */
	protected array $objectIds;
	/** @var list<int> */
	protected array $tags;

	/**
	 * constructor
	 *
	 * @param list<string> $objectIds
	 * @param list<int> $tags
	 * @since 32.0.0
	 */
	public function __construct(string $objectType, array $objectIds, array $tags) {
		parent::__construct();
		$this->objectType = $objectType;
		$this->objectIds = $objectIds;
		$this->tags = $tags;
	}

	/**
	 * @since 32.0.0
	 */
	public function getObjectType(): string {
		return $this->objectType;
	}

	/**
	 * @return list<string>
	 * @since 32.0.0
	 */
	public function getObjectIds(): array {
		return $this->objectIds;
	}

	/**
	 * @return list<int>
	 * @since 32.0.0
	 */
	public function getTags(): array {
		return $this->tags;
	}

	/**
	 * @return array{objectType: string, objectIds: list<string>, tagIds: list<int>}
	 * @since 32.0.0
	 */
	public function getWebhookSerializable(): array {
		return [
			'objectType' => $this->getObjectType(),
			'objectIds' => $this->getObjectIds(),
			'tagIds' => $this->getTags(),
		];
	}
}
