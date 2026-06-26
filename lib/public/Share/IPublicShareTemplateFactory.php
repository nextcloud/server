<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

/**
 * @since 26.0.0
 */
interface IPublicShareTemplateFactory {
	/**
	 * Returns a provider that is willing to respond for given share.
	 * @since 26.0.0
	 */
	public function getProvider(IShare $share): IPublicShareTemplateProvider;
}
