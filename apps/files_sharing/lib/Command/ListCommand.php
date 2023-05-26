<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing\Command;

use OC\Core\Command\Base;
use OCP\IUserManager;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	private IManager $shareManager;

	public function __construct(
		IManager $shareManager
	) {
		$this->shareManager = $shareManager;
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('sharing:list')
			->setDescription('List shares')
			->addOption('owner', null, InputOption::VALUE_REQUIRED, "Limit shares by share owner")
			->addOption('shared-by', null, InputOption::VALUE_REQUIRED, "Limit shares by share initiator")
			->addOption('shared-with', null, InputOption::VALUE_REQUIRED, "Limit shares by share recipient")
			->addOption('share-type', null, InputOption::VALUE_REQUIRED, "Limit shares by share recipient")
			->addOption('file-id', null, InputOption::VALUE_REQUIRED, "Limit shares to a specific file or folder id");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$ownerInput = $input->getOption('owner');
		$sharedByInput = $input->getOption('shared-by');
		$sharedWithInput = $input->getOption('shared-with');
		$shareTypeInput = $input->getOption('share-type');
		$fileInput = $input->getOption('file-id');

		if ($shareTypeInput) {
			$shareType = $this->parseShareType($shareTypeInput);
			if ($shareType === null) {
				$output->writeln("<error>Unknown share type $shareTypeInput</error>");
				$output->writeln("possible values: <info>user</info>, <info>group</info>, <info>link</info>, " .
					"<info>email</info>, <info>remote</info>, <info>circle</info>, <info>guest</info>, <info>remote_group</info>, " .
					"<info>room</info>, <info>deck</info>, <info>deck_user</info>, <info>science-mesh</info>");
				return 1;
			}
		} else {
			$shareTypeInput = null;
		}

		$allShares = $this->shareManager->getAllShares();

		$filteredShares = new \CallbackFilterIterator($allShares, function(IShare $share) use ($ownerInput, $shareType, $sharedByInput, $sharedWithInput, $fileInput) {
			return $this->filterShare($share, $ownerInput, $sharedByInput, $sharedWithInput, $shareType, $fileInput);
		});

		$shareData = [];
		foreach ($filteredShares as $share) {
			/** @var IShare $share */
			$shareData[] = [
				'share-id' => $share->getFullId(),
				'share-owner' => $share->getShareOwner(),
				'shared-by' => $share->getSharedBy(),
				'shared-with' => $share->getSharedWith(),
				'share-type' => $this->formatShareType($share->getShareType()),
				'file-id' => $share->getNodeId(),
			];
		}

		$outputFormat = $input->getOption('output');
		if ($outputFormat === self::OUTPUT_FORMAT_JSON || $outputFormat === self::OUTPUT_FORMAT_JSON_PRETTY) {
			$this->writeArrayInOutputFormat($input, $output, $shareData);
		} else {
			$table = new Table($output);
			$table
				->setHeaders(['share-id', 'share-owner', 'shared-by', 'shared-with', 'share-type', 'file-id'])
				->setRows($shareData);
			$table->render();
		}

		return 0;
	}

	private function filterShare(
		IShare $share,
		string $ownerInput = null,
		string $sharedByInput = null,
		string $sharedWithInput = null,
		int $shareType = null,
		int $fileInput = null
	): bool {
		if ($ownerInput && $share->getShareOwner() !== $ownerInput) {
			return false;
		}
		if ($sharedByInput && $share->getSharedBy() !== $sharedByInput) {
			return false;
		}
		if ($sharedWithInput && $share->getSharedWith() !== $sharedWithInput) {
			return false;
		}
		if ($shareType && $share->getShareType() !== $shareType) {
			return false;
		}
		if ($fileInput && $share->getNodeId() !== $fileInput) {
			return false;
		}
		return true;
	}

	private function parseShareType(string $type): ?int {
		switch ($type) {
			case 'user':
				return IShare::TYPE_USER;
			case 'group':
				return IShare::TYPE_GROUP;
			case 'link':
				return IShare::TYPE_LINK;
			case 'email':
				return IShare::TYPE_EMAIL;
			case 'remote':
				return IShare::TYPE_REMOTE;
			case 'circle':
				return IShare::TYPE_CIRCLE;
			case 'guest':
				return IShare::TYPE_GUEST;
			case 'remote_group':
				return IShare::TYPE_REMOTE_GROUP;
			case 'room':
				return IShare::TYPE_ROOM;
			case 'deck':
				return IShare::TYPE_DECK;
			case 'deck_user':
				return IShare::TYPE_DECK_USER;
			case 'science-mesh':
				return IShare::TYPE_SCIENCEMESH;
			default:
				return null;
		}
	}

	private function formatShareType(int $type): string {
		switch ($type) {
			case IShare::TYPE_USER:
				return 'user';
			case IShare::TYPE_GROUP:
				return 'group';
			case IShare::TYPE_LINK:
				return 'link';
			case IShare::TYPE_EMAIL:
				return 'email';
			case IShare::TYPE_REMOTE:
				return 'remote';
			case IShare::TYPE_CIRCLE:
				return 'circle';
			case IShare::TYPE_GUEST:
				return 'guest';
			case IShare::TYPE_REMOTE_GROUP:
				return 'remote_group';
			case IShare::TYPE_ROOM:
				return 'room';
			case IShare::TYPE_DECK:
				return 'deck';
			case IShare::TYPE_DECK_USER:
				return 'deck_user';
			case IShare::TYPE_SCIENCEMESH:
				return 'science-mesh';
			default:
				return 'other';
		}
	}
}
