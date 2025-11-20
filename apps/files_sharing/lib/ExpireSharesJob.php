<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use Exception;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Share\IProviderFactory;
use Psr\Log\LoggerInterface;

/**
 * Delete all shares that are expired
 */
class ExpireSharesJob extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private IManager $shareManager,
		private IProviderFactory $factory,
		private IDBConnection $db,
		private LoggerInterface $logger,
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
		 * Expire all shares
		 */
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'share_type')
			->from('share')
			->where(
				$qb->expr()->andX(
					$qb->expr()->lte('expiration', $qb->expr()->literal($now)),
					$qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY))
				)
			);

		$shares = $qb->executeQuery();
		while ($share = $shares->fetch()) {
			try {
				$provider = $this->factory->getProviderForType((int)$share['share_type'])->identifier(); // returns something like ocinternal, ocMailShare
				$id = $provider . ':' . $share['id'];
				$share = $this->shareManager->getShareById($id);
				$this->shareManager->deleteShare($share);
			} catch (ShareNotFound $e) {
				// Normally the share gets automatically expired on fetching it
				$this->logger->debug('Got share not found for share with id {share_id} of type {share_type}. Got {e}', [
					'share_id' => $share['id'],
					'share_type' => $share['share_type'],
					'error' => $e,
				]);
			} catch (Exception $e ) {
				$this->logger->error('Something unexpected happened while trying to expire a share with id {share_id} of type {share_type}. Got {e}', [
					'share_id' => $share['id'],
					'share_type' => $share['share_type'],
					'error' => $e,
				]);
			}
		}
		$shares->closeCursor();
	}
}
