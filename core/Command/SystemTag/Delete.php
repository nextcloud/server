<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\SystemTag;

use OC\Core\Command\Base;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Base {
	public function __construct(
		protected ISystemTagManager $systemTagManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('tag:delete')
			->setDescription('delete a tag')
			->addArgument(
				'id',
				InputOption::VALUE_REQUIRED,
				'The ID of the tag that should be deleted',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->systemTagManager->deleteTags($input->getArgument('id'));
			$output->writeln('<info>The specified tag was deleted</info>');
			return 0;
		} catch (TagNotFoundException $e) {
			$output->writeln('<error>Tag not found</error>');
			return 1;
		}
	}
}
