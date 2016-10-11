<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\SystemTag;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MapperEvent
 *
 * @package OCP\SystemTag
 * @since 9.0.0
 */
class MapperEvent extends Event {

	const EVENT_ASSIGN = 'OCP\SystemTag\ISystemTagObjectMapper::assignTags';
	const EVENT_UNASSIGN = 'OCP\SystemTag\ISystemTagObjectMapper::unassignTags';

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
	public function __construct($event, $objectType, $objectId, array $tags) {
		$this->event = $event;
		$this->objectType = $objectType;
		$this->objectId = $objectId;
		$this->tags = $tags;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectType() {
		return $this->objectType;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	/**
	 * @return int[]
	 * @since 9.0.0
	 */
	public function getTags() {
		return $this->tags;
	}
}
