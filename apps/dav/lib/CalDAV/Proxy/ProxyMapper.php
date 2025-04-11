<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Proxy;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * Class ProxyMapper
 *
 * @package OCA\DAV\CalDAV\Proxy
 *
 * @template-extends QBMapper<Proxy>
 */
class ProxyMapper extends QBMapper {
	public const PERMISSION_READ = 1;
	public const PERMISSION_WRITE = 2;

	/**
	 * ProxyMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'dav_cal_proxy', Proxy::class);
	}

	/**
	 * @param string $proxyId The principal uri that can act as a proxy for the resulting calendars
	 *
	 * @return Proxy[]
	 */
	public function getProxiesFor(string $proxyId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('proxy_id', $qb->createNamedParameter($proxyId)));

		return $this->findEntities($qb);
	}

	/**
	 * @param string $ownerId The principal uri that has the resulting proxies for their calendars
	 *
	 * @return Proxy[]
	 */
	public function getProxiesOf(string $ownerId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('owner_id', $qb->createNamedParameter($ownerId)));

		return $this->findEntities($qb);
	}
}
