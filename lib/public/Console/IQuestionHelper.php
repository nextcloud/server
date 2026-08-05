<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IQuestionHelper {
	/**
	 * @param mixed $question One of Question from Symfony/Console
	 * @return mixed The question answer
	 * @since 35.0.0
	 */
	public function ask(mixed $question): mixed;
}
