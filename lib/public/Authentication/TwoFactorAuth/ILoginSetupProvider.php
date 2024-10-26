<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\Template;

/**
 * @since 17.0.0
 */
interface ILoginSetupProvider {
	/**
	 * @return Template
	 *
	 * @since 17.0.0
	 */
	public function getBody(): Template;
}
