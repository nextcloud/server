<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotPermittedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
	name: 'dav:clear-contacts-photo-cache',
	description: 'Clear cached contact photos',
	hidden: false,
)]
class ClearContactsPhotoCache extends Command {

	public function __construct(
		private IAppDataFactory $appDataFactory,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$photoCacheAppData = $this->appDataFactory->get('dav-photocache');

		$folders = $photoCacheAppData->getDirectoryListing();
		$countFolders = count($folders);

		if ($countFolders === 0) {
			$output->writeln('No cached contact photos found.');
			return self::SUCCESS;
		}

		$output->writeln('Found ' . count($folders) . ' cached contact photos.');

		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion('Please confirm to clear the contacts photo cache [y/n] ', true);

		if ($helper->ask($input, $output, $question) === false) {
			$output->writeln('Clearing the contacts photo cache aborted.');
			return self::SUCCESS;
		}

		$progressBar = new ProgressBar($output, $countFolders);
		$progressBar->start();

		foreach ($folders as $folder) {
			try {
				$folder->delete();
			} catch (NotPermittedException) {
			}
			$progressBar->advance();
		}

		$progressBar->finish();

		$output->writeln('');
		$output->writeln('Contacts photo cache cleared.');

		return self::SUCCESS;
	}
}
