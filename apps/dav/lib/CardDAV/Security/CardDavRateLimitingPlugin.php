<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV\Security;

use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ServerPlugin;
use function count;
use function explode;

class CardDavRateLimitingPlugin extends ServerPlugin {
	private ?string $userId;

	public function __construct(private Limiter $limiter,
		private IUserManager $userManager,
		private CardDavBackend $cardDavBackend,
		private LoggerInterface $logger,
		private IConfig $config,
		?string $userId) {
		$this->limiter = $limiter;
		$this->userManager = $userManager;
		$this->cardDavBackend = $cardDavBackend;
		$this->config = $config;
		$this->logger = $logger;
		$this->userId = $userId;
	}

	public function initialize(DAV\Server $server): void {
		$server->on('beforeBind', [$this, 'beforeBind'], 1);
	}

	public function beforeBind(string $path): void {
		if ($this->userId === null) {
			// We only care about authenticated users here
			return;
		}
		$user = $this->userManager->get($this->userId);
		if ($user === null) {
			// We only care about authenticated users here
			return;
		}

		$pathParts = explode('/', $path);
		if (count($pathParts) === 4 && $pathParts[0] === 'addressbooks') {
			// Path looks like addressbooks/users/username/addressbooksname so a new addressbook is created
			try {
				$this->limiter->registerUserRequest(
					'carddav-create-address-book',
					(int) $this->config->getAppValue('dav', 'rateLimitAddressBookCreation', '10'),
					(int) $this->config->getAppValue('dav', 'rateLimitPeriodAddressBookCreation', '3600'),
					$user
				);
			} catch (RateLimitExceededException $e) {
				throw new TooManyRequests('Too many addressbooks created', 0, $e);
			}

			$addressBookLimit = (int) $this->config->getAppValue('dav', 'maximumAdressbooks', '10');
			if ($addressBookLimit === -1) {
				return;
			}
			$numAddressbooks = $this->cardDavBackend->getAddressBooksForUserCount('principals/users/' . $user->getUID());

			if ($numAddressbooks >= $addressBookLimit) {
				$this->logger->warning('Maximum number of address books reached', [
					'addressbooks' => $numAddressbooks,
					'addressBookLimit' => $addressBookLimit,
				]);
				throw new Forbidden('AddressBook limit reached', 0);
			}
		}
	}

}
