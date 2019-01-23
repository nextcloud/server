<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\SyncService;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncSystemAddressBook extends Command {

	/** @var SyncService */
	private $syncService;

	/** @var IConfig */
	private $config;

	/**
	 * @param SyncService $syncService
	 * @param IConfig $config
	 */
	function __construct(SyncService $syncService, IConfig $config) {
		parent::__construct();
		$this->syncService = $syncService;
		$this->config = $config;
	}

	protected function configure() {
		$this
			->setName('dav:sync-system-addressbook')
			->setDescription('Synchronizes users to the system addressbook');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($this->config->getAppValue('dav', 'syncSystemAddressbook', 'yes') === 'yes') {
			$output->writeln('Syncing users ...');
			$progress = new ProgressBar($output);
			$progress->start();
			$this->syncService->syncInstance(function() use ($progress) {
				$progress->advance();
			});

			$progress->finish();
			$output->writeln('');
		} else {
			$this->syncService->purgeSystemAddressBook();
			$output->writeln('Syncing system addressbook is disabled. Addressbook has been removed');
		}
	}
}
