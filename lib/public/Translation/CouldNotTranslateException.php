<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Translation;

/**
 * @since 27.0.0
 * @deprecated 30.0.0
 */
class CouldNotTranslateException extends \RuntimeException {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		protected ?string $from,
	) {
		parent::__construct();
	}

	/**
	 * @since 27.0.0
	 */
	public function getFrom(): ?string {
		return $this->from;
	}
}
