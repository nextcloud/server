<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Encryption;

use OC\Core\Command\Base;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListModules extends Base {
	public function __construct(
		protected IManager $encryptionManager,
		protected IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:list-modules')
			->setDescription('List all available encryption modules')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$isMaintenanceModeEnabled = $this->config->getSystemValue('maintenance', false);
		if ($isMaintenanceModeEnabled) {
			$output->writeln('Maintenance mode must be disabled when listing modules');
			$output->writeln('in order to list the relevant encryption modules correctly.');
			return 1;
		}

		$encryptionModules = $this->encryptionManager->getEncryptionModules();
		$defaultEncryptionModuleId = $this->encryptionManager->getDefaultEncryptionModuleId();

		$encModules = [];
		foreach ($encryptionModules as $module) {
			$encModules[$module['id']]['displayName'] = $module['displayName'];
			$encModules[$module['id']]['default'] = $module['id'] === $defaultEncryptionModuleId;
		}
		$this->writeModuleList($input, $output, $encModules);
		return 0;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param array $items
	 */
	protected function writeModuleList(InputInterface $input, OutputInterface $output, $items) {
		if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN) {
			array_walk($items, function (&$item) {
				if (!$item['default']) {
					$item = $item['displayName'];
				} else {
					$item = $item['displayName'] . ' [default*]';
				}
			});
		}

		$this->writeArrayInOutputFormat($input, $output, $items);
	}
}
