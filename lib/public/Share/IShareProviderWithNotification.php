<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

/**
 * Interface IShareProvider
 *
 * @since 30.0.0
 */
interface IShareProviderWithNotification extends IShareProvider {
	/**
	 * Send a mail notification to the recipient of a share
	 * @param IShare $share
	 * @return bool True if the mail was sent successfully
	 * @throws \Exception If the mail could not be sent
	 * @since 30.0.0
	 */
	public function sendMailNotification(IShare $share): bool;
}
