<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OCP\ICertificateManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCertificate extends Base {
	public function __construct(
		protected ICertificateManager $certificateManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('security:certificates:remove')
			->setDescription('remove trusted certificate')
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'the file name of the certificate to remove'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$this->certificateManager->removeCertificate($name);
		return 0;
	}
}
