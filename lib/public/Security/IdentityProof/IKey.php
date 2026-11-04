<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\IdentityProof;

use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '36.0.0')]
interface IKey {
	/** @since 36.0.0 */
	public function getAlgorithm(): Algorithm;

	/** @since 36.0.0 */
	public function getPrivate(): string;

	/** @since 36.0.0 */
	public function getPublic(): string;
}
