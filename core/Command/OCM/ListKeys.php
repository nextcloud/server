<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\OCM;

use OC\Core\Command\Base;
use OC\OCM\OCMSignatoryManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListKeys extends Base {
	public function __construct(
		private readonly OCMSignatoryManager $signatoryManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('ocm:keys:list')
			->setDescription('list Ed25519 keys used by OCM RFC 9421 HTTP Message Signatures');
		parent::configure();
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$keys = $this->signatoryManager->listEd25519Keys();
		$format = $input->getOption('output');
		if ($format === self::OUTPUT_FORMAT_JSON || $format === self::OUTPUT_FORMAT_JSON_PRETTY) {
			$output->writeln(json_encode($keys, $format === self::OUTPUT_FORMAT_JSON_PRETTY ? JSON_PRETTY_PRINT : 0));
			return self::SUCCESS;
		}

		if ($keys === []) {
			$output->writeln('<comment>No Ed25519 keys yet; one will be generated on first OCM request.</comment>');
			return self::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders(['Pool', 'Slot', 'Key ID']);
		foreach ($keys as $key) {
			$table->addRow([$key['poolId'], $key['slot'] ?? '-', $key['kid']]);
		}
		$table->render();
		return self::SUCCESS;
	}
}
