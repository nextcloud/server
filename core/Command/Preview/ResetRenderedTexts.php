<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Preview;

use OC\Preview\Storage\Root;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAvatarManager;
use OCP\IDBConnection;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetRenderedTexts extends Command {
	public function __construct(
		protected IDBConnection $connection,
		protected IUserManager $userManager,
		protected IAvatarManager $avatarManager,
		private Root $previewFolder,
		private IMimeTypeLoader $mimeTypeLoader,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('preview:reset-rendered-texts')
			->setDescription('Deletes all generated avatars and previews of text and md files')
			->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry mode - will not delete any files - in combination with the verbose mode one could check the operations.');
	}

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
			} catch (NotFoundException $e) {
				// continue
			} catch (NotPermittedException $e) {
				// continue
			}
		}

		$output->writeln('Deleted ' . $avatarsToDeleteCount . ' avatars');
		$output->writeln('');
	}

	private function getAvatarsToDelete(): \Iterator {
		foreach ($this->userManager->search('') as $user) {
			$avatar = $this->avatarManager->getAvatar($user->getUID());

			if (!$avatar->isCustomAvatar()) {
				yield [$user->getUID(), $avatar];
			}
		}
	}

	private function deletePreviews(OutputInterface $output, bool $dryMode): void {
		$previewsToDeleteCount = 0;

		foreach ($this->getPreviewsToDelete() as ['name' => $previewFileId, 'path' => $filePath]) {
			$output->writeln('Deleting previews for ' . $filePath, OutputInterface::VERBOSITY_VERBOSE);

			$previewsToDeleteCount++;

			if ($dryMode) {
				continue;
			}

			try {
				$preview = $this->previewFolder->getFolder((string)$previewFileId);
				$preview->delete();
			} catch (NotFoundException $e) {
				// continue
			} catch (NotPermittedException $e) {
				// continue
			}
		}

		$output->writeln('Deleted ' . $previewsToDeleteCount . ' previews');
	}

	// Copy pasted and adjusted from
	// "lib/private/Preview/BackgroundCleanupJob.php".
	private function getPreviewsToDelete(): \Iterator {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('path', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($this->previewFolder->getId())));
		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === null) {
			return [];
		}

		/*
		 * This lovely like is the result of the way the new previews are stored
		 * We take the md5 of the name (fileid) and split the first 7 chars. That way
		 * there are not a gazillion files in the root of the preview appdata.
		 */
		$like = $this->connection->escapeLikeParameter($data['path']) . '/_/_/_/_/_/_/_/%';

		$qb = $this->connection->getQueryBuilder();
		$qb->select('a.name', 'b.path')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where(
				$qb->expr()->andX(
					$qb->expr()->like('a.path', $qb->createNamedParameter($like)),
					$qb->expr()->eq('a.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory'))),
					$qb->expr()->orX(
						$qb->expr()->eq('b.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('text/plain'))),
						$qb->expr()->eq('b.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('text/markdown'))),
						$qb->expr()->eq('b.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('text/x-markdown')))
					)
				)
			);

		$cursor = $qb->executeQuery();

		while ($row = $cursor->fetch()) {
			yield $row;
		}

		$cursor->closeCursor();
	}
}
