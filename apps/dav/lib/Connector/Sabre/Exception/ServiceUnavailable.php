<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre\Exception;

use Sabre\DAV\Exception\ServiceUnavailable as SabreServiceUnavailable;

class ServiceUnavailable extends SabreServiceUnavailable implements ITranslatedSabreException {

	public function __construct(
		string $message,
		private readonly string $translatedMessage,
	) {
		parent::__construct($message, 0, null);
	}

	public function getTranslatedMessage(): string {
		return $this->translatedMessage;
	}
}
