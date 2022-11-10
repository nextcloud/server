<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RepairShareOwnership extends Command {
	private IDBConnection $dbConnection;
	private IUserManager $userManager;

	public function __construct(
		IDBConnection $dbConnection,
		IUserManager $userManager
	) {
		$this->dbConnection = $dbConnection;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:repair-share-owner')
			->setDescription('repair invalid share-owner entries in the database')
			->addOption('no-confirm', 'y', InputOption::VALUE_NONE, "Don't ask for confirmation before repairing the shares")
			->addArgument('user', InputArgument::OPTIONAL, "User to fix incoming shares for, if omitted all users will be fixed");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$noConfirm = $input->getOption('no-confirm');
		$userId = $input->getArgument('user');
		if ($userId) {
			$user = $this->userManager->get($userId);
			if (!$user) {
				$output->writeln("<error>user $userId not found</error>");
				return 1;
			}
			$shares = $this->getWrongShareOwnershipForUser($user);
		} else {
			$shares = $this->getWrongShareOwnership();
		}

		if ($shares) {
			$output->writeln("");
			$output->writeln("Found " . count($shares) . " shares with invalid share owner");
			foreach ($shares as $share) {
				/** @var array{shareId: int, fileTarget: string, initiator: string, receiver: string, owner: string, mountOwner: string} $share */
				$output->writeln(" - share ${share['shareId']} from \"${share['initiator']}\" to \"${share['receiver']}\" at \"${share['fileTarget']}\", owned by \"${share['owner']}\", that should be owned by \"${share['mountOwner']}\"");
			}
			$output->writeln("");

			if (!$noConfirm) {
				$helper = $this->getHelper('question');
				$question = new ConfirmationQuestion('Repair these shares? [y/N]', false);

				if (!$helper->ask($input, $output, $question)) {
					return 0;
				}
			}
			$output->writeln("Repairing " . count($shares) . " shares");
			$this->repairShares($shares);
		} else {
			$output->writeln("Found no shares with invalid share owner");
		}

		return 0;
	}

	/**
	 * @return array{shareId: int, fileTarget: string, initiator: string, receiver: string, owner: string, mountOwner: string}[]
	 * @throws \OCP\DB\Exception
	 */
	protected function getWrongShareOwnership(): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$brokenShares = $qb
			->select('s.id', 'm.user_id', 's.uid_owner', 's.uid_initiator', 's.share_with', 's.file_target')
			->from('share', 's')
			->join('s', 'filecache', 'f', $qb->expr()->eq('s.item_source', $qb->expr()->castColumn('f.fileid', IQueryBuilder::PARAM_STR)))
			->join('s', 'mounts', 'm', $qb->expr()->eq('f.storage', 'm.storage_id'))
			->where($qb->expr()->neq('m.user_id', 's.uid_owner'))
			->andWhere($qb->expr()->eq($qb->func()->concat($qb->expr()->literal('/'), 'm.user_id', $qb->expr()->literal('/')), 'm.mount_point'))
			->executeQuery()
			->fetchAll();

		$found = [];

		foreach ($brokenShares as $share) {
			$found[] = [
				'shareId' => (int) $share['id'],
				'fileTarget' => $share['file_target'],
				'initiator' => $share['uid_initiator'],
				'receiver' => $share['share_with'],
				'owner' => $share['uid_owner'],
				'mountOwner' => $share['user_id'],
			];
		}

		return $found;
	}

	/**
	 * @param IUser $user
	 * @return array{shareId: int, fileTarget: string, initiator: string, receiver: string, owner: string, mountOwner: string}[]
	 * @throws \OCP\DB\Exception
	 */
	protected function getWrongShareOwnershipForUser(IUser $user): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$brokenShares = $qb
			->select('s.id', 'm.user_id', 's.uid_owner', 's.uid_initiator', 's.share_with', 's.file_target')
			->from('share', 's')
			->join('s', 'filecache', 'f', $qb->expr()->eq('s.item_source', $qb->expr()->castColumn('f.fileid', IQueryBuilder::PARAM_STR)))
			->join('s', 'mounts', 'm', $qb->expr()->eq('f.storage', 'm.storage_id'))
			->where($qb->expr()->neq('m.user_id', 's.uid_owner'))
			->andWhere($qb->expr()->eq($qb->func()->concat($qb->expr()->literal('/'), 'm.user_id', $qb->expr()->literal('/')), 'm.mount_point'))
			->andWhere($qb->expr()->eq('s.share_with', $qb->createNamedParameter($user->getUID())))
			->executeQuery()
			->fetchAll();

		$found = [];

		foreach ($brokenShares as $share) {
			$found[] = [
				'shareId' => (int) $share['id'],
				'fileTarget' => $share['file_target'],
				'initiator' => $share['uid_initiator'],
				'receiver' => $share['share_with'],
				'owner' => $share['uid_owner'],
				'mountOwner' => $share['user_id'],
			];
		}

		return $found;
	}

	/**
	 * @param array{shareId: int, fileTarget: string, initiator: string, receiver: string, owner: string, mountOwner: string}[] $shares
	 * @return void
	 */
	protected function repairShares(array $shares) {
		$this->dbConnection->beginTransaction();

		$update = $this->dbConnection->getQueryBuilder();
		$update->update('share')
			->set('uid_owner', $update->createParameter('share_owner'))
			->set('uid_initiator', $update->createParameter('share_initiator'))
			->where($update->expr()->eq('id', $update->createParameter('share_id')));

		foreach ($shares as $share) {
			/** @var array{shareId: int, fileTarget: string, initiator: string, receiver: string, owner: string, mountOwner: string} $share */
			$update->setParameter('share_id', $share['shareId'], IQueryBuilder::PARAM_INT);
			$update->setParameter('share_owner', $share['mountOwner']);

			// if the broken owner is also the initiator it's safe to update them both, otherwise we don't touch the initiator
			if ($share['initiator'] === $share['owner']) {
				$update->setParameter('share_initiator', $share['mountOwner']);
			} else {
				$update->setParameter('share_initiator', $share['initiator']);
			}
			$update->executeStatement();
		}

		$this->dbConnection->commit();
	}
}
