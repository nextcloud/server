<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TextProcessing;

/**
 * This is a task type interface that is implemented by text processing
 * task types
 * @since 27.1.0
 * @deprecated 30.0.0
 */
interface ITaskType {
	/**
	 * Returns the localized name of this task type
	 *
	 * @since 27.1.0
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Returns the localized description of this task type
	 *
	 * @since 27.1.0
	 * @return string
	 */
	public function getDescription(): string;
}
