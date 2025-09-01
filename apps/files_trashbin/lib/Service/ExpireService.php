<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Service;

use OCA\Files_Trashbin\Command\Expire;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Trashbin;
use OCP\Command\IBus;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use Psr\Log\LoggerInterface;

class ExpireService {
	public function __construct(
		readonly private TrashFolderService $trashFolderService,
		readonly private Expiration $expiration,
		readonly private IBus $ibus,
		readonly private LoggerInterface $logger,
	) {
	}

	public function expireTrashForUser(IUser $user): void {
		$trashFolderRoot = $this->trashFolderService->getTrashFolderRoot($user);
		if (!$trashFolderRoot) {
			return;
		}

		$availableSpace = $this->trashFolderService->getAvailableSpace($trashFolderRoot, $user);

		try {
			/** @var Folder $trashFolder */
			$trashFolder = $trashFolderRoot->get('files');
		} catch (NotFoundException) {
			echo "bug";
			return; // Nothing to expire
		}

		$nodes = $trashFolder->getDirectoryListing();

		usort($nodes, fn (Node $a, Node $b): int => $a->getMTime() <=> $b->getMTime());

		// delete all files older then $retention_obligation
		[$delSize, $count] = $this->deleteExpiredNodes($trashFolder, $nodes, $user);

		$availableSpace += $delSize;

		// delete files from trash until we meet the trash bin size limit again
		Trashbin::deleteNodes(array_slice($nodes, $count), $user, $availableSpace);
	}

	public function scheduleExpirationJobIfNeeded(IUser $user): void {
		$trashFolderRoot = $this->trashFolderService->getTrashFolderRoot($user);
		if (!$trashFolderRoot) {
			return;
		}

		$freeSpace = $this->trashFolderService->getAvailableSpace($trashFolderRoot, $user);

		if ($freeSpace < 0) {
			$this->scheduleExpirationJob($user);
		}
	}

	public function scheduleExpirationJob(IUser $user): void {
		// let the admin disable auto expire
		if ($this->expiration->isEnabled()) {
			$this->ibus->push(new Expire($user->getUID()));
		}
	}

	/**
	 * @param Node[] $nodes
	 */
	private function deleteExpiredNodes(Folder $trashFolder, array $nodes, IUser $user) {
		/** @var Expiration $expiration */
		$expiration = Server::get(Expiration::class);
		$size = 0;
		$count = 0;
		foreach ($nodes as $node) {
			$timestamp = $node->getMTime();
			if (!$expiration->isExpired($timestamp)) {
				break; // Since the nodes are sorted by mtime, we can already abord
			}

			try {
				$size += $this->trashFolderService->delete($trashFolder, $node, $user, $timestamp);
				$count++;
			} catch (NotPermittedException $e) {
				$this->logger->warning('Removing "' . $node->getName() . '" from trashbin failed for user "{user}"',
					[
						'exception' => $e,
						'app' => 'files_trashbin',
						'user' => $user,
					]
				);
				continue;
			}
			$this->logger->info(
				'Remove "' . $node->getName() . '" from trashbin for user "{user}" because it exceeds max retention obligation term.',
				[
					'app' => 'files_trashbin',
					'user' => $user,
				],
			);
		}

		return [$size, $count];
	}
}
