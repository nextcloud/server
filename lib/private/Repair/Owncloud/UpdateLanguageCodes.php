<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Override;

class UpdateLanguageCodes implements IRepairStep {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly IConfig $config,
	) {
	}

	#[Override]
	public function getName(): string {
		return 'Repair language codes';
	}

	#[Override]
	public function run(IOutput $output): void {
		$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0');

		if (version_compare($versionFromBeforeUpdate, '12.0.0.13', '>')) {
			return;
		}

		$languages = [
			'bg_BG' => 'bg',
			'cs_CZ' => 'cs',
			'fi_FI' => 'fi',
			'hu_HU' => 'hu',
			'nb_NO' => 'nb',
			'sk_SK' => 'sk',
			'th_TH' => 'th',
		];

		foreach ($languages as $oldCode => $newCode) {
			$qb = $this->connection->getQueryBuilder();

			$affectedRows = $qb->update('preferences')
				->set('configvalue', $qb->createNamedParameter($newCode))
				->where($qb->expr()->eq('appid', $qb->createNamedParameter('core')))
				->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('lang')))
				->andWhere($qb->expr()->eq('configvalue', $qb->createNamedParameter($oldCode), IQueryBuilder::PARAM_STR))
				->executeStatement();

			$output->info('Changed ' . $affectedRows . ' setting(s) from "' . $oldCode . '" to "' . $newCode . '" in preferences table.');
		}
	}
}
