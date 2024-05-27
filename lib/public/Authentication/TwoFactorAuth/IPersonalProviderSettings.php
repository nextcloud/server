<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\Template;

/**
 * Interface IPersonalProviderSettings
 *
 * @since 15.0.0
 */
interface IPersonalProviderSettings {
	/**
	 * @return Template
	 *
	 * @since 15.0.0
	 */
	public function getBody(): Template;
}
