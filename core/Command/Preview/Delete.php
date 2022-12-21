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
			->addOption('remnant-only', 'r', InputOption::VALUE_NONE, 'Limit deletion to remnant previews (no longer having their original file)')
			->addOption('mimetype', 'm', InputArgument::OPTIONAL, 'Limit deletion to this mimetype, eg. --mimetype="image/jpeg"')
			->addOption('batch-size', 'b', InputArgument::OPTIONAL, 'Delete previews by batches of specified number (for database access performance issue')
			->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry mode (will not delete any files). In combination with the verbose mode one could check the operations');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// Get options
		$remnantOnly = $input->getOption('remnant-only');
		$selectedMimetype = $input->getOption('mimetype');
		$batchSize = $input->getOption('batch-size');
		$dryMode = $input->getOption('dry');
		
		// Handle incompatible options choices
		if ($selectedMimetype) {
			if ($remnantOnly) {
				$output->writeln('Mimetype of absent original files cannot be determined. Aborting...');
				return 0;
			} else {
				if (! $this->mimeTypeLoader->exists($selectedMimetype)) {
					$output->writeln('Mimetype ' . $selectedMimetype . ' does not exist in database. Aborting...');
					return 0;
				}
			}
		}

		if ($batchSize != null) {
			$batchSize = (int) $batchSize;
			if ($batchSize <= 0) {
				$output->writeln('Batch size must be a strictly positive integer. Aborting...');
				return 0;
			}
		}

		if ($batchSize && $dryMode) {
			$output->writeln('Batch mode is incompatible with dry mode as it relies on actually deleted batches. Aborting...');
			return 0;
		}

		// Display dry mode message
		if ($dryMode) {
			$output->writeln('INFO: The command is run in dry mode and will not modify anything.');
			$output->writeln('');
		}
		
		// Delete previews
		$this->deletePreviews($output, $remnantOnly, $selectedMimetype, $batchSize, $dryMode);

		return 0;
	}

	private function deletePreviews(OutputInterface $output, bool $remnantOnly, string $selectedMimetype = null, int $batchSize = null, bool $dryMode): void {
		// Get preview folder path
		$previewFolderPath = $this->getPreviewFolderPath($output);
		
		// Delete previews
		$hasPreviews = true;
		$batchCount = 0;
		$batchStr = '';
		while ($hasPreviews) {
			$previewFoldersToDeleteCount = 0;
			foreach ($this->getPreviewsToDelete($output, $previewFolderPath, $remnantOnly, $selectedMimetype, $batchSize) as ['name' => $previewFileId, 'path' => $filePath]) {
				if ($remnantOnly || $filePath === null) {
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
			
			if ($batchSize) {
				$batchCount++;
				$batchStr = '[Batch ' . $batchCount . '] ';
			}
			
			if ($batchSize === null || $previewFoldersToDeleteCount === 0) {
				$hasPreviews = false;
			}
			
			if ($previewFoldersToDeleteCount > 0) {
				$output->writeln($batchStr . 'Deleted previews of ' . $previewFoldersToDeleteCount . ' original files');
			}
		}
	}

	// Copy pasted and adjusted from
	// "lib/private/Preview/BackgroundCleanupJob.php".
	private function getPreviewFolderPath(OutputInterface $output): string {
		// Get preview folder
		$qb = $this->connection->getQueryBuilder();
		$qb->select('path', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($this->previewFolder->getId())));
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === null) {
			$output->writeln('No preview folder found.');
			return "";
		}
		
		$output->writeln('Preview folder: ' . $data['path'], OutputInterface::VERBOSITY_VERBOSE);
		return $data['path'];
	}
	
	private function getPreviewsToDelete(OutputInterface $output, string $previewFolderPath, bool $remnantOnly, string $selectedMimetype = null, int $batchSize = null): \Iterator {
		// Initialize Query Builder
		$qb = $this->connection->getQueryBuilder();

		/* This lovely like is the result of the way the new previews are stored
		 * We take the md5 of the name (fileid) and split the first 7 chars. That way
		 * there are not a gazillion files in the root of the preview appdata.*/
		$like = $this->connection->escapeLikeParameter($previewFolderPath) . '/_/_/_/_/_/_/_/%';

		// Specify conditions based on options
		$and = $qb->expr()->andX();
		$and->add($qb->expr()->eq('a.storage', $qb->createNamedParameter($this->previewFolder->getStorageId())));
		$and->add($qb->expr()->like('a.path', $qb->createNamedParameter($like)));
		$and->add($qb->expr()->eq('a.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory'))));
		if ($remnantOnly) {
			$and->add($qb->expr()->isNull('b.fileid'));
		}
		if ($selectedMimetype) {
			$and->add($qb->expr()->eq('b.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId($selectedMimetype))));
		}

		// Build query
		$qb->select('a.name', 'b.path')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where($and)
			->setMaxResults($batchSize);

		$cursor = $qb->execute();

		while ($row = $cursor->fetch()) {
			yield $row;
		}

		$cursor->closeCursor();
	}
}
