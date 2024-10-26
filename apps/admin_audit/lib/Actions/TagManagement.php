<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

use OCP\SystemTag\ISystemTag;

class TagManagement extends Action {
	/**
	 * @param ISystemTag $tag newly created tag
	 */
	public function createTag(ISystemTag $tag): void {
		$this->log('System tag "%s" (%s, %s) created',
			[
				'name' => $tag->getName(),
				'visbility' => $tag->isUserVisible() ? 'visible' : 'invisible',
				'assignable' => $tag->isUserAssignable() ? 'user assignable' : 'system only',
			],
			['name', 'visibility', 'assignable']
		);
	}
}
