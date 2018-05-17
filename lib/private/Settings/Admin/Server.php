<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Settings\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\Lock\DBLockingProvider;
use OC\Lock\NoopLockingProvider;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Lock\ILockingProvider;
use OCP\Settings\ISettings;

class Server implements ISettings {
	/** @var IDBConnection|Connection */
	private $db;
	/** @var IRequest */
	private $request;
	/** @var IConfig */
	private $config;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IL10N */
	private $l;

	/**
	 * @param IDBConnection $db
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ILockingProvider $lockingProvider
	 * @param IL10N $l
	 */
	public function __construct(IDBConnection $db,
								IRequest $request,
								IConfig $config,
								ILockingProvider $lockingProvider,
								IL10N $l) {
		$this->db = $db;
		$this->request = $request;
		$this->config = $config;
		$this->lockingProvider = $lockingProvider;
		$this->l = $l;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			// Background jobs
			'backgroundjobs_mode' => $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax'),
			'lastcron'            => $this->config->getAppValue('core', 'lastcron', false),
			'cronErrors'		  => $this->config->getAppValue('core', 'cronErrors'),
			'cli_based_cron_possible' => function_exists('posix_getpwuid'),
			'cli_based_cron_user' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner(\OC::$configDir . 'config.php'))['name'] : '',
		];

		return new TemplateResponse('settings', 'settings/admin/server', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'server';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 0;
	}
}
