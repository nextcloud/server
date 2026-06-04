<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Preview;

use OC\Preview\Db\Preview;
use OC\Preview\PreviewService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAvatarManager;
use OCP\IDBConnection;
use OCP\IUserManager;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetRenderedTexts extends Command {
	public function __construct(
		protected readonly IDBConnection $connection,
		protected readonly IUserManager $userManager,
		protected readonly IAvatarManager $avatarManager,
		private readonly PreviewService $previewService,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		$this
			->setName('preview:reset-rendered-texts')
			->setDescription('Deletes all generated avatars and previews of text and md files')
			->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry mode - will not delete any files - in combination with the verbose mode one could check the operations.');
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryMode = $input->getOption('dry');

		if ($dryMode) {
			$output->writeln('INFO: The command is run in dry mode and will not modify anything.');
			$output->writeln('');
		}

		$this->deleteAvatars($output, $dryMode);
		$this->deletePreviews($output, $dryMode);

		return 0;
	}

	private function deleteAvatars(OutputInterface $output, bool $dryMode): void {
		$avatarsToDeleteCount = 0;

		foreach ($this->getAvatarsToDelete() as [$userId, $avatar]) {
			$output->writeln('Deleting avatar for ' . $userId, OutputInterface::VERBOSITY_VERBOSE);

			$avatarsToDeleteCount++;

			if ($dryMode) {
				continue;
			}

			try {
				$avatar->remove();
			} catch (NotFoundException|NotPermittedException) {
				// continue
			}
		}

		$output->writeln('Deleted ' . $avatarsToDeleteCount . ' avatars');
		$output->writeln('');
	}

	private function getAvatarsToDelete(): \Iterator {
		foreach ($this->userManager->searchDisplayName('') as $user) {
			$avatar = $this->avatarManager->getAvatar($user->getUID());

			if (!$avatar->isCustomAvatar()) {
				yield [$user->getUID(), $avatar];
			}
		}
	}

	private function deletePreviews(OutputInterface $output, bool $dryMode): void {
		$previewsToDeleteCount = 0;

		foreach ($this->getPreviewsToDelete() as $preview) {
			$output->writeln('Deleting preview ' . $preview->getName() . ' for fileId ' . $preview->getFileId(), OutputInterface::VERBOSITY_VERBOSE);

			$previewsToDeleteCount++;

			if ($dryMode) {
				continue;
			}

			$this->previewService->deletePreview($preview);
		}

		$output->writeln('Deleted ' . $previewsToDeleteCount . ' previews');
	}

	/**
	 * @return \Generator<Preview>
	 */
	private function getPreviewsToDelete(): \Generator {
		return $this->previewService->getPreviewsForMimeTypes([
			'text/plain',
			'text/markdown',
			'text/x-markdown'
		]);
	}
}
