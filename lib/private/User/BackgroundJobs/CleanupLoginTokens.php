<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IDBConnection;

class CleanupLoginTokens extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly IDBConnection $connection,
		private readonly IConfig $config,
	) {
		parent::__construct($time);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
	}

	#[\Override]
	protected function run($argument): void {
		$rememberMeMaxAge = $this->config->getSystemValueInt('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete()
			->where($qb->expr()->eq('appid', $qb->expr()->literal('login_token')))
			->andWhere($qb->expr()->lt('configvalue', $qb->createNamedParameter(time() - $rememberMeMaxAge)))
			->executeStatement();
	}
}
