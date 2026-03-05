<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\DAV\Sharing;

use OCA\DAV\CalDAV\Federation\FederationSharingService;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
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
		private RemoteUserPrincipalBackend $remoteUserPrincipalBackend,
		private ICacheFactory $cacheFactory,
		private SharingService $service,
		// TODO: Make `FederationSharingService` abstract once we support federated address book
		//       sharing. The abstract sharing backend should not take a service scoped to calendars
		//       by default.
		private FederationSharingService $federationSharingService,
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
			// Hacky code below ... shouldn't we check the whole (principal) root collection instead?
			$principal = $this->principalBackend->findByUri($element['href'], '')
				?? $this->remoteUserPrincipalBackend->findByUri($element['href'], '');
			if (empty($principal)) {
				continue;
			}

			// We need to validate manually because some principals are only virtual
			// i.e. Group principals
			$principalparts = explode('/', $principal, 3);
			if (count($principalparts) !== 3 || $principalparts[0] !== 'principals' || !in_array($principalparts[1], ['users', 'groups', 'circles', 'remote-users'], true)) {
				// Invalid principal
				continue;
			}

			// Don't add share for owner
			if ($shareable->getOwner() !== null && strcasecmp($shareable->getOwner(), $principal) === 0) {
				continue;
			}

			$principalparts[2] = urldecode($principalparts[2]);
			if (($principalparts[1] === 'users' && !$this->userManager->userExists($principalparts[2]))
				|| ($principalparts[1] === 'groups' && !$this->groupManager->groupExists($principalparts[2]))) {
				// User or group does not exist
				continue;
			}

			$access = Backend::ACCESS_READ;
			if (isset($element['readOnly'])) {
				$access = $element['readOnly'] ? Backend::ACCESS_READ : Backend::ACCESS_READ_WRITE;
			}

			if ($principalparts[1] === 'remote-users') {
				$this->federationSharingService->shareWith($shareable, $principal, $access);
			} else {
				$this->service->shareWith($shareable->getResourceId(), $principal, $access);
			}
		}
		foreach ($remove as $element) {
			// Hacky code below ... shouldn't we check the whole (principal) root collection instead?
			$principal = $this->principalBackend->findByUri($element, '')
				?? $this->remoteUserPrincipalBackend->findByUri($element, '');
			if (empty($principal)) {
				continue;
			}

			// Don't add unshare for owner
			if ($shareable->getOwner() !== null && strcasecmp($shareable->getOwner(), $principal) === 0) {
				continue;
			}

			// Delete any possible direct shares (since the frontend does not separate between them)
			$this->service->deleteShare($shareable->getResourceId(), $principal);
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
		/** @var list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>|null $cached */
		$cached = $this->shareCache->get((string)$resourceId);
		if (is_array($cached)) {
			return $cached;
		}

		$rows = $this->service->getShares($resourceId);
		$shares = [];
		foreach ($rows as $row) {
			$p = $this->getPrincipalByPath($row['principaluri'], [
				'uri',
				'{DAV:}displayname',
			]);
			$shares[] = [
				'href' => "principal:{$row['principaluri']}",
				'commonName' => isset($p['{DAV:}displayname']) ? (string)$p['{DAV:}displayname'] : '',
				'status' => 1,
				'readOnly' => (int)$row['access'] === Backend::ACCESS_READ,
				'{http://owncloud.org/ns}principal' => (string)$row['principaluri'],
				'{http://owncloud.org/ns}group-share' => isset($p['uri']) && (str_starts_with($p['uri'], 'principals/groups') || str_starts_with($p['uri'], 'principals/circles')),
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
			$p = $this->getPrincipalByPath($row['principaluri'], [
				'uri',
				'{DAV:}displayname',
			]);
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

		// Also remember resources with no shares to prevent superfluous (empty) queries later on
		foreach ($resourceIds as $resourceId) {
			$hasShares = false;
			foreach ($rows as $row) {
				if ((int)$row['resourceid'] === $resourceId) {
					$hasShares = true;
					break;
				}
			}

			if ($hasShares) {
				continue;
			}

			$this->shareCache->set((string)$resourceId, []);
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

	public function unshare(IShareable $shareable, string $principalUri): bool {
		$this->shareCache->clear();

		$principal = $this->principalBackend->findByUri($principalUri, '');
		if (empty($principal)) {
			return false;
		}

		if ($shareable->getOwner() === $principal) {
			return false;
		}

		// Delete any possible direct shares (since the frontend does not separate between them)
		$this->service->deleteShare($shareable->getResourceId(), $principal);

		$needsUnshare = $this->hasAccessByGroupOrCirclesMembership(
			$shareable->getResourceId(),
			$principal
		);

		if ($needsUnshare) {
			$this->service->unshare($shareable->getResourceId(), $principal);
		}

		return true;
	}

	private function hasAccessByGroupOrCirclesMembership(int $resourceId, string $principal) {
		$memberships = array_merge(
			$this->principalBackend->getGroupMembership($principal, true),
			$this->principalBackend->getCircleMembership($principal)
		);

		$shares = array_column(
			$this->service->getShares($resourceId),
			'principaluri'
		);

		return count(array_intersect($memberships, $shares)) > 0;
	}

	public function getSharesByShareePrincipal(string $principal): array {
		return $this->service->getSharesByPrincipals([$principal]);
	}

	/**
	 * @param string[]|null $propertyFilter A list of properties to be retrieved or all if null. Is not guaranteed to always be applied and might overfetch.
	 */
	private function getPrincipalByPath(string $principalUri, ?array $propertyFilter = null): ?array {
		// Hacky code below ... shouldn't we check the whole (principal) root collection instead?
		if (str_starts_with($principalUri, RemoteUserPrincipalBackend::PRINCIPAL_PREFIX)) {
			return $this->remoteUserPrincipalBackend->getPrincipalByPath($principalUri);
		}

		return $this->principalBackend->getPrincipalPropertiesByPath($principalUri, $propertyFilter);
	}
}
