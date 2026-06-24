<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV\Import;

final readonly class ImportCountEvent implements ImportEvent {
	public function __construct(
		public int $vcard
	) {
	}

	public function total(): int {
		return $this->vcard;
	}

	/**
	 * @return array{type: 'count', vcard: int}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'type' => 'count',
			'vcard' => $this->total(),
		];
	}
}
