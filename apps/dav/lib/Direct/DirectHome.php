<?php

declare(strict_types=1);

/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Direct;

use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class DirectHome implements ICollection {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var DirectMapper */
	private $mapper;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IThrottler */
	private $throttler;

	/** @var IRequest */
	private $request;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(
		IRootFolder $rootFolder,
		DirectMapper $mapper,
		ITimeFactory $timeFactory,
		IThrottler $throttler,
		IRequest $request,
		IEventDispatcher $eventDispatcher
	) {
		$this->rootFolder = $rootFolder;
		$this->mapper = $mapper;
		$this->timeFactory = $timeFactory;
		$this->throttler = $throttler;
		$this->request = $request;
		$this->eventDispatcher = $eventDispatcher;
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name): DirectFile {
		try {
			$direct = $this->mapper->getByToken($name);

			// Expired
			if ($direct->getExpiration() < $this->timeFactory->getTime()) {
				throw new NotFound();
			}

			return new DirectFile($direct, $this->rootFolder, $this->eventDispatcher);
		} catch (DoesNotExistException $e) {
			// Since the token space is so huge only throttle on non-existing token
			$this->throttler->registerAttempt('directlink', $this->request->getRemoteAddress());
			$this->throttler->sleepDelay($this->request->getRemoteAddress(), 'directlink');

			throw new NotFound();
		}
	}

	public function getChildren() {
		throw new MethodNotAllowed('Listing members of this collection is disabled');
	}

	public function childExists($name): bool {
		return false;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return 'direct';
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return 0;
	}
}
