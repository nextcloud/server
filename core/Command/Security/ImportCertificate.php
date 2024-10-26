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

class ImportCertificate extends Base {
	public function __construct(
		protected ICertificateManager $certificateManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('security:certificates:import')
			->setDescription('import trusted certificate in PEM format')
			->addArgument(
				'path',
				InputArgument::REQUIRED,
				'path to the PEM certificate to import'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getArgument('path');

		if (!file_exists($path)) {
			$output->writeln('<error>Certificate not found, please provide a path accessible by the web server user</error>');
			return 1;
		}

		$certData = file_get_contents($path);
		$name = basename($path);

		$this->certificateManager->addCertificate($certData, $name);
		return 0;
	}
}
