<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\Core\Command\FilesMetadata;

use OC\DB\ConnectionAdapter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\IFilesMetadataManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command {
	public function __construct(
		private IRootFolder $rootFolder,
		private IFilesMetadataManager $filesMetadataManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this->setName('metadata:get')
			 ->setDescription('get stored metadata about a file, by its id')
			 ->addArgument(
				 'fileId',
				 InputArgument::REQUIRED,
				 'id of the file document'
			 )
			 ->addArgument(
				 'userId',
				 InputArgument::OPTIONAL,
				 'file owner'
			 )
			 ->addOption(
				 'refresh',
				 '',
				 InputOption::VALUE_NONE,
				 'refresh metadata'
			 )
			 ->addOption(
				 'reset',
				 '',
				 InputOption::VALUE_NONE,
				 'refresh metadata from scratch'
			 )
			 ->addOption(
				 'background',
				 '',
				 InputOption::VALUE_NONE,
				 'emulate background jobs when refreshing metadata'
			 );
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$fileId = (int)$input->getArgument('fileId');
		if ($input->getOption('refresh')) {
			$node = $this->rootFolder->getUserFolder($input->getArgument('userId'))->getById($fileId);
			$file = $node[0];
			$metadata = $this->filesMetadataManager->refreshMetadata(
				$file,
				$input->getOption('background'),
				$input->getOption('reset')
			);
		} else {
			$metadata = $this->filesMetadataManager->getMetadata($fileId);
		}

		$output->writeln(json_encode($metadata, JSON_PRETTY_PRINT));

		return 0;
	}
}
