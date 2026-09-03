<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareByMail\Settings;

use OCP\IAppConfig;

class SettingsManager {

	private $sendPasswordByMailDefault = 'yes';

	private $replyToInitiatorDefault = 'yes';

	private $ccToInitiatorDefault = 'no';

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * should the password for a mail share be send to the recipient
	 *
	 * @return bool
	 */
	public function sendPasswordByMail(): bool {
		$sendPasswordByMail = $this->appConfig->getValueString('sharebymail', 'sendpasswordmail', $this->sendPasswordByMailDefault);
		return $sendPasswordByMail === 'yes';
	}

	/**
	 * should add reply to with initiator mail
	 *
	 * @return bool
	 */
	public function replyToInitiator(): bool {
		$replyToInitiator = $this->appConfig->getValueString('sharebymail', 'replyToInitiator', $this->replyToInitiatorDefault);
		return $replyToInitiator === 'yes';
	}

	/**
	 * should the initiator be added to the recipients in CC
	 *
	 * @return bool
	 */
	public function ccToInitiator(): bool {
		$ccToInitiator = $this->appConfig->getValueString('sharebymail', 'ccToInitiator', $this->ccToInitiatorDefault);
		return $ccToInitiator === 'yes';
	}
}
