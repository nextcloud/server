<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\SpeechToText;

/**
 * @since 29.0.0
 * @deprecated 30.0.0
 */
interface ISpeechToTextProviderWithUserId extends ISpeechToTextProvider {
	/**
	 * @since 29.0.0
	 */
	public function setUserId(?string $userId): void;
}
