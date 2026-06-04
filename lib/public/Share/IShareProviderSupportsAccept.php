<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

/**
 * Interface IShareProviderSupportsAccept
 *
 * This interface allows to define IShareProvider that can handle the `acceptShare` method,
 * which is available since Nextcloud 17.
 *
 * @since 30.0.0
 */
interface IShareProviderSupportsAccept extends IShareProvider {
	/**
	 * Accept a share.
	 *
	 * @param IShare $share
	 * @param string $recipient
	 * @return IShare The share object
	 * @since 30.0.0
	 */
	public function acceptShare(IShare $share, string $recipient): IShare;
}
