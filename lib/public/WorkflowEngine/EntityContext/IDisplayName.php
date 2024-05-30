<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\EntityContext;

/**
 * Interface IDisplayName
 *
 *
 * @since 18.0.0
 */
interface IDisplayName {
	/**
	 * returns the end user facing name of the object related to the entity
	 *
	 * @since 18.0.0
	 */
	public function getDisplayName(): string;
}
