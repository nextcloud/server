<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\Template\ITemplate;

/**
 * @since 17.0.0
 */
interface ILoginSetupProvider {
	/**
	 * @since 17.0.0
	 * @since 32.0.0 Broader return type ITemplate instead of \OCP\Template
	 */
	public function getBody(): ITemplate;
}
