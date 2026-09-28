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

class UpdateLanguageCodes implements IRepairStep {
	/** @var IDBConnection */
	private $connection;

	/** @var IConfig */
	private $config;

	/**
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $connection,
		IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'Repair language codes';
	}

	/**
	 * {@inheritdoc}
	 */
	public function run(IOutput $output) {
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
