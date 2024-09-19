<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Command;

use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\SetupCheck\ISetupCheckManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupChecks extends Base {
	public function __construct(
		private ISetupCheckManager $setupCheckManager,
		private IRichTextFormatter $richTextFormatter,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('setupchecks')
			->setDescription('Run setup checks and output the results')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$results = $this->setupCheckManager->runAll();
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$this->writeArrayInOutputFormat($input, $output, $results);
				break;
			default:
				foreach ($results as $category => $checks) {
					$output->writeln("\t{$category}:");
					foreach ($checks as $check) {
						$styleTag = match ($check->getSeverity()) {
							'success' => 'info',
							'error' => 'error',
							'warning' => 'comment',
							default => null,
						};
						$emoji = match ($check->getSeverity()) {
							'success' => '✓',
							'error' => '✗',
							'warning' => '⚠',
							default => 'ℹ',
						};
						$verbosity = ($check->getSeverity() === 'error' ? OutputInterface::VERBOSITY_QUIET : OutputInterface::VERBOSITY_NORMAL);
						$description = $check->getDescription();
						$descriptionParameters = $check->getDescriptionParameters();
						if ($description !== null && $descriptionParameters !== null) {
							$description = $this->richTextFormatter->richToParsed($description, $descriptionParameters);
						}
						$output->writeln(
							"\t\t" .
							($styleTag !== null ? "<{$styleTag}>" : '') .
							"{$emoji} " .
							($check->getName() ?? $check::class) .
							($description !== null ? ': ' . $description : '') .
							($styleTag !== null ? "</{$styleTag}>" : ''),
							$verbosity
						);
					}
				}
		}
		foreach ($results as $category => $checks) {
			foreach ($checks as $check) {
				if ($check->getSeverity() !== 'success') {
					return self::FAILURE;
				}
			}
		}
		return self::SUCCESS;
	}
}
