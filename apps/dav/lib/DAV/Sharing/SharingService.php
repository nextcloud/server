<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\DAV\Sharing;

abstract class SharingService {
	protected string $resourceType = '';
	public function __construct(
		protected SharingMapper $mapper,
	) {
	}

	public function getResourceType(): string {
		return $this->resourceType;
	}
	public function shareWith(int $resourceId, string $principal, int $access): void {
		// remove the share if it already exists
		$this->mapper->deleteShare($resourceId, $this->getResourceType(), $principal);
		$this->mapper->share($resourceId, $this->getResourceType(), $access, $principal);
	}

	public function unshare(int $resourceId, string $principal): void {
		$this->mapper->unshare($resourceId, $this->getResourceType(), $principal);
	}

	public function deleteShare(int $resourceId, string $principal): void {
		$this->mapper->deleteShare($resourceId, $this->getResourceType(), $principal);
	}

	public function deleteAllShares(int $resourceId): void {
		$this->mapper->deleteAllShares($resourceId, $this->getResourceType());
	}

	public function deleteAllSharesByUser(string $principaluri): void {
		$this->mapper->deleteAllSharesByUser($principaluri, $this->getResourceType());
	}

	public function getShares(int $resourceId): array {
		return $this->mapper->getSharesForId($resourceId, $this->getResourceType());
	}

	public function getUnshares(int $resourceId): array {
		return $this->mapper->getUnsharesForId($resourceId, $this->getResourceType());
	}

	public function getSharesForIds(array $resourceIds): array {
		return $this->mapper->getSharesForIds($resourceIds, $this->getResourceType());
	}

	/**
	 * @param array $oldShares
	 * @return bool
	 */
	public function hasGroupShare(array $oldShares): bool {
		return !empty(array_filter($oldShares, function (array $share) {
			return $share['{http://owncloud.org/ns}group-share'] === true;
		}));
	}
}
