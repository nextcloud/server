<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Resolves the teams related to a file, whatever this provider's own resource
 * ids look like.
 *
 * Asked alongside the addressed provider whenever a lookup comes in by file id,
 * so one provider can contribute what another cannot see. A team folder is
 * mounted rather than shared, so a share lookup never finds it.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
#[Implementable(since: '35.0.0')]
interface ITeamFileResolver extends ITeamResourceProvider {
	/**
	 * Return the ids of the teams the given file belongs to.
	 *
	 * May include teams the current user cannot see. The caller filters those out.
	 *
	 * @return list<string> Empty when the file is unrelated to this provider.
	 * @since 35.0.0
	 */
	public function getTeamsForFile(int $fileId): array;
}
