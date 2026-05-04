<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Source;

use Exception;
use OCA\Files\AppInfo\Application;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Source\IShareSourceType;
use RuntimeException;

final class NodeShareSourceType implements IShareSourceType {
	private ?IRootFolder $rootFolder = null;

	private ?IURLGenerator $urlGenerator = null;

	private function getRootFolder(): IRootFolder {
		return $this->rootFolder ??= Server::get(IRootFolder::class);
	}

	private function getUrlGenerator(): IURLGenerator {
		return $this->urlGenerator ??= Server::get(IURLGenerator::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('File');
	}

	#[\Override]
	public function validateSource(IUser $owner, string $source): bool {
		$neededPermissions = Constants::PERMISSION_READ | Constants::PERMISSION_SHARE;

		try {
			$nodes = $this->getRootFolder()->getUserFolder($owner->getUID())->getById((int)$source);
			$permissions = 0;
			foreach ($nodes as $node) {
				$permissions |= $node->getPermissions();
			}

			return ($permissions & $neededPermissions) === $neededPermissions;
		} catch (Exception) {
			return false;
		}
	}

	#[\Override]
	public function getSourceDisplayName(string $source): ?string {
		$displayName = $this->getRootFolder()->getFirstNodeById((int)$source)?->getName();
		if ($displayName === '') {
			return null;
		}

		return $displayName;
	}

	#[\Override]
	public function getSourceIcon(string $source): ?ShareIconURL {
		$node = $this->getRootFolder()->getFirstNodeById((int)$source);
		if (!$node instanceof File) {
			return null;
		}

		$url = $this->getUrlGenerator()->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['fileId' => $source, 'x' => 64, 'y' => 64]);
		if ($url === '') {
			throw new RuntimeException('The URL is empty.');
		}

		return new ShareIconURL($url, $url);
	}
}
