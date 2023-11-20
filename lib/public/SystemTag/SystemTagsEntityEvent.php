<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\SystemTag;

use OCP\EventDispatcher\Event;

/**
 * Class SystemTagsEntityEvent
 *
 * @since 9.1.0
 * @since 28.0.0 Dispatched as a typed event
 */
class SystemTagsEntityEvent extends Event {
	/**
	 * @deprecated 22.0.0 Listen to the typed event instead
	 */
	public const EVENT_ENTITY = 'OCP\SystemTag\ISystemTagManager::registerEntity';

	/** @var \Closure[] */
	protected $collections;

	/**
	 * @since 9.1.0
	 */
	public function __construct() {
		parent::__construct();
		$this->collections = [];
	}

	/**
	 * @param string $name
	 * @param \Closure $entityExistsFunction The closure should take one
	 *                 argument, which is the id of the entity, that tags
	 *                 should be handled for. The return should then be bool,
	 *                 depending on whether tags are allowed (true) or not.
	 * @throws \OutOfBoundsException when the entity name is already taken
	 * @since 9.1.0
	 */
	public function addEntityCollection(string $name, \Closure $entityExistsFunction) {
		if (isset($this->collections[$name])) {
			throw new \OutOfBoundsException('Duplicate entity name "' . $name . '"');
		}

		$this->collections[$name] = $entityExistsFunction;
	}

	/**
	 * @return \Closure[]
	 * @since 9.1.0
	 */
	public function getEntityCollections(): array {
		return $this->collections;
	}
}
