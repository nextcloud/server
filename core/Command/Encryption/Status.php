<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Encryption;

use OC\Core\Command\Base;
use OCP\Encryption\IManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Status extends Base {
	public function __construct(
		protected IManager $encryptionManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:status')
			->setDescription('Lists the current status of encryption')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->writeArrayInOutputFormat($input, $output, [
			'enabled' => $this->encryptionManager->isEnabled(),
			'defaultModule' => $this->encryptionManager->getDefaultEncryptionModuleId(),
		]);
		return 0;
	}
}
