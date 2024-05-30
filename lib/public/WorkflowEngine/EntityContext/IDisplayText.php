<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\EntityContext;

/**
 * Interface IDisplayText
 *
 *
 * @since 18.0.0
 */
interface IDisplayText {
	/**
	 * returns translated text used for display to the end user. For instance,
	 * it can describe the event in a human readable way.
	 *
	 * The entity may react to a verbosity level that is provided. With the
	 * basic level, 0, it would return brief information, and more with higher
	 * numbers. All information shall be shown at a level of 3.
	 *
	 * @since 18.0.0
	 */
	public function getDisplayText(int $verbosity = 0): string;
}
