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
 * Class ManagerEvent
 *
 * @package OCP\SystemTag
 * @since 9.0.0
 */
class ManagerEvent extends Event {

	const EVENT_CREATE = 'OCP\SystemTag\ISystemTagManager::createTag';
	const EVENT_UPDATE = 'OCP\SystemTag\ISystemTagManager::updateTag';
	const EVENT_DELETE = 'OCP\SystemTag\ISystemTagManager::deleteTag';

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
	 * @param ISystemTag $beforeTag
	 * @since 9.0.0
	 */
	public function __construct($event, ISystemTag $tag, ISystemTag $beforeTag = null) {
		$this->event = $event;
		$this->tag = $tag;
		$this->beforeTag = $beforeTag;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @return ISystemTag
	 * @since 9.0.0
	 */
	public function getTag() {
		return $this->tag;
	}

	/**
	 * @return ISystemTag
	 * @since 9.0.0
	 */
	public function getTagBefore() {
		if ($this->event !== self::EVENT_UPDATE) {
			throw new \BadMethodCallException('getTagBefore is only available on the update Event');
		}
		return $this->beforeTag;
	}
}
