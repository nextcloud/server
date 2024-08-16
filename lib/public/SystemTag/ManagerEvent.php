<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\SystemTag;

use OCP\EventDispatcher\Event;

/**
 * Class ManagerEvent
 *
 * @since 9.0.0
 */
class ManagerEvent extends Event {
	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_CREATE = 'OCP\SystemTag\ISystemTagManager::createTag';

	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_UPDATE = 'OCP\SystemTag\ISystemTagManager::updateTag';

	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_DELETE = 'OCP\SystemTag\ISystemTagManager::deleteTag';

	/** @var string */
	protected $event;
	/** @var ISystemTag */
	protected $tag;
	/** @var ISystemTag */
	protected $beforeTag;

	/**
	 * DispatcherEvent constructor.
	 *
	 * @param string $event
	 * @param ISystemTag $tag
	 * @param ISystemTag|null $beforeTag
	 * @since 9.0.0
	 */
	public function __construct(string $event, ISystemTag $tag, ?ISystemTag $beforeTag = null) {
		$this->event = $event;
		$this->tag = $tag;
		$this->beforeTag = $beforeTag;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent(): string {
		return $this->event;
	}

	/**
	 * @return ISystemTag
	 * @since 9.0.0
	 */
	public function getTag(): ISystemTag {
		return $this->tag;
	}

	/**
	 * @return ISystemTag
	 * @since 9.0.0
	 * @throws \BadMethodCallException
	 */
	public function getTagBefore(): ISystemTag {
		if ($this->event !== self::EVENT_UPDATE) {
			throw new \BadMethodCallException('getTagBefore is only available on the update Event');
		}
		return $this->beforeTag;
	}
}
