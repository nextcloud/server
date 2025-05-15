<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\DAV\Sharing;

use OCA\DAV\Connector\Sabre\Principal;
use OCP\AppFramework\Db\TTransactional;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

abstract class Backend {
	use TTransactional;
	public const ACCESS_OWNER = 1;

	public const ACCESS_READ_WRITE = 2;
	public const ACCESS_READ = 3;
	// 4 is already in use for public calendars
	public const ACCESS_UNSHARED = 5;

	private ICache $shareCache;

	public function __construct(
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private Principal $principalBackend,
		private ICacheFactory $cacheFactory,
		private SharingService $service,
		private LoggerInterface $logger,
	) {
		$this->shareCache = $this->cacheFactory->createInMemory();
	}

	/**
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $add
	 * @param list<string> $remove
	 */
	public function updateShares(IShareable $shareable, array $add, array $remove, array $oldShares = []): void {
		$this->shareCache->clear();
		foreach ($add as $element) {
			$principal = $this->principalBackend->findByUri($element['href'], '');
			if (empty($principal)) {
				continue;
			}

			// We need to validate manually because some principals are only virtual
			// i.e. Group principals
			$principalparts = explode('/', $principal, 3);
			if (count($principalparts) !== 3 || $principalparts[0] !== 'principals' || !in_array($principalparts[1], ['users', 'groups', 'circles'], true)) {
				// Invalid principal
				continue;
			}

			// Don't add share for owner
			if ($shareable->getOwner() !== null && strcasecmp($shareable->getOwner(), $principal) === 0) {
				continue;
			}

			$principalparts[2] = urldecode($principalparts[2]);
			if (($principalparts[1] === 'users' && !$this->userManager->userExists($principalparts[2])) ||
				($principalparts[1] === 'groups' && !$this->groupManager->groupExists($principalparts[2]))) {
				// User or group does not exist
				continue;
			}

			$access = Backend::ACCESS_READ;
			if (isset($element['readOnly'])) {
				$access = $element['readOnly'] ? Backend::ACCESS_READ : Backend::ACCESS_READ_WRITE;
			}

			$this->service->shareWith($shareable->getResourceId(), $principal, $access);
		}
		foreach ($remove as $element) {
			$principal = $this->principalBackend->findByUri($element, '');
			if (empty($principal)) {
				continue;
			}

			// Don't add unshare for owner
			if ($shareable->getOwner() !== null && strcasecmp($shareable->getOwner(), $principal) === 0) {
				continue;
			}

			// Delete any possible direct shares (since the frontend does not separate between them)
			$this->service->deleteShare($shareable->getResourceId(), $principal);

			// Check if a user has a groupshare that they're trying to free themselves from
			// If so we need to add a self::ACCESS_UNSHARED row
			if (!str_contains($principal, 'group')
				&& $this->service->hasGroupShare($oldShares)
			) {
				$this->service->unshare($shareable->getResourceId(), $principal);
			}
		}
	}

	public function deleteAllShares(int $resourceId): void {
		$this->shareCache->clear();
		$this->service->deleteAllShares($resourceId);
	}

	public function deleteAllSharesByUser(string $principaluri): void {
		$this->shareCache->clear();
		$this->service->deleteAllSharesByUser($principaluri);
	}

	/**
	 * Returns the list of people whom this resource is shared with.
	 *
	 * Every element in this array should have the following properties:
	 *   * href - Often a mailto: address
	 *   * commonName - Optional, for example a first + last name
	 *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
	 *   * readOnly - boolean
	 *
	 * @param int $resourceId
	 * @return list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>
	 */
	public function getShares(int $resourceId): array {
		$cached = $this->shareCache->get((string)$resourceId);
		if ($cached) {
			return $cached;
		}

		$rows = $this->service->getShares($resourceId);
		$shares = [];
		foreach ($rows as $row) {
			$p = $this->principalBackend->getPrincipalByPath($row['principaluri']);
			$shares[] = [
				'href' => "principal:{$row['principaluri']}",
				'commonName' => isset($p['{DAV:}displayname']) ? (string)$p['{DAV:}displayname'] : '',
				'status' => 1,
				'readOnly' => (int)$row['access'] === Backend::ACCESS_READ,
				'{http://owncloud.org/ns}principal' => (string)$row['principaluri'],
				'{http://owncloud.org/ns}group-share' => isset($p['uri']) && (str_starts_with($p['uri'], 'principals/groups') || str_starts_with($p['uri'], 'principals/circles'))
			];
		}
		$this->shareCache->set((string)$resourceId, $shares);
		return $shares;
	}

	public function preloadShares(array $resourceIds): void {
		$resourceIds = array_filter($resourceIds, function (int $resourceId) {
			return empty($this->shareCache->get((string)$resourceId));
		});
		if (empty($resourceIds)) {
			return;
		}

		$rows = $this->service->getSharesForIds($resourceIds);
		$sharesByResource = array_fill_keys($resourceIds, []);
		foreach ($rows as $row) {
			$resourceId = (int)$row['resourceid'];
			$p = $this->principalBackend->getPrincipalByPath($row['principaluri']);
			$sharesByResource[$resourceId][] = [
				'href' => "principal:{$row['principaluri']}",
				'commonName' => isset($p['{DAV:}displayname']) ? (string)$p['{DAV:}displayname'] : '',
				'status' => 1,
				'readOnly' => (int)$row['access'] === self::ACCESS_READ,
				'{http://owncloud.org/ns}principal' => (string)$row['principaluri'],
				'{http://owncloud.org/ns}group-share' => isset($p['uri']) && str_starts_with($p['uri'], 'principals/groups')
			];
			$this->shareCache->set((string)$resourceId, $sharesByResource[$resourceId]);
		}
	}

	/**
	 * For shared resources the sharee is set in the ACL of the resource
	 *
	 * @param int $resourceId
	 * @param list<array{privilege: string, principal: string, protected: bool}> $acl
	 * @param list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}> $shares
	 * @return list<array{principal: string, privilege: string, protected: bool}>
	 */
	public function applyShareAcl(array $shares, array $acl): array {
		foreach ($shares as $share) {
			$acl[] = [
				'privilege' => '{DAV:}read',
				'principal' => $share['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}principal'],
				'protected' => true,
			];
			if (!$share['readOnly']) {
				$acl[] = [
					'privilege' => '{DAV:}write',
					'principal' => $share['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}principal'],
					'protected' => true,
				];
			} elseif (in_array($this->service->getResourceType(), ['calendar','addressbook'])) {
				// Allow changing the properties of read only calendars,
				// so users can change the visibility.
				$acl[] = [
					'privilege' => '{DAV:}write-properties',
					'principal' => $share['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}principal'],
					'protected' => true,
				];
			}
		}
		return $acl;
	}
}
