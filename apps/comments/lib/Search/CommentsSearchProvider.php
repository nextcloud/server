<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Comments\Search;

use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Psr\Log\LoggerInterface;

class CommentsSearchProvider implements IProvider {
	public function __construct(
		private IUserManager $userManager,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ICommentsManager $commentsManager,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
	}

	public function getId(): string {
		return 'comments';
	}

	public function getName(): string {
		return $this->l10n->t('Comments');
	}

	public function getOrder(string $route, array $routeParameters): int {
		if ($route === 'files.View.index') {
			// Files first
			return 0;
		}
		return 10;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$result = $this->findCommentsBySearchQuery($query, $userFolder);

		return SearchResult::complete(
			$this->l10n->t('Comments'),
			$result
		);
	}

	/**
	 * @return list<SearchResultEntry>
	 */
	private function findCommentsBySearchQuery(ISearchQuery $query, Folder $userFolder): array {
		$result = [];
		$numComments = 50;
		$offset = 0;

		while (count($result) < $numComments) {
			$comments = $this->commentsManager->search(
				$query->getTerm(),
				'files',
				'',
				'comment',
				$offset,
				$numComments
			);

			foreach ($comments as $comment) {
				if ($comment->getActorType() !== 'users') {
					continue;
				}

				try {
					$node = $this->getFileForComment($userFolder, $comment);
				} catch (\Throwable $e) {
					$this->logger->debug('Found comment for a file, but obtaining the file thrown an exception', ['exception' => $e]);
					continue;
				}

				$actorId = $comment->getActorId();
				$isUser = $this->userManager->userExists($actorId);

				$avatarUrl = $isUser
					? $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', ['userId' => $actorId, 'size' => 42])
					: $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $actorId, 'size' => 42]);

				$path = $userFolder->getRelativePath($node->getPath());

				// Use shortened link to centralize the various
				// files/folder url redirection in files.View.showFile
				$link = $this->urlGenerator->linkToRoute(
					'files.View.showFile',
					['fileid' => $node->getId()]
				);

				$searchResultEntry = new SearchResultEntry(
					$avatarUrl,
					$comment->getMessage(),
					ltrim($path, '/'),
					$this->urlGenerator->getAbsoluteURL($link),
					'',
				);
				$searchResultEntry->addAttribute('fileId', (string)$node->getId());
				$searchResultEntry->addAttribute('path', $path);

				$result[] = $searchResultEntry;
			}

			if (count($comments) < $numComments) {
				// Didn't find more comments when we tried to get, so there are no more comments.
				break;
			}

			$offset += $numComments;
			$numComments = 50 - count($result);
		}

		return $result;
	}

	private function getFileForComment(Folder $userFolder, IComment $comment): Node {
		$node = $userFolder->getFirstNodeById((int)$comment->getObjectId());
		if ($node === null) {
			throw new NotFoundException('File not found');
		}

		return $node;
	}
}
