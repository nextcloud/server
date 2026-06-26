<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication;

/**
 * Provider exposing one or multiple IAlternativeLogin.
 *
 * @since 34.0.0
 */
interface IAlternativeLoginProvider {
	/**
	 * @return list<IAlternativeLogin>
	 * @since 34.0.0
	 */
	public function getAlternativeLogins(): array;
}
