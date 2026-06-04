<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function __construct(
		private IRootFolder $rootFolder,
		private DirectMapper $mapper,
		private ITimeFactory $timeFactory,
		private IThrottler $throttler,
		private IRequest $request,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	#[\Override]
	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	#[\Override]
	public function createDirectory($name) {
		throw new Forbidden();
	}

	#[\Override]
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
			$this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), 'directlink');

			throw new NotFound();
		}
	}

	#[\Override]
	public function getChildren() {
		throw new MethodNotAllowed('Listing members of this collection is disabled');
	}

	#[\Override]
	public function childExists($name): bool {
		return false;
	}

	#[\Override]
	public function delete() {
		throw new Forbidden();
	}

	#[\Override]
	public function getName(): string {
		return 'direct';
	}

	#[\Override]
	public function setName($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function getLastModified(): int {
		return 0;
	}
}
