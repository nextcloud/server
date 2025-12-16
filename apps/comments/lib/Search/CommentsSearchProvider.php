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
use OCP\Files\InvalidPathException;
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

class CommentsSearchProvider implements IProvider {
	public function __construct(
		private IUserManager $userManager,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ICommentsManager $commentsManager,
		private IRootFolder $rootFolder,
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

		$result = [];
		$numComments = 50;
		$offset = 0;

		while (count($result) < $numComments) {
			$comments = $this->commentsManager->search($query->getTerm(), 'files', '', 'comment', $offset, $numComments);

			foreach ($comments as $comment) {
				if ($comment->getActorType() !== 'users') {
					continue;
				}

				$displayName = $this->commentsManager->resolveDisplayName('user', $comment->getActorId());

				try {
					$file = $this->getFileForComment($userFolder, $comment);

					$isUser = $this->userManager->userExists($comment->getActorId());
					$avatarUrl = $isUser
						? $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', ['userId' => $comment->getActorId(), 'size' => 42])
						: $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $comment->getActorId(), 'size' => 42]);
					$link = $this->urlGenerator->linkToRoute(
						'files.View.showFile',
						['fileid' => $file->getId()]
					);

					$result[] = new SearchResultEntry(
						$avatarUrl,
						$displayName,
						$file->getPath(),
						$link,
						'',
						true
					);
				} catch (NotFoundException|InvalidPathException $e) {
					continue;
				}
			}

			if (count($comments) < $numComments) {
				// Didn't find more comments when we tried to get, so there are no more comments.
				break;
			}

			$offset += $numComments;
			$numComments = 50 - count($result);
		}


		return SearchResult::complete(
			$this->l10n->t('Comments'),
			$result,
		);
	}

	/**
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
