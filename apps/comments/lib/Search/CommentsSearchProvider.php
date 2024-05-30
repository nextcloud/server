<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Search;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use function array_map;
use function pathinfo;

class CommentsSearchProvider implements IProvider {
	public function __construct(
		private IUserManager $userManager,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private LegacyProvider $legacyProvider,
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
		return SearchResult::complete(
			$this->l10n->t('Comments'),
			array_map(function (Result $result) {
				$path = $result->path;
				$pathInfo = pathinfo($path);
				$isUser = $this->userManager->userExists($result->authorId);
				$avatarUrl = $isUser
					? $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', ['userId' => $result->authorId, 'size' => 42])
					: $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $result->authorId, 'size' => 42]);
				return new SearchResultEntry(
					$avatarUrl,
					$result->name,
					$path,
					$this->urlGenerator->linkToRouteAbsolute('files.view.index', [
						'dir' => $pathInfo['dirname'],
						'scrollto' => $pathInfo['basename'],
					]),
					'',
					true
				);
			}, $this->legacyProvider->search($query->getTerm()))
		);
	}
}
