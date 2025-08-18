<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Delete all shares that are expired
 */
class ExpireSharesJob extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private IManager $shareManager,
		private IDBConnection $db,
	) {
		parent::__construct($time);

		// Run once a day
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}


	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 */
	public function run($argument) {
		//Current time
		$now = new \DateTime();
		$now = $now->format('Y-m-d H:i:s');

		/*
		 * Expire file link shares only (for now)
		 */
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'share_type')
			->from('share')
			->where(
				$qb->expr()->andX(
					$qb->expr()->in('share_type', $qb->createNamedParameter([IShare::TYPE_LINK, IShare::TYPE_EMAIL], IQueryBuilder::PARAM_INT_ARRAY)),
					$qb->expr()->lte('expiration', $qb->expr()->literal($now)),
					$qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY))
				)
			);

		$shares = $qb->executeQuery();
		while ($share = $shares->fetch()) {
			if ((int)$share['share_type'] === IShare::TYPE_LINK) {
				$id = 'ocinternal';
			} elseif ((int)$share['share_type'] === IShare::TYPE_EMAIL) {
				$id = 'ocMailShare';
			}

			$id .= ':' . $share['id'];

			try {
				$share = $this->shareManager->getShareById($id);
				$this->shareManager->deleteShare($share);
			} catch (ShareNotFound $e) {
				// Normally the share gets automatically expired on fetching it
			}
		}
		$shares->closeCursor();
	}
}
