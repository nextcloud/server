<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use function strlen;
use function substr;
use function urldecode;
use function urlencode;

class RepairDavShares implements IRepairStep {
	protected const GROUP_PRINCIPAL_PREFIX = 'principals/groups/';

	private bool $hintInvalidShares = false;

	public function __construct(
		private readonly IConfig $config,
		private readonly IDBConnection $dbc,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
	}

	public function getName(): string {
		return 'Repair DAV shares';
	}

	protected function repairUnencodedGroupShares(): bool {
		$qb = $this->dbc->getQueryBuilder();
		$qb->select(['id', 'principaluri'])
			->from('dav_shares')
			->where($qb->expr()->like('principaluri', $qb->createNamedParameter(self::GROUP_PRINCIPAL_PREFIX . '%')));

		$updateQuery = $this->dbc->getQueryBuilder();
		$updateQuery->update('dav_shares')
			->set('principaluri', $updateQuery->createParameter('updatedPrincipalUri'))
			->where($updateQuery->expr()->eq('id', $updateQuery->createParameter('shareId')));

		$statement = $qb->executeQuery();
		while ($share = $statement->fetch()) {
			$gid = substr($share['principaluri'], strlen(self::GROUP_PRINCIPAL_PREFIX));
			$decodedGid = urldecode($gid);
			$encodedGid = urlencode($gid);
			if ($gid === $encodedGid
				|| !$this->groupManager->groupExists($gid)
				|| ($gid !== $decodedGid && $this->groupManager->groupExists($decodedGid))
			) {
				$this->hintInvalidShares = $this->hintInvalidShares || $gid !== $encodedGid;
				continue;
			}

			// Repair when
			// + the group name needs encoding
			// + AND it is not encoded yet
			// + AND there are no ambivalent groups

			try {
				$fixedPrincipal = self::GROUP_PRINCIPAL_PREFIX . $encodedGid;
				$logParameters = [
					'app' => 'core',
					'id' => $share['id'],
					'old' => $share['principaluri'],
					'new' => $fixedPrincipal,
				];
				$updateQuery
					->setParameter('updatedPrincipalUri', $fixedPrincipal)
					->setParameter('shareId', $share['id'])
					->executeStatement();
				$this->logger->info('Repaired principal for dav share {id} from {old} to {new}', $logParameters);
			} catch (Exception $e) {
				$logParameters['message'] = $e->getMessage();
				$logParameters['exception'] = $e;
				$this->logger->info('Could not repair principal for dav share {id} from {old} to {new}: {message}', $logParameters);
			}
		}
		return true;
	}

	public function run(IOutput $output): void {
		$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0');
		if (version_compare($versionFromBeforeUpdate, '20.0.8', '<')
			&& $this->repairUnencodedGroupShares()
		) {
			$output->info('Repaired DAV group shares');
			if ($this->hintInvalidShares) {
				$output->info('Invalid shares might be left in the database, running "occ dav:remove-invalid-shares" can remove them.');
			}
		}
	}
}
