<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\DAV\Sharing;

use OCP\IDBConnection;

class Backend {

	/** @var IDBConnection */
	private $db;

	const ACCESS_OWNER = 1;
	const ACCESS_READ_WRITE = 2;
	const ACCESS_READ = 3;

	/** @var string */
	private $resourceType;

	/**
	 * CardDavBackend constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db, $resourceType) {
		$this->db = $db;
		$this->resourceType = $resourceType;
	}

	/**
	 * @param IShareable $shareable
	 * @param string[] $add
	 * @param string[] $remove
	 */
	public function updateShares($shareable, $add, $remove) {
		foreach($add as $element) {
			$this->shareWith($shareable, $element);
		}
		foreach($remove as $element) {
			$this->unshare($shareable->getResourceId(), $element);
		}
	}

	/**
	 * @param IShareable $shareable
	 * @param string $element
	 */
	private function shareWith($shareable, $element) {
		$user = $element['href'];
		$parts = explode(':', $user, 2);
		if ($parts[0] !== 'principal') {
			return;
		}

		// remove the share if it already exists
		$this->unshare($shareable->getResourceId(), $element['href']);
		$access = self::ACCESS_READ;
		if (isset($element['readOnly'])) {
			$access = $element['readOnly'] ? self::ACCESS_READ : self::ACCESS_READ_WRITE;
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('dav_shares')
			->values([
				'principaluri' => $query->createNamedParameter($parts[1]),
				'type' => $query->createNamedParameter($this->resourceType),
				'access' => $query->createNamedParameter($access),
				'resourceid' => $query->createNamedParameter($shareable->getResourceId())
			]);
		$query->execute();
	}

	/**
	 * @param int $resourceId
	 * @param string $element
	 */
	private function unshare($resourceId, $element) {
		$parts = explode(':', $element, 2);
		if ($parts[0] !== 'principal') {
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->delete('dav_shares')
			->where($query->expr()->eq('resourceid', $query->createNamedParameter($resourceId)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($this->resourceType)))
			->andWhere($query->expr()->eq('principaluri', $query->createNamedParameter($parts[1])))
		;
		$query->execute();
	}

	/**
	 * Returns the list of people whom this resource is shared with.
	 *
	 * Every element in this array should have the following properties:
	 *   * href - Often a mailto: address
	 *   * commonName - Optional, for example a first + last name
	 *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
	 *   * readOnly - boolean
	 *   * summary - Optional, a description for the share
	 *
	 * @return array
	 */
	public function getShares($resourceId) {
		$query = $this->db->getQueryBuilder();
		$result = $query->select(['principaluri', 'access'])
			->from('dav_shares')
			->where($query->expr()->eq('resourceid', $query->createNamedParameter($resourceId)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($this->resourceType)))
			->execute();

		$shares = [];
		while($row = $result->fetch()) {
			$shares[]= [
				'href' => "principal:${row['principaluri']}",
//				'commonName' => isset($p['{DAV:}displayname']) ? $p['{DAV:}displayname'] : '',
				'status' => 1,
				'readOnly' => ($row['access'] === self::ACCESS_READ),
				'{'.\OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD.'}principal' => $row['principaluri']
			];
		}

		return $shares;
	}

	/**
	 * For shared resources the sharee is set in the ACL of the resource
	 *
	 * @param int $resourceId
	 * @param array $acl
	 * @return array
	 */
	public function applyShareAcl($resourceId, $acl) {

		$shares = $this->getShares($resourceId);
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
			}
		}
		return $acl;
	}
}
