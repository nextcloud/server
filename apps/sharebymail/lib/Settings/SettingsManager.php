<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareByMail\Settings;

use OCP\IConfig;

class SettingsManager {

	private $sendPasswordByMailDefault = 'yes';

	private $replyToInitiatorDefault = 'yes';

	private $useUserEmailDefault = 'no';

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * should the password for a mail share be send to the recipient
	 *
	 * @return bool
	 */
	public function sendPasswordByMail(): bool {
		$sendPasswordByMail = $this->config->getAppValue('sharebymail', 'sendpasswordmail', $this->sendPasswordByMailDefault);
		return $sendPasswordByMail === 'yes';
	}

	/**
	 * should add reply to with initiator mail
	 *
	 * @return bool
	 */
	public function replyToInitiator(): bool {
		$replyToInitiator = $this->config->getAppValue('sharebymail', 'replyToInitiator', $this->replyToInitiatorDefault);
		return $replyToInitiator === 'yes';
	}

	/**
	 * Should share-by-mail emails be sent using the user's personal email
	 * address (via Mail Provider) instead of the global system email address.
	 *
	 * When enabled and a Mail Provider is available for the user, share
	 * notification emails will be sent from the user's own address, similar
	 * to how calendar invitations work.
	 *
	 * @return bool
	 * @psalm-suppress DeprecatedMethod
	 */
	public function useUserEmail(): bool {
		$useUserEmail = $this->config->getAppValue('sharebymail', 'useUserEmail', $this->useUserEmailDefault);
		return $useUserEmail === 'yes';
	}
}
