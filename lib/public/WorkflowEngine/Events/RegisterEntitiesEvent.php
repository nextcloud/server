<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\Events;

use OCP\EventDispatcher\Event;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IManager;

/**
 * @since 18.0.0
 */
class RegisterEntitiesEvent extends Event {
	/** @var IManager */
	private $manager;

	/**
	 * @since 18.0.0
	 */
	public function __construct(IManager $manager) {
		parent::__construct();

		$this->manager = $manager;
	}

	/**
	 * @since 18.0.0
	 */
	public function registerEntity(IEntity $entity): void {
		$this->manager->registerEntity($entity);
	}
}
