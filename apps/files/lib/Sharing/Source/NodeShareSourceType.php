<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Source;

use OCA\Files\AppInfo\Application;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Source\IShareSourceType;

final readonly class NodeShareSourceType implements IShareSourceType {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('File or folder');
	}

	#[\Override]
	public function validateSource(IUser $owner, string $source): bool {
		return Server::get(IRootFolder::class)->getUserFolder($owner->getUID())->getFirstNodeById((int)$source) !== null;
	}

	#[\Override]
	public function getSourceDisplayName(string $source): ?string {
		$displayName = Server::get(IRootFolder::class)->getFirstNodeById((int)$source)?->getName();
		if ($displayName === '') {
			return null;
		}

		return $displayName;
	}
}
