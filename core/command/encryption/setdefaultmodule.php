<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OC\Core\Command\Encryption;


use OCP\Encryption\IManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetDefaultModule extends Command {
	/** @var IManager */
	protected $encryptionManager;

	/**
	 * @param IManager $encryptionManager
	 */
	public function __construct(IManager $encryptionManager) {
		parent::__construct();
		$this->encryptionManager = $encryptionManager;
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:set-default-module')
			->setDescription('Set the encryption default module')
			->addArgument(
				'module',
				InputArgument::REQUIRED,
				'ID of the encryption module that should be used'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$moduleId = $input->getArgument('module');

		if ($moduleId === $this->encryptionManager->getDefaultEncryptionModuleId()) {
			$output->writeln('"' . $moduleId . '"" is already the default module');
		} else if ($this->encryptionManager->setDefaultEncryptionModule($moduleId)) {
			$output->writeln('<info>Set default module to "' . $moduleId . '"</info>');
		} else {
			$output->writeln('<error>The specified module "' . $moduleId . '" does not exist</error>');
		}
	}
}
