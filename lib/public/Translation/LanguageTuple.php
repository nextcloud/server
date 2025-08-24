<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\Translation;

use JsonSerializable;

/**
 * @since 26.0.0
 * @deprecated 30.0.0
 */
class LanguageTuple implements JsonSerializable {
	/**
	 * @since 26.0.0
	 */
	public function __construct(
		private string $from,
		private string $fromLabel,
		private string $to,
		private string $toLabel,
	) {
	}

	/**
	 * @since 26.0.0
	 * @return array{from: string, fromLabel: string, to: string, toLabel: string}
	 */
	public function jsonSerialize(): array {
		return [
			'from' => $this->from,
			'fromLabel' => $this->fromLabel,
			'to' => $this->to,
			'toLabel' => $this->toLabel,
		];
	}

	/**
	 * @since 26.0.0
	 */
	public static function fromArray(array $data): LanguageTuple {
		return new self(
			$data['from'],
			$data['fromLabel'],
			$data['to'],
			$data['toLabel'],
		);
	}
}
