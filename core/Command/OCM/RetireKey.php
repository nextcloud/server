<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\OCM;

use OC\Core\Command\Base;
use OC\OCM\OCMSignatoryManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetireKey extends Base {
	public function __construct(
		private readonly OCMSignatoryManager $signatoryManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('ocm:keys:retire')
			->setDescription('delete the retiring Ed25519 key; signatures that referenced its kid can no longer be verified');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->signatoryManager->retireEd25519Key();
		} catch (\RuntimeException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}
		$output->writeln('<info>Retiring key deleted.</info>');
		return 0;
	}
}
