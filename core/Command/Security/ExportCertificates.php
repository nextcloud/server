<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
declare(strict_types=1);

namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OCP\ICertificateManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCertificates extends Base {
	public function __construct(
		protected ICertificateManager $certificateManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('security:certificates:export')
			->setDescription('export the certificate bundle');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$bundlePath = $this->certificateManager->getAbsoluteBundlePath();
		$bundle = file_get_contents($bundlePath);
		$output->writeln($bundle);
		return 0;
	}
}
