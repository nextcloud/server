<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Import;

final readonly class ImportCountEvent implements ImportEvent {
	public function __construct(
		public int $vevent,
		public int $vtodo,
		public int $vjournal,
	) {
	}

	public function total(): int {
		return $this->vevent + $this->vtodo + $this->vjournal;
	}

	/**
	 * @return array{type: 'count', vevent: int, vtodo: int, vjournal: int}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'type' => 'count',
			'vevent' => $this->vevent,
			'vtodo' => $this->vtodo,
			'vjournal' => $this->vjournal,
		];
	}
}
