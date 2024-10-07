<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Julien Veyssier <julien-nc@posteo.net>
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
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
