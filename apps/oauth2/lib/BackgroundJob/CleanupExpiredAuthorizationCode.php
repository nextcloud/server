<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\OAuth2\BackgroundJob;

use OCA\OAuth2\Db\AccessTokenMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class CleanupExpiredAuthorizationCode extends TimedJob {

	public function __construct(
		ITimeFactory $timeFactory,
		private AccessTokenMapper $accessTokenMapper,
		private LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		// 30 days
		$this->setInterval(60 * 60 * 24 * 30);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @param mixed $argument
	 * @inheritDoc
	 */
	protected function run($argument): void {
		try {
			$this->accessTokenMapper->cleanupExpiredAuthorizationCode();
		} catch (Exception $e) {
			$this->logger->warning('Failed to cleanup tokens with expired authorization code', ['exception' => $e]);
		}
	}
}
