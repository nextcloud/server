<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\SystemTag;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

/**
 * Class MapperEvent
 *
 * @since 9.0.0
 */
class MapperEvent extends Event implements IWebhookCompatibleEvent {
	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_ASSIGN = 'OCP\SystemTag\ISystemTagObjectMapper::assignTags';

	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_UNASSIGN = 'OCP\SystemTag\ISystemTagObjectMapper::unassignTags';

	/** @var string */
	protected $event;
	/** @var string */
	protected $objectType;
	/** @var string */
	protected $objectId;
	/** @var int[] */
	protected $tags;

	/**
	 * DispatcherEvent constructor.
	 *
	 * @param string $event
	 * @param string $objectType
	 * @param string $objectId
	 * @param int[] $tags
	 * @since 9.0.0
	 */
	public function __construct(string $event, string $objectType, string $objectId, array $tags) {
		$this->event = $event;
		$this->objectType = $objectType;
		$this->objectId = $objectId;
		$this->tags = $tags;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent(): string {
		return $this->event;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectType(): string {
		return $this->objectType;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectId(): string {
		return $this->objectId;
	}

	/**
	 * @return int[]
	 * @since 9.0.0
	 */
	public function getTags(): array {
		return $this->tags;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getWebhookSerializable(): array {
		return [
			'eventType' => $this->getEvent(),
			'objectType' => $this->getObjectType(),
			'objectId' => $this->getObjectId(),
			'tagIds' => $this->getTags(),
		];
	}
}
