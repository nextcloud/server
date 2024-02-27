<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace OCA\DAV\DAV\Sharing;

abstract class SharingService {
	protected string $resourceType = '';
	public function __construct(protected SharingMapper $mapper) {
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
