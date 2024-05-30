<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\EntityContext;

/**
 * Interface IIcon
 *
 *
 * @since 18.0.0
 */
interface IIcon {
	/**
	 * returns a URL to an icon that is related to the entity, for instance
	 * a group icon for groups.
	 *
	 * @since 18.0.0
	 */
	public function getIconUrl(): string;
}
