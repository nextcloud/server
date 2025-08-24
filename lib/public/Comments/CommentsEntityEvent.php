<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

use OCP\EventDispatcher\Event;

/**
 * Class CommentsEntityEvent
 *
 * @since 9.1.0
 * @since 28.0.0 Dispatched as a typed event
 */
class CommentsEntityEvent extends Event {
	/**
	 * @since 9.1.0
	 * @deprecated 22.0.0 - Listen to the typed event instead.
	 */
	public const EVENT_ENTITY = 'OCP\Comments\ICommentsManager::registerEntity';

	/** @var \Closure[] */
	protected $collections;

	/**
	 * DispatcherEvent constructor.
	 *
	 * @since 9.1.0
	 */
	public function __construct() {
		parent::__construct();
		$this->collections = [];
	}

	/**
	 * @param string $name
	 * @param \Closure $entityExistsFunction The closure should take one
	 *                                       argument, which is the id of the entity, that comments
	 *                                       should be handled for. The return should then be bool,
	 *                                       depending on whether comments are allowed (true) or not.
	 * @throws \OutOfBoundsException when the entity name is already taken
	 * @since 9.1.0
	 */
	public function addEntityCollection($name, \Closure $entityExistsFunction) {
		if (isset($this->collections[$name])) {
			throw new \OutOfBoundsException('Duplicate entity name "' . $name . '"');
		}

		$this->collections[$name] = $entityExistsFunction;
	}

	/**
	 * @return \Closure[]
	 * @since 9.1.0
	 */
	public function getEntityCollections() {
		return $this->collections;
	}
}
