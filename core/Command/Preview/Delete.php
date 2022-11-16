<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Charley Paulus <charleypaulus@hotmail.com>
 *
 * @author Charley Paulus <charleypaulus@hotmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Command\Preview;

use OC\Preview\Storage\Root;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Command {
	protected IDBConnection $connection;
	private Root $previewFolder;
	private IMimeTypeLoader $mimeTypeLoader;

	public function __construct(IDBConnection $connection,
								Root $previewFolder,
								IMimeTypeLoader $mimeTypeLoader) {
		parent::__construct();

		$this->connection = $connection;
		$this->previewFolder = $previewFolder;
		$this->mimeTypeLoader = $mimeTypeLoader;
	}

	protected function configure() {
		$this
			->setName('preview:delete')
			->setDescription('Deletes all previews')
			->addOption('old-only', 'o', InputOption::VALUE_NONE, 'Limit deletion to old previews (no longer having their original file)')
			->addOption('mimetype', 'm', InputArgument::OPTIONAL, 'Limit deletion to this mimetype, eg. --mimetype="image/jpeg"')
			->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry mode (will not delete any files). In combination with the verbose mode one could check the operations');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$oldOnly = $input->getOption('old-only');

		$selectedMimetype = $input->getOption('mimetype');
		if ($selectedMimetype) {
			if ($oldOnly) {
				$output->writeln('Mimetype of absent original files cannot be determined. Aborting...');
				return 0;
			} else {
				if (! $this->mimeTypeLoader->exists($selectedMimetype)) {
					$output->writeln('Mimetype ' . $selectedMimetype . ' does not exist in database. Aborting...');
					return 0;
				}
			}
		}

		$dryMode = $input->getOption('dry');
		if ($dryMode) {
			$output->writeln('INFO: The command is run in dry mode and will not modify anything.');
			$output->writeln('');
		}

		$this->deletePreviews($output, $oldOnly, $selectedMimetype, $dryMode);

		return 0;
	}

	private function deletePreviews(OutputInterface $output, bool $oldOnly, string $selectedMimetype = null, bool $dryMode): void {
		$previewFoldersToDeleteCount = 0;

		foreach ($this->getPreviewsToDelete($output, $oldOnly, $selectedMimetype) as ['name' => $previewFileId, 'path' => $filePath]) {
			if ($oldOnly || $filePath === null) {
				$output->writeln('Deleting previews of absent original file (fileid:' . $previewFileId . ')', OutputInterface::VERBOSITY_VERBOSE);
			} else {
				$output->writeln('Deleting previews of original file ' . substr($filePath, 7) . ' (fileid:' . $previewFileId . ')', OutputInterface::VERBOSITY_VERBOSE);
		}

			$previewFoldersToDeleteCount++;

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

		$output->writeln('Deleted previews of ' . $previewFoldersToDeleteCount . ' original files');
	}

	// Copy pasted and adjusted from
	// "lib/private/Preview/BackgroundCleanupJob.php".
	private function getPreviewsToDelete(OutputInterface $output, bool $oldOnly, string $selectedMimetype = null): \Iterator {
		// Get preview folder
		$qb = $this->connection->getQueryBuilder();
		$qb->select('path', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($this->previewFolder->getId())));
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		$output->writeln('Preview folder: ' . $data['path'], OutputInterface::VERBOSITY_VERBOSE);

		if ($data === null) {
			return [];
		}

		// Get previews to delete
		// Initialize Query Builder
		$qb = $this->connection->getQueryBuilder();

		/* This lovely like is the result of the way the new previews are stored
		 * We take the md5 of the name (fileid) and split the first 7 chars. That way
		 * there are not a gazillion files in the root of the preview appdata.*/
		$like = $this->connection->escapeLikeParameter($data['path']) . '/_/_/_/_/_/_/_/%';

		// Specify conditions based on options
		$and = $qb->expr()->andX();
		$and->add($qb->expr()->like('a.path', $qb->createNamedParameter($like)));
		$and->add($qb->expr()->eq('a.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory'))));
		if ($oldOnly) {
			$and->add($qb->expr()->isNull('b.fileid'));
		}
		if ($selectedMimetype) {
			$and->add($qb->expr()->eq('b.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('image/jpeg'))));
		}

		// Build query
		$qb->select('a.name', 'b.path')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where($and);

		$cursor = $qb->execute();

		while ($row = $cursor->fetch()) {
			yield $row;
		}

		$cursor->closeCursor();
	}
}
