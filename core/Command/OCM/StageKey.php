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

class StageKey extends Base {
	public function __construct(
		private readonly OCMSignatoryManager $signatoryManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('ocm:keys:stage')
			->setDescription('generate a new JWKS key and advertise it via JWKS without using it for signing yet');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$signatory = $this->signatoryManager->stageJwksKey();
		} catch (\RuntimeException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
		$output->writeln('Staged new JWKS key: <info>' . $signatory->getKeyId() . '</info>');
		$output->writeln('Wait for federated peers to refresh their JWKS cache before activating.');
		return self::SUCCESS;
	}
}
