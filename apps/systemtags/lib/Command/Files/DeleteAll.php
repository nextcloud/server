<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SystemTags\Command\Files;

use OC\Core\Command\Info\FileUtils;
use OCP\SystemTag\ISystemTagObjectMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteAll extends Command {

	public function __construct(
		private FileUtils $fileUtils,
		private ISystemTagObjectMapper $systemTagObjectMapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('tag:files:delete-all')
			->setDescription('Delete all system-tags from a file or folder')
			->addArgument('target', InputArgument::REQUIRED, 'file id or path');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$targetInput = $input->getArgument('target');
		$targetNode = $this->fileUtils->getNode($targetInput);

		if (! $targetNode) {
			$output->writeln("<error>file $targetInput not found</error>");
			return 1;
		}

		$tags = $this->systemTagObjectMapper->getTagIdsForObjects([$targetNode->getId()], 'files');
		$this->systemTagObjectMapper->unassignTags((string)$targetNode->getId(), 'files', $tags[$targetNode->getId()]);
		$output->writeln('<info>all tags removed.</info>');

		return 0;
	}
}
