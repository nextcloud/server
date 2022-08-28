<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_LDAP\Jobs;

use OCA\User_LDAP\Service\UpdateGroupsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class UpdateGroups extends TimedJob {
	public function __construct(
		private UpdateGroupsService $service,
		private LoggerInterface $logger,
		IConfig $config,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->interval = (int)$config->getAppValue('user_ldap', 'bgjRefreshInterval', '3600');
	}

	/**
	 * @param mixed $argument
	 * @throws Exception
	 */
	public function run($argument): void {
		$this->logger->debug('Run background job "updateGroups"');
		$this->service->updateGroups();
	}
}
