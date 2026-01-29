<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Repair;

use OC\Files\SetupManager;
use OCA\Files_Sharing\ShareTargetValidator;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Mount\IMountManager;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;

class CleanupShareTarget implements IRepairStep {
	/** we only care about shares with a user target,
	 *  since the underling group/deck/talk share doesn't get moved
	 */
	private const USER_SHARE_TYPES = [
		IShare::TYPE_USER,
		IShare::TYPE_USERGROUP,
		IShare::TYPE_DECK_USER,
		11 // TYPE_USERROOM
	];

	public function __construct(
		private readonly IDBConnection $connection,
		private readonly IManager $shareManager,
		private readonly IProviderFactory $shareProviderFactory,
		private readonly ShareTargetValidator $shareTargetValidator,
		private readonly IUserManager $userManager,
		private readonly SetupManager $setupManager,
		private readonly IMountManager $mountManager,
	) {
	}

	#[\Override]
	public function getName() {
		return 'Cleanup share names with false conflicts';
	}

	#[\Override]
	public function run(IOutput $output) {
		$count = $this->countProblemShares();
		if ($count === 0) {
			return;
		}
		$output->startProgress($count);

		$lastUser = '';
		$userMounts = [];

		foreach ($this->getProblemShares() as $shareInfo) {
			$recipient = $this->userManager->getExistingUser($shareInfo['share_with']);
			$share = $this->shareProviderFactory
				->getProviderForType((int)$shareInfo['share_type'])
				->getShareById($shareInfo['id'], $recipient->getUID());

			// since we ordered the share by user, we can reuse the last data until we get to the next user
			if ($lastUser !== $recipient->getUID()) {
				$lastUser = $recipient->getUID();

				$this->setupManager->tearDown();
				$this->setupManager->setupForUser($recipient);
				$userMounts = $this->mountManager->getAll();
			}

			$oldTarget = $share->getTarget();
			$newTarget = $this->cleanTarget($oldTarget);
			$share->setTarget($newTarget);
			$this->shareManager->moveShare($share, $recipient->getUID());

			$this->shareTargetValidator->verifyMountPoint(
				$recipient,
				$share,
				$userMounts,
				[$share],
			);

			$oldMountPoint = "/{$recipient->getUID()}/files$oldTarget/";
			$newMountPoint = "/{$recipient->getUID()}/files$newTarget/";
			$userMounts[$newMountPoint] = $userMounts[$oldMountPoint];
			unset($userMounts[$oldMountPoint]);

			$output->advance();
		}
		$output->finishProgress();
		$output->info("Fixed $count shares");
	}

	private function countProblemShares(): int {
		$query = $this->connection->getQueryBuilder();
		$query->select($query->func()->count('id'))
			->from('share')
			->where($query->expr()->like('file_target', $query->createNamedParameter('% (_) (_)%')))
			->andWhere($query->expr()->in('share_type', $query->createNamedParameter(self::USER_SHARE_TYPES, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY));
		return (int)$query->executeQuery()->fetchOne();
	}

	/**
	 * @return \Traversable<array{id: string, share_type: string, share_with: string}>
	 */
	private function getProblemShares(): \Traversable {
		$query = $this->connection->getQueryBuilder();
		$query->select('id', 'share_type', 'share_with')
			->from('share')
			->where($query->expr()->like('file_target', $query->createNamedParameter('% (_) (_)%')))
			->andWhere($query->expr()->in('share_type', $query->createNamedParameter(self::USER_SHARE_TYPES, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY))
			->orderBy('share_with')
			->addOrderBy('id');
		$result = $query->executeQuery();
		/** @var \Traversable<array{id: string, share_type: string, share_with: string}> $rows */
		$rows = $result->iterateAssociative();
		return $rows;
	}

	private function cleanTarget(string $target): string {
		return preg_replace('/( \([2-9]\)){2,}/', '', $target);
	}
}
