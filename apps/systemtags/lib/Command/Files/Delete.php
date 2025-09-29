<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SystemTags\Command\Files;

use OC\Core\Command\Info\FileUtils;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Command {

	public function __construct(
		private FileUtils $fileUtils,
		private ISystemTagManager $systemTagManager,
		private ISystemTagObjectMapper $systemTagObjectMapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('tag:files:delete')
			->setDescription('Delete a system-tag from a file or folder')
			->addArgument('target', InputArgument::REQUIRED, 'file id or path')
			->addArgument('tags', InputArgument::REQUIRED, 'Name of the tag(s) to delete, comma separated')
			->addArgument('access', InputArgument::REQUIRED, 'access level of the tag (public, restricted or invisible)');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$targetInput = $input->getArgument('target');
		$tagsInput = $input->getArgument('tags');

		if ($tagsInput === '') {
			$output->writeln('<error>`tags` can\'t be empty</error>');
			return 3;
		}

		$tagNameArray = explode(',', $tagsInput);

		$access = $input->getArgument('access');
		switch ($access) {
			case 'public':
				$userVisible = true;
				$userAssignable = true;
				break;
			case 'restricted':
				$userVisible = true;
				$userAssignable = false;
				break;
			case 'invisible':
				$userVisible = false;
				$userAssignable = false;
				break;
			default:
				$output->writeln('<error>`access` property is invalid</error>');
				return 1;
		}

		$targetNode = $this->fileUtils->getNode($targetInput);

		if (! $targetNode) {
			$output->writeln("<error>file $targetInput not found</error>");
			return 1;
		}

		foreach ($tagNameArray as $tagName) {
			try {
				$tag = $this->systemTagManager->getTag($tagName, $userVisible, $userAssignable);
				$this->systemTagObjectMapper->unassignTags((string)$targetNode->getId(), 'files', $tag->getId());
				$output->writeln("<info>$access</info> tag named <info>$tagName</info> removed.");
			} catch (TagNotFoundException $e) {
				$output->writeln("<info>$access</info> tag named <info>$tagName</info> does not exist!");
			}
		}

		return 0;
	}
}
