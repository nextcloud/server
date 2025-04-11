<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command;

use OCP\Defaults;
use OCP\IConfig;
use OCP\ServerVersion;
use OCP\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Status extends Base {
	public function __construct(
		private IConfig $config,
		private Defaults $themingDefaults,
		private ServerVersion $serverVersion,
	) {
		parent::__construct('status');
	}

	protected function configure() {
		parent::configure();

		$this
			->setDescription('show some status information')
			->addOption(
				'exit-code',
				'e',
				InputOption::VALUE_NONE,
				'exit with 0 if running in normal mode, 1 when in maintenance mode, 2 when `./occ upgrade` is needed. Does not write any output to STDOUT.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$maintenanceMode = $this->config->getSystemValueBool('maintenance', false);
		$needUpgrade = Util::needUpgrade();
		$values = [
			'installed' => $this->config->getSystemValueBool('installed', false),
			'version' => implode('.', $this->serverVersion->getVersion()),
			'versionstring' => $this->serverVersion->getVersionString(),
			'edition' => '',
			'maintenance' => $maintenanceMode,
			'needsDbUpgrade' => $needUpgrade,
			'productname' => $this->themingDefaults->getProductName(),
			'extendedSupport' => Util::hasExtendedSupport()
		];

		if ($input->getOption('verbose') || !$input->getOption('exit-code')) {
			$this->writeArrayInOutputFormat($input, $output, $values);
		}

		if ($input->getOption('exit-code')) {
			if ($maintenanceMode === true) {
				return 1;
			}
			if ($needUpgrade === true) {
				return 2;
			}
		}
		return 0;
	}
}
