<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Search;

use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Search\Provider;
use OCP\Server;
use function count;

class LegacyProvider extends Provider {
	/**
	 * Search for $query
	 *
	 * @param string $query
	 * @return array An array of OCP\Search\Result's
	 * @since 7.0.0
	 */
	public function search($query): array {
		$cm = Server::get(ICommentsManager::class);
		$us = Server::get(IUserSession::class);

		$user = $us->getUser();
		if (!$user instanceof IUser) {
			return [];
		}
		$uf = \OC::$server->getUserFolder($user->getUID());

		if ($uf === null) {
			return [];
		}

		$result = [];
		$numComments = 50;
		$offset = 0;

		while (count($result) < $numComments) {
			/** @var IComment[] $comments */
			$comments = $cm->search($query, 'files', '', 'comment', $offset, $numComments);

			foreach ($comments as $comment) {
				if ($comment->getActorType() !== 'users') {
					continue;
				}

				$displayName = $cm->resolveDisplayName('user', $comment->getActorId());

				try {
					$file = $this->getFileForComment($uf, $comment);
					$result[] = new Result($query,
						$comment,
						$displayName,
						$file->getPath(),
						$file->getId(),
					);
				} catch (NotFoundException|InvalidPathException $e) {
					continue;
				}
			}

			if (count($comments) < $numComments) {
				// Didn't find more comments when we tried to get, so there are no more comments.
				return $result;
			}

			$offset += $numComments;
			$numComments = 50 - count($result);
		}

		return $result;
	}

	/**
	 * @param Folder $userFolder
	 * @param IComment $comment
	 * @return Node
	 * @throws NotFoundException
	 */
	protected function getFileForComment(Folder $userFolder, IComment $comment): Node {
		$nodes = $userFolder->getById((int)$comment->getObjectId());
		if (empty($nodes)) {
			throw new NotFoundException('File not found');
		}

		return array_shift($nodes);
	}
}
