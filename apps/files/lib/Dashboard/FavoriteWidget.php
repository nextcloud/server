<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Dashboard;

use OCA\Files\AppInfo\Application;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IPreview;
use OCP\ITagManager;
use OCP\IURLGenerator;
use OCP\IUserManager;

class FavoriteWidget implements IIconWidget, IAPIWidgetV2, IButtonWidget, IOptionWidget {

	public function __construct(
		private readonly IL10N $l10n,
		private readonly IURLGenerator $urlGenerator,
		private readonly IMimeTypeDetector $mimeTypeDetector,
		private readonly IUserManager $userManager,
		private readonly ITagManager $tagManager,
		private readonly IRootFolder $rootFolder,
		private readonly IPreview $previewManager,
	) {
	}

	public function getId(): string {
		return Application::APP_ID . '-favorites';
	}

	public function getTitle(): string {
		return $this->l10n->t('Favorite files');
	}

	public function getOrder(): int {
		return 0;
	}

	public function getIconClass(): string {
		return 'icon-star-dark';
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath('core', 'actions/star.svg')
		);
	}

	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute('files.View.indexView', ['view' => 'favorites']);
	}

	public function load(): void {
	}

	public function getItems(string $userId, int $limit = 7): array {
		$user = $this->userManager->get($userId);

		if (!$user) {
			return [];
		}
		$tags = $this->tagManager->load('files', [], false, $userId);
		$favorites = $tags->getFavorites();
		if (empty($favorites)) {
			return [];
		}
		$favoriteNodes = [];
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$count = 0;
		foreach ($favorites as $favorite) {
			$node = $userFolder->getFirstNodeById($favorite);
			if ($node) {
				$url = $this->urlGenerator->linkToRouteAbsolute(
					'files.view.showFile', ['fileid' => $node->getId()]
				);
				if ($node instanceof File) {
					$icon = $this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', [
						'x' => 256,
						'y' => 256,
						'fileId' => $node->getId(),
						'c' => $node->getEtag(),
						'mimeFallback' => true,
					]);
				} else {
					$icon = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'filetypes/folder.svg'));
				}
				$favoriteNodes[] = new WidgetItem(
					$node->getName(),
					'',
					$url,
					$icon,
					(string)$node->getCreationTime()
				);
				$count++;
				if ($count >= $limit) {
					break;
				}
			}
		}

		return $favoriteNodes;
	}

	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
		$items = $this->getItems($userId, $limit);
		return new WidgetItems(
			$items,
			count($items) === 0 ? $this->l10n->t('No favorites') : '',
		);
	}

	public function getWidgetButtons(string $userId): array {
		return [
			new WidgetButton(
				WidgetButton::TYPE_MORE,
				$this->urlGenerator->linkToRouteAbsolute('files.View.indexView', ['view' => 'favorites']),
				$this->l10n->t('More favorites')
			),
		];
	}

	public function getWidgetOptions(): WidgetOptions {
		return new WidgetOptions(roundItemIcons: false);
	}
}
