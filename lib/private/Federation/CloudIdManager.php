<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017, Robin Appelman <robin@icewind.nl>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Guillaume Virlet <github@virlet.org>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OC\Federation;

use OCA\DAV\Events\CardUpdatedEvent;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\User\Events\UserChangedEvent;

class CloudIdManager implements ICloudIdManager {
	/** @var IManager */
	private $contactsManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserManager */
	private $userManager;
	private ICache $memCache;
	/** @var array[] */
	private array $cache = [];

	public function __construct(
		IManager $contactsManager,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		ICacheFactory $cacheFactory,
		IEventDispatcher $eventDispatcher
	) {
		$this->contactsManager = $contactsManager;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->memCache = $cacheFactory->createDistributed('cloud_id_');
		$eventDispatcher->addListener(UserChangedEvent::class, [$this, 'handleUserEvent']);
		$eventDispatcher->addListener(CardUpdatedEvent::class, [$this, 'handleCardEvent']);
	}

	public function handleUserEvent(Event $event): void {
		if ($event instanceof UserChangedEvent && $event->getFeature() === 'displayName') {
			$userId = $event->getUser()->getUID();
			$key = $userId . '@local';
			unset($this->cache[$key]);
			$this->memCache->remove($key);
		}
	}

	public function handleCardEvent(Event $event): void {
		if ($event instanceof CardUpdatedEvent) {
			$data = $event->getCardData()['carddata'];
			foreach (explode("\r\n", $data) as $line) {
				if (str_starts_with($line, "CLOUD;")) {
					$parts = explode(':', $line, 2);
					if (isset($parts[1])) {
						$key = $parts[1];
						unset($this->cache[$key]);
						$this->memCache->remove($key);
					}
				}
			}
		}
	}

	/**
	 * @param string $cloudId
	 * @return ICloudId
	 * @throws \InvalidArgumentException
	 */
	public function resolveCloudId(string $cloudId): ICloudId {
		// TODO magic here to get the url and user instead of just splitting on @

		if (!$this->isValidCloudId($cloudId)) {
			throw new \InvalidArgumentException('Invalid cloud id');
		}

		// Find the first character that is not allowed in user names
		$id = $this->fixRemoteURL($cloudId);
		$posSlash = strpos($id, '/');
		$posColon = strpos($id, ':');

		if ($posSlash === false && $posColon === false) {
			$invalidPos = \strlen($id);
		} elseif ($posSlash === false) {
			$invalidPos = $posColon;
		} elseif ($posColon === false) {
			$invalidPos = $posSlash;
		} else {
			$invalidPos = min($posSlash, $posColon);
		}

		$lastValidAtPos = strrpos($id, '@', $invalidPos - strlen($id));

		if ($lastValidAtPos !== false) {
			$user = substr($id, 0, $lastValidAtPos);
			$remote = substr($id, $lastValidAtPos + 1);

			$this->userManager->validateUserId($user);

			if (!empty($user) && !empty($remote)) {
				return new CloudId($id, $user, $remote, $this->getDisplayNameFromContact($id));
			}
		}
		throw new \InvalidArgumentException('Invalid cloud id');
	}

	protected function getDisplayNameFromContact(string $cloudId): ?string {
		$addressBookEntries = $this->contactsManager->search($cloudId, ['CLOUD'], [
			'limit' => 1,
			'enumeration' => false,
			'fullmatch' => false,
			'strict_search' => true,
		]);
		foreach ($addressBookEntries as $entry) {
			if (isset($entry['CLOUD'])) {
				foreach ($entry['CLOUD'] as $cloudID) {
					if ($cloudID === $cloudId) {
						// Warning, if user decides to make his full name local only,
						// no FN is found on federated servers
						if (isset($entry['FN'])) {
							return $entry['FN'];
						} else {
							return $cloudID;
						}
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param string $user
	 * @param string|null $remote
	 * @return CloudId
	 */
	public function getCloudId(string $user, ?string $remote): ICloudId {
		$isLocal = $remote === null;
		if ($isLocal) {
			$remote = rtrim($this->removeProtocolFromUrl($this->urlGenerator->getAbsoluteURL('/')), '/');
			$fixedRemote = $this->fixRemoteURL($remote);
			$host = $fixedRemote;
		} else {
			// note that for remote id's we don't strip the protocol for the remote we use to construct the CloudId
			// this way if a user has an explicit non-https cloud id this will be preserved
			// we do still use the version without protocol for looking up the display name
			$fixedRemote = $this->fixRemoteURL($remote);
			$host = $this->removeProtocolFromUrl($fixedRemote);
		}

		$key = $user . '@' . ($isLocal ? 'local' : $host);
		$cached = $this->cache[$key] ?? $this->memCache->get($key);
		if ($cached) {
			$this->cache[$key] = $cached; // put items from memcache into local cache
			return new CloudId($cached['id'], $cached['user'], $cached['remote'], $cached['displayName']);
		}

		if ($isLocal) {
			$localUser = $this->userManager->get($user);
			$displayName = $localUser ? $localUser->getDisplayName() : '';
		} else {
			$displayName = $this->getDisplayNameFromContact($user . '@' . $host);
		}
		$id = $user . '@' . $remote;

		$data = [
			'id' => $id,
			'user' => $user,
			'remote' => $fixedRemote,
			'displayName' => $displayName,
		];
		$this->cache[$key] = $data;
		$this->memCache->set($key, $data, 15 * 60);
		return new CloudId($id, $user, $fixedRemote, $displayName);
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public function removeProtocolFromUrl(string $url): string {
		if (str_starts_with($url, 'https://')) {
			return substr($url, 8);
		}
		if (str_starts_with($url, 'http://')) {
			return substr($url, 7);
		}

		return $url;
	}

	/**
	 * Strips away a potential file names and trailing slashes:
	 * - http://localhost
	 * - http://localhost/
	 * - http://localhost/index.php
	 * - http://localhost/index.php/s/{shareToken}
	 *
	 * all return: http://localhost
	 *
	 * @param string $remote
	 * @return string
	 */
	protected function fixRemoteURL(string $remote): string {
		$remote = str_replace('\\', '/', $remote);
		if ($fileNamePosition = strpos($remote, '/index.php')) {
			$remote = substr($remote, 0, $fileNamePosition);
		}
		$remote = rtrim($remote, '/');

		return $remote;
	}

	/**
	 * @param string $cloudId
	 * @return bool
	 */
	public function isValidCloudId(string $cloudId): bool {
		return str_contains($cloudId, '@');
	}
}
