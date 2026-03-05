<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCA\DAV\Events\CardUpdatedEvent;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Federation\ICloudIdResolver;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\User\Events\UserChangedEvent;

class CloudIdManager implements ICloudIdManager {
	private ICache $memCache;
	private ICache $displayNameCache;
	private array $cache = [];
	/** @var ICloudIdResolver[] */
	private array $cloudIdResolvers = [];

	public function __construct(
		ICacheFactory $cacheFactory,
		IEventDispatcher $eventDispatcher,
		private IManager $contactsManager,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
	) {
		$this->memCache = $cacheFactory->createDistributed('cloud_id_');
		$this->displayNameCache = $cacheFactory->createDistributed('cloudid_name_');
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
				if (str_starts_with($line, 'CLOUD;')) {
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

		foreach ($this->cloudIdResolvers as $resolver) {
			if ($resolver->isValidCloudId($cloudId)) {
				return $resolver->resolveCloudId($cloudId);
			}
		}

		if (!$this->isValidCloudId($cloudId)) {
			throw new \InvalidArgumentException('Invalid cloud id');
		}

		// Find the first character that is not allowed in user names
		$id = $this->stripShareLinkFragments($cloudId);
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

			// We accept slightly more chars when working with federationId than with a local userId.
			// We remove those eventual chars from the UserId before using
			// the IUserManager API to confirm its format.
			$this->validateUser($user, $remote);

			if (!empty($user) && !empty($remote)) {
				$remote = $this->ensureDefaultProtocol($remote);
				return new CloudId($id, $user, $remote, null);
			}
		}
		throw new \InvalidArgumentException('Invalid cloud id');
	}

	protected function validateUser(string $user, string $remote): void {
		// Check the ID for bad characters
		// Allowed are: "a-z", "A-Z", "0-9", spaces and "_.@-'" (Nextcloud)
		// Additional: "=" (oCIS)
		if (preg_match('/[^a-zA-Z0-9 _.@\-\'=]/', $user)) {
			throw new \InvalidArgumentException('Invalid characters');
		}

		// No empty user ID
		if (trim($user) === '') {
			throw new \InvalidArgumentException('Empty user');
		}

		// No whitespace at the beginning or at the end
		if (trim($user) !== $user) {
			throw new \InvalidArgumentException('User contains whitespace at the beginning or at the end');
		}

		// User ID only consists of 1 or 2 dots (directory traversal)
		if ($user === '.' || $user === '..') {
			throw new \InvalidArgumentException('User must not consist of dots only');
		}

		// User ID is too long
		if (strlen($user . '@' . $remote) > 255) {
			// TRANSLATORS User ID is too long
			throw new \InvalidArgumentException('Cloud id is too long');
		}
	}

	public function getDisplayNameFromContact(string $cloudId): ?string {
		$cachedName = $this->displayNameCache->get($cloudId);
		if ($cachedName !== null) {
			if ($cachedName === $cloudId) {
				return null;
			}
			return $cachedName;
		}

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
						// Warning, if user decides to make their full name local only,
						// no FN is found on federated servers
						if (isset($entry['FN'])) {
							$this->displayNameCache->set($cloudId, $entry['FN'], 15 * 60);
							return $entry['FN'];
						} else {
							$this->displayNameCache->set($cloudId, $cloudId, 15 * 60);
							return null;
						}
					}
				}
			}
		}
		$this->displayNameCache->set($cloudId, $cloudId, 15 * 60);
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
			$remote = rtrim($this->urlGenerator->getAbsoluteURL('/'), '/');
		}

		// note that for remote id's we don't strip the protocol for the remote we use to construct the CloudId
		// this way if a user has an explicit non-https cloud id this will be preserved
		// we do still use the version without protocol for looking up the display name
		$remote = $this->stripShareLinkFragments($remote);
		$host = $this->removeProtocolFromUrl($remote);
		$remote = $this->ensureDefaultProtocol($remote);

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
			$displayName = null;
		}

		// For the visible cloudID we only strip away https
		$id = $user . '@' . $this->removeProtocolFromUrl($remote, true);

		$data = [
			'id' => $id,
			'user' => $user,
			'remote' => $remote,
			'displayName' => $displayName,
		];
		$this->cache[$key] = $data;
		$this->memCache->set($key, $data, 15 * 60);
		return new CloudId($id, $user, $remote, $displayName);
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public function removeProtocolFromUrl(string $url, bool $httpsOnly = false): string {
		if (str_starts_with($url, 'https://')) {
			return substr($url, 8);
		}
		if (!$httpsOnly && str_starts_with($url, 'http://')) {
			return substr($url, 7);
		}

		return $url;
	}

	protected function ensureDefaultProtocol(string $remote): string {
		if (!str_contains($remote, '://')) {
			$remote = 'https://' . $remote;
		}

		return $remote;
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
	protected function stripShareLinkFragments(string $remote): string {
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
		foreach ($this->cloudIdResolvers as $resolver) {
			if ($resolver->isValidCloudId($cloudId)) {
				return true;
			}
		}

		return strpos($cloudId, '@') !== false;
	}

	public function createCloudId(string $id, string $user, string $remote, ?string $displayName = null): ICloudId {
		return new CloudId($id, $user, $remote, $displayName);
	}

	public function registerCloudIdResolver(ICloudIdResolver $resolver): void {
		array_unshift($this->cloudIdResolvers, $resolver);
	}

	public function unregisterCloudIdResolver(ICloudIdResolver $resolver): void {
		if (($key = array_search($resolver, $this->cloudIdResolvers)) !== false) {
			array_splice($this->cloudIdResolvers, $key, 1);
		}
	}
}
