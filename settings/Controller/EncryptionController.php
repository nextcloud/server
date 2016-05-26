<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OC\Settings\Controller;
use OC\Files\View;
use OCA\Encryption\Migration;
use OCP\IL10N;
use OCP\AppFramework\Controller;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IConfig;
use OC\DB\Connection;
use OCP\IUserManager;

/**
 * @package OC\Settings\Controller
 */
class EncryptionController extends Controller {

	/** @var \OCP\IL10N */
	private $l10n;

	/** @var  Connection */
	private $connection;

	/** @var IConfig */
	private $config;

	/** @var IUserManager */
	private $userManager;

	/** @var View */
	private $view;

	/** @var  ILogger */
	private $logger;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param \OCP\IL10N $l10n
	 * @param \OCP\IConfig $config
	 * @param \OC\DB\Connection $connection
	 * @param IUserManager $userManager
	 * @param View $view
	 * @param ILogger $logger
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config,
								Connection $connection,
								IUserManager $userManager,
								View $view,
								ILogger  $logger) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->connection = $connection;
		$this->view = $view;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	/**
	 * @param IConfig $config
	 * @param View $view
	 * @param Connection $connection
	 * @param ILogger $logger
	 * @return Migration
	 */
	protected function getMigration(IConfig $config,
								 View $view,
								 Connection $connection,
								 ILogger $logger) {
		return new Migration($config, $view, $connection, $logger);
	}

	/**
	 * start migration
	 *
	 * @return array
	 */
	public function startMigration() {
        // allow as long execution on the web server as possible
		set_time_limit(0);

		try {

			$migration = $this->getMigration($this->config, $this->view, $this->connection, $this->logger);
			$migration->reorganizeSystemFolderStructure();
			$migration->updateDB();

			foreach ($this->userManager->getBackends() as $backend) {
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					foreach ($users as $user) {
						$migration->reorganizeFolderStructureForUser($user);
					}
					$offset += $limit;
				} while (count($users) >= $limit);
			}

			$migration->finalCleanUp();

		} catch (\Exception $e) {
			return [
				'data' => [
					'message' => (string)$this->l10n->t('A problem occurred, please check your log files (Error: %s)', [$e->getMessage()]),
				],
				'status' => 'error',
			];
		}

		return [
			'data' => [
				'message' => (string) $this->l10n->t('Migration Completed'),
				],
			'status' => 'success',
		];
	}

}
