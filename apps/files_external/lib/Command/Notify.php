<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\Files\Storage\INotifyStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Notify extends Base {
	/** @var GlobalStoragesService */
	private $globalService;
	/** @var IDBConnection */
	private $connection;
	/** @var \OCP\DB\QueryBuilder\IQueryBuilder */
	private $updateQuery;

	function __construct(GlobalStoragesService $globalService, IDBConnection $connection) {
		parent::__construct();
		$this->globalService = $globalService;
		$this->connection = $connection;
		// the query builder doesn't really like subqueries with parameters
		$this->updateQuery = $this->connection->prepare(
			'UPDATE *PREFIX*filecache SET size = -1
			WHERE `path` = ?
			AND `storage` IN (SELECT storage_id FROM *PREFIX*mounts WHERE mount_id = ?)'
		);
	}

	protected function configure() {
		$this
			->setName('files_external:notify')
			->setDescription('Listen for active update notifications for a configured external mount')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'the mount id of the mount to listen to'
			)->addOption(
				'user',
				'u',
				InputOption::VALUE_REQUIRED,
				'The username for the remote mount (required only for some mount configuration that don\'t store credentials)'
			)->addOption(
				'password',
				'p',
				InputOption::VALUE_REQUIRED,
				'The password for the remote mount (required only for some mount configuration that don\'t store credentials)'
			)->addOption(
				'path',
				null,
				InputOption::VALUE_REQUIRED,
				'The directory in the storage to listen for updates in',
				'/'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$mount = $this->globalService->getStorage($input->getArgument('mount_id'));
		if (is_null($mount)) {
			$output->writeln('<error>Mount not found</error>');
			return 1;
		}
		$noAuth = false;
		try {
			$authBackend = $mount->getAuthMechanism();
			$authBackend->manipulateStorageConfig($mount);
		} catch (InsufficientDataForMeaningfulAnswerException $e) {
			$noAuth = true;
		} catch (StorageNotAvailableException $e) {
			$noAuth = true;
		}

		if ($input->getOption('user')) {
			$mount->setBackendOption('user', $input->getOption('user'));
		}
		if ($input->getOption('password')) {
			$mount->setBackendOption('password', $input->getOption('password'));
		}

		try {
			$storage = $this->createStorage($mount);
		} catch (\Exception $e) {
			$output->writeln('<error>Error while trying to create storage</error>');
			if ($noAuth) {
				$output->writeln('<error>Username and/or password required</error>');
			}
			return 1;
		}
		if (!$storage instanceof INotifyStorage) {
			$output->writeln('<error>Mount of type "' . $mount->getBackend()->getText() . '" does not support active update notifications</error>');
			return 1;
		}

		$verbose = $input->getOption('verbose');

		$path = trim($input->getOption('path'), '/');
		$storage->listen($path, function ($type, $path, $renameTarget) use ($mount, $verbose, $output) {
			if ($verbose) {
				$this->logUpdate($type, $path, $renameTarget, $output);
			}
			if ($type == INotifyStorage::NOTIFY_RENAMED) {
				$this->markParentAsOutdated($mount->getId(), $renameTarget);
			}
			$this->markParentAsOutdated($mount->getId(), $path);
		});
	}

	private function createStorage(StorageConfig $mount) {
		$class = $mount->getBackend()->getStorageClass();
		return new $class($mount->getBackendOptions());
	}

	private function markParentAsOutdated($mountId, $path) {
		$parent = dirname($path);
		if ($parent === '.') {
			$parent = '';
		}
		$this->updateQuery->execute([$parent, $mountId]);
	}

	private function logUpdate($type, $path, $renameTarget, OutputInterface $output) {
		switch ($type) {
			case INotifyStorage::NOTIFY_ADDED:
				$text = 'added';
				break;
			case INotifyStorage::NOTIFY_MODIFIED:
				$text = 'modified';
				break;
			case INotifyStorage::NOTIFY_REMOVED:
				$text = 'removed';
				break;
			case INotifyStorage::NOTIFY_RENAMED:
				$text = 'renamed';
				break;
			default:
				return;
		}

		$text .= ' ' . $path;
		if ($type === INotifyStorage::NOTIFY_RENAMED) {
			$text .= ' to ' . $renameTarget;
		}

		$output->writeln($text);
	}
}
