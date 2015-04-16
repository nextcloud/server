<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Core\Command\Encryption;

use OC\Core\Command\Base;
use OC\Encryption\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListModules extends Base {
	/** @var Manager */
	protected $encryptionManager;

	/**
	 * @param Manager $encryptionManager
	 */
	public function __construct(Manager $encryptionManager) {
		parent::__construct();
		$this->encryptionManager = $encryptionManager;
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:list-modules')
			->setDescription('List all available encryption modules')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$encryptionModules = $this->encryptionManager->getEncryptionModules();
		$defaultEncryptionModuleId = '';
		try {
			$defaultEncryptionModule = $this->encryptionManager->getDefaultEncryptionModule();
			$defaultEncryptionModuleId = $defaultEncryptionModule->getId();
		} catch (\Exception $e) {}

		$encModules = array();
		foreach ($encryptionModules as $module) {
			$encModules[$module->getId()]['displayName'] = $module->getDisplayName();
			$encModules[$module->getId()]['default'] .= $module->getId() === $defaultEncryptionModuleId;
		}
		$this->writeModuleList($input, $output, $encModules);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param array $items
	 */
	protected function writeModuleList(InputInterface $input, OutputInterface $output, $items) {
		switch ($input->getOption('output')) {
			case 'plain':
				array_walk($items, function(&$item) {
					if (!$item['default']) {
						$item = $item['displayName'];
					} else {
						$item = $item['displayName'] . ' [default*]';
					}
				});
				// no break;

			default:
				parent::writeArrayInOutputFormat($input, $output, $items);
				break;
		}
	}
}
