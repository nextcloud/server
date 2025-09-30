<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\SourceTypes;

use OCA\Sharing\Model\AShareSourceType;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\Server;

class NodeShareSourceType extends AShareSourceType {
	public function validateSource(IUser $creator, string $source): bool {
		return Server::get(IRootFolder::class)->getUserFolder($creator->getUID())->getFirstNodeById((int)$source) !== null;
	}
}
