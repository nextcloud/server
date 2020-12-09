<?php

declare(strict_types=1);

/**
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\WellKnown;

use OC\WellKnown\Exceptions\NotManagedWellKnownRequestException;
use OC\WellKnown\Exceptions\WellKnownRequestException;
use OC\WellKnown\Model\WellKnown;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\WellKnown\Event\WellKnownEvent;
use OCP\WellKnown\IWellKnownManager;
use OCP\WellKnown\Model\IWellKnown;

/**
 * @since 21.0.0
 *
 * Class WellKnownManager
 *
 * @package OC\WellKnown
 */
class WellKnownManager implements IWellKnownManager {


	/** @var IEventDispatcher */
	private $eventDispatcher;


	/** @var array */
	private $managedServices =
		[
			self::WEBFINGER,
			self::NODEINFO
		];


	/**
	 * WellKnownManager constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher
	 *
	 * @since 21.0.0
	 *
	 */
	public function __construct(IEventDispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;
	}


	/**
	 * @param IRequest $request
	 *
	 * @return IWellKnown
	 * @throws WellKnownRequestException
	 * @throws NotManagedWellKnownRequestException
	 * @since 21.0.0
	 */
	public function manageRequest(IRequest $request): IWellKnown {
		$service = $request->getParam('service', '');
		if (!in_array($service, $this->managedServices)) {
			throw new NotManagedWellKnownRequestException(Http::STATUS_NOT_FOUND);
		}

		$wellKnown = new WellKnown($service, $request);
		$this->eventDispatcher->dispatchTyped(new WellKnownEvent($wellKnown));

		if ($this->isEmpty($wellKnown)) {
			throw new WellKnownRequestException(Http::STATUS_NOT_FOUND);
		}

		return $wellKnown;
	}


	/**
	 * @param WellKnown $wellKnown
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	private function isEmpty(WellKnown $wellKnown): bool {
		if (!empty($wellKnown->getLinks())) {
			return false;
		}

		if (!empty($wellKnown->getProperties())) {
			return false;
		}

		if (!empty($wellKnown->getAliases())) {
			return false;
		}

		return true;
	}
}
