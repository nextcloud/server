<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Recipient;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IShareRecipientTypePublicSecret extends IShareRecipientType {
	/**
	 * @param non-empty-string $recipient
	 * @experimental 35.0.0
	 */
	public function isSecretPublic(string $recipient): bool;

	/**
	 * @param non-empty-string $recipient
	 * @experimental 35.0.0
	 */
	public function isSecretUpdatable(string $recipient): bool;
}
