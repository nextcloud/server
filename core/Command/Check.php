<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command;

use OC\SystemConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Base {
	public function __construct(
		private SystemConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('check')
			->setDescription('check dependencies of the server environment')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$errors = \OC_Util::checkServer($this->config);
		if (!empty($errors)) {
			$errors = array_map(function ($item) {
				return (string)$item['error'];
			}, $errors);

			$this->writeArrayInOutputFormat($input, $output, $errors);
			return 1;
		}
		return 0;
	}
}
